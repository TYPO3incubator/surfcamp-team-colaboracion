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
        // event.js is now loaded in the module frame by AddPresenceToDocHeaderListener
        // so that SSE has correct window.location context for presence features.
        // input.js (field tracking) stays in the outer backend frame.
        $event->javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create(
                '@collaboration/event-stream/input.js',
            ),
        );
    }
}
