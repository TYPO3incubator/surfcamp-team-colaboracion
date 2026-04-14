<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\EventListener;

use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonSize;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsEventListener(
    identifier: 'collaboration/backend/add-history-button',
)]
final readonly class AddHistoryButtonEvent
{
    public function __construct(
        protected readonly ComponentFactory $componentFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (str_starts_with($event->getRequest()->getAttribute('route')->getPath(), '/module/web/layout')) {
            $buttons = $event->getButtons();
            $request = $event->getRequest();

            $currentTable = 'pages';
            $currentPageId = $request->getAttribute('pageContext')->pageId;

            $urlParameters = [
                'element' => $currentTable . ':' . $currentPageId, // TODO | originally the first variable was a "schema"
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            ];
            $recordHistoryUrl = (string) $this->uriBuilder->buildUriFromRoute('record_history', $urlParameters);

            $recordHistory = GeneralUtility::makeInstance(RecordHistory::class, $currentTable . ':' . $currentPageId);
            $recordChangelog = $recordHistory->getChangeLog();

            $showHistoryAnchor = $this->componentFactory->createLinkButton()
                ->setHref($recordHistoryUrl)
                ->setClasses('btn-borderless')
                ->setTitle('Last edited ' . date('d-m-Y, G:i', $recordChangelog[0]['tstamp']))
                ->setSize(ButtonSize::SMALL)
                ->setShowLabelText(true);

            // TODO | Find out who last edited -> Update title

            // TODO | Respect current workspace

            $buttons[ButtonBar::BUTTON_POSITION_RIGHT][-1][] = clone $showHistoryAnchor;
            $event->setButtons($buttons);
        }
    }
}
