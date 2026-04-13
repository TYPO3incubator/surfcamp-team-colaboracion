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
        $eventData = [
            'user' => 1,
            'isOpen' => true,
        ];
        // Open event stream
        $eventStream = SSE\Stream\SelfEmittingEventStream::create();
        $eventStream->open();

        $eventStream->sendEvent(new MyCustomEvent('MyCustomEvent', $eventData));

        // Send message
        $eventStream->sendMessage('myCustomEvent');

        // Close event stream
        $eventStream->close();

        // return content type has to be "text/event-stream"
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/event-stream');
    }
}
