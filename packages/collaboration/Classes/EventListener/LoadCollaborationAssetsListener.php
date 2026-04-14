<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;

#[AsEventListener('collaboration/load-assets')]
final readonly class LoadCollaborationAssetsListener
{
    public function __construct(
        private PageRenderer $pageRenderer,
    ) {}

    public function __invoke(AfterBackendPageRenderEvent $event): void
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-avatars.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-badge.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-highlight.js');
    }
}
