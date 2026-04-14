<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\EventListener;

use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonSize;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
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
        protected readonly BackendUserRepository $backendUserRepository,
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
            $lastRecordChange = $recordHistory->getChangeLog()[0];
            $lastRecordChangeUser = $this->backendUserRepository->findByUid($lastRecordChange['userid']);

            $showHistoryAnchor = $this->componentFactory->createGenericButton()
                ->setHref($recordHistoryUrl)
                ->setClasses('btn-borderless')
                ->setLabel('Last edited ' . date('d.m.y, G:i', $lastRecordChange['tstamp'])) // TODO | Move to locallang.xlf
                ->setTag('typo3-backend-contextual-record-edit-trigger')
                ->setAttributes(['url' => $recordHistoryUrl])
                ->setTitle('Last edited ' . date('d.m.y, G:i', $lastRecordChange['tstamp']) . ' by ' . $lastRecordChangeUser->getUserName()) // TODO | Move to locallang.xlf
                ->setSize(ButtonSize::SMALL)
                ->setShowLabelText(true);

            // TODO | Modify "Close"-Button in changelog context panel / Implement new layout with route like "record_edit_contextual"
            // TODO | Respect current workspace?

            $buttons[ButtonBar::BUTTON_POSITION_RIGHT][-1][] = clone $showHistoryAnchor;
            $event->setButtons($buttons);
        }
    }
}
