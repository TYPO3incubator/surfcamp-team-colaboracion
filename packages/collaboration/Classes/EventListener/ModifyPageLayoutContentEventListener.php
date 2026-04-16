<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;

#[AsEventListener]
final readonly class ModifyPageLayoutContentEventListener
{
    public function __construct(
        private PageRenderer $pageRenderer
    ) {}

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $this->pageRenderer->loadJavaScriptModule(
            '@collaboration/event-stream/contextual-history.js',
        );
    }
}
