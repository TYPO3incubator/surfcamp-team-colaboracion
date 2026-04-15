<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use EliasHaeussler\SSE;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Service\EventMessageService;
use TYPO3Incubator\Collaboration\Stream\Event\StreamEvent;

#[AsController]
final readonly class StreamController
{
    public function __construct(
        private EventMessageService $eventMessageService
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
                    if ($eventMessage['users_to_inform'] !== '') {
                        $usersToInform = GeneralUtility::intExplode(',', $eventMessage['users_to_inform']);
                        if (!in_array($GLOBALS['BE_USER']->user['uid'], $usersToInform)) {
                            continue;
                        }
                    }
                    $stream->sendEvent(new StreamEvent($eventMessage['name'], $eventData));
                }
                sleep(2);
                // clear table afterwards
                $this->eventMessageService->cleanUp();
            }

            echo str_repeat(' ', 4096); // force buffer flush

            if (connection_aborted()) {
                break;
            }

            sleep(2);
        }

        $stream->close();
        exit();
    }
}
