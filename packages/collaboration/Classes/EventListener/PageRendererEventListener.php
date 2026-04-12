<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeBackendPageRenderEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

#[AsEventListener]
class PageRendererEventListener
{
    public function __invoke(BeforeBackendPageRenderEvent $event): void
    {
        $event->javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create(
                '@collaboration/event-stream/event.js',
            ),
        );
    }
}