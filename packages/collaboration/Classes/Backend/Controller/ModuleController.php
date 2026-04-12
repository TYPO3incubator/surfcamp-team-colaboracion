<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use EliasHaeussler\SSE;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

#[AsController]
final class ModuleController extends ActionController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function eventAction(): void
    {
        // Open event stream
        $eventStream = SSE\Stream\SelfEmittingEventStream::create();
        $eventStream->open();

        // Send message
        $eventStream->sendMessage('myCustomEvent');

        // Close event stream
        $eventStream->close();
    }
}
