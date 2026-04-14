<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener('collaboration/add-presence-to-record-list')]
final class AddPresenceToRecordListListener
{
    /**
     * Mock: hardcoded UIDs that are "being edited".
     * Will be replaced by real presence data from the data layer.
     */
    private const MOCK_EDITING_UIDS = [46, 50];

    public function __invoke(AfterRecordListRowPreparedEvent $event): void
    {
        $record = $event->getRecord();
        $uid = $record->getUid();

        if (!in_array($uid, self::MOCK_EDITING_UIDS, true)) {
            return;
        }

        $mockCounts = [5 => 1, 12 => 2, 18 => 3];
        $count = $mockCounts[$uid] ?? 1;

        $badge = sprintf(
            '<typo3-collaboration-badge count="%d"></typo3-collaboration-badge>',
            $count
        );

        $data = $event->getData();
        $data['_CONTROL_'] = $badge . ($data['_CONTROL_'] ?? '');
        $event->setData($data);
    }
}
