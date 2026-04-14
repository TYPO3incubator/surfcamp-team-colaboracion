<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use EliasHaeussler\SSE;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3Incubator\Collaboration\Service\CollaborationEventService;
use TYPO3Incubator\Collaboration\Service\EventMessageService;
use TYPO3Incubator\Collaboration\Stream\Event\StreamEvent;

#[AsController]
final readonly class StreamController
{
    public function __construct(
        private EventMessageService $eventMessageService,
        private CollaborationEventService $collaborationEventService,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        // go straight to eventAction
        return $this->eventAction();
    }

    public function eventAction(): ResponseInterface
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        set_time_limit(0);

        $stream = SSE\Stream\SelfEmittingEventStream::create();
        $stream->open();

        while (true) {
            $stream->sendMessage('ping', time());

            $eventMessages = $this->eventMessageService->getAllMessages();
            if (!empty($eventMessages)) {
                // execute message events
                foreach ($eventMessages as $eventMessage) {
                    $eventData = json_decode($eventMessage['message'], true);
                    $stream->sendEvent(new StreamEvent($eventMessage['name'], $eventData));
                }
                // clear table afterwards
                $this->eventMessageService->cleanUp();
            }

            $events = $this->collaborationEventService->getAllEvents();
            if (!empty($events)) {
                foreach ($events as $event) {
                    if ($GLOBALS['BE_USER']->user['uid'] === $event['user_id']) break;
                    $eventData = json_decode($event['payload'], true);
                    if (time() - $event['timestamp'] < 2) {
                        $stream->sendEvent(new StreamEvent('stream_'.$event['type'], $eventData));
                    } else {
                        $stream->sendEvent(new StreamEvent('stream_blur', $eventData));
                    }

                    if (time() - $event['timestamp'] > 2) {
                            $stream->sendEvent(new StreamEvent('stream_blur', $eventData));
                            $this->collaborationEventService->cleanUp($event['uid']);
                    }
                }
            }

            echo str_repeat(' ', 4096); // force buffer flush

            if (connection_aborted()) {
                break;
            }

            usleep(500000);
        }

        $stream->close();
        exit();
    }
}
