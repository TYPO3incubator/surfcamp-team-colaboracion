<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\EventListener;

use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Backend\Templates\Components\Buttons\ContextualHistoryButton;

#[AsEventListener(
    identifier: 'collaboration/backend/add-history-button',
)]
final readonly class AddHistoryButtonEvent
{
    public function __construct(
        protected ComponentFactory $componentFactory,
        protected IconFactory $iconFactory,
        protected UriBuilder $uriBuilder,
        protected BackendUserRepository $backendUserRepository,
        protected LanguageServiceFactory $languageServiceFactory,
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
            ];
            $recordHistoryUrl = (string) $this->uriBuilder->buildUriFromRoute('record_history', $urlParameters);

            $recordHistory = GeneralUtility::makeInstance(RecordHistory::class, $currentTable . ':' . $currentPageId);
            $lastRecordChange = $recordHistory->getChangeLog()[0];
            $lastRecordChangeUser = $this->backendUserRepository->findByUid($lastRecordChange['userid']);

            $languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
            $lastEditedDate = date('d.m.y, G:i', $lastRecordChange['tstamp']);

            $showHistoryAnchor = GeneralUtility::makeInstance(ContextualHistoryButton::class)
                ->setLabel(sprintf($languageService->sL('LLL:EXT:collaboration/Resources/Private/Language/locallang.xlf:history_button.label'), $lastEditedDate))
                ->setUrl($recordHistoryUrl)
                ->setTitle(sprintf($languageService->sL('LLL:EXT:collaboration/Resources/Private/Language/locallang.xlf:history_button.title'), $lastEditedDate, $lastRecordChangeUser->getUserName()));

            // TODO | Respect current workspace?

            $buttons[ButtonBar::BUTTON_POSITION_RIGHT][-1][] = clone $showHistoryAnchor;
            $event->setButtons($buttons);
        }
    }
}
