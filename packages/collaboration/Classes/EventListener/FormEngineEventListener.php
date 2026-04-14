<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Controller\Event\AfterFormEnginePageInitializedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Domain\Model\Dto\EventMessageDto;
use TYPO3Incubator\Collaboration\Service\EventMessageService;
use TYPO3Incubator\Collaboration\Service\LockedRecordsService;

#[AsEventListener]
readonly class FormEngineEventListener
{
    public function __construct(
        private LockedRecordsService $lockedRecordsService,
        private EventMessageService $eventMessageService,
    ) {}

    public function __invoke(AfterFormEnginePageInitializedEvent $event): void
    {
        $queryParams = $event->getRequest()->getQueryParams();
        $editRecords = [];

        // get all records the user is editing
        if (isset($queryParams['edit'])) {
            foreach ($queryParams['edit'] as $table => $records) {
                foreach ($records as $uids => $record) {
                    $editRecords[$table] = is_string($uids) ? GeneralUtility::intExplode(',', $uids) : $uids;
                }
            }
        }

        // now create an event message if a record is locked
        foreach ($editRecords as $table => $recordId) {
            $recordLockedBy = $this->lockedRecordsService->recordLockedBy($table, $recordId);
            if (!empty($recordLockedBy)) {
                $usersToInform = [];
                foreach ($recordLockedBy as $user) {
                    $usersToInform[] = $user->getUserid();
                }
                // create a message for the event with the given data
                $message = new EventMessageDto(
                    $GLOBALS['EXEC_TIME'],
                    $GLOBALS['BE_USER']->user['uid'],
                    $GLOBALS['BE_USER']->getUsername(),
                    'clearCacheEvent',
                    json_encode(
                        [
                            'data' => 'User ' . $GLOBALS['BE_USER']->getUsername() . ' started editing this record too. You might overwrite one another changes.'
                        ],
                        JSON_THROW_ON_ERROR
                    ),
                    implode(',', $usersToInform),
                );
                $this->eventMessageService->addMessage($message);
            }
        }
    }
}
