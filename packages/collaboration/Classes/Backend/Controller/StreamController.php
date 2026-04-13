<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use EliasHaeussler\SSE;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3Incubator\Collaboration\Stream\Event\MyCustomEvent;

#[AsController]
final readonly class StreamController
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
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

        $eventData = [
            'user' => 1,
            'isOpen' => true,
        ];

        while (true) {
            $stream->sendMessage('ping', time());
            $stream->sendEvent(new MyCustomEvent('MyCustomEvent', $eventData));

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
