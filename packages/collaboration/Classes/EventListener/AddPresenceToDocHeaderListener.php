<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Template\Components\Buttons\RawHtmlButton;

#[AsEventListener('collaboration/add-presence-to-docheader')]
final readonly class AddPresenceToDocHeaderListener
{
    public function __construct(
        private PageRenderer $pageRenderer,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-avatars.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-badge.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-highlight.js');

        $button = GeneralUtility::makeInstance(RawHtmlButton::class);
        $button->setHtml('<typo3-collaboration-avatars></typo3-collaboration-avatars>');

        $buttons = $event->getButtons();
        $buttons['right'][-1][] = $button;
        $event->setButtons($buttons);
    }
}
