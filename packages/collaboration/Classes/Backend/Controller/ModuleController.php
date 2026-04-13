<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use EliasHaeussler\SSE;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

#[AsController]
final readonly class ModuleController
{
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        return $this->eventAction($request, $moduleTemplate);
    }

    public function eventAction(
        ServerRequestInterface $request,
        ModuleTemplate $view
    ): ResponseInterface {
        // Open event stream
        $eventStream = SSE\Stream\SelfEmittingEventStream::create();
        $eventStream->open();

        // Send message
        $eventStream->sendMessage('myCustomEvent');

        // Close event stream
        $eventStream->close();
        return $view->renderResponse('');
    }
}
