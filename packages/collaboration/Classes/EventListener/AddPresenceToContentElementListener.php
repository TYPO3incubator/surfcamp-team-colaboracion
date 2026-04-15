<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\View\Event\AfterPageContentPreviewRenderedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener('collaboration/add-presence-to-content-element')]
final class AddPresenceToContentElementListener
{
    /**
     * Mock: hardcoded UIDs that are "being edited".
     * Will be replaced by real presence data from the data layer.
     */
    private const MOCK_EDITING_UIDS = [46, 50];

    public function __invoke(AfterPageContentPreviewRenderedEvent $event): void
    {
        $record = $event->getRecord();
        $uid = $record->getUid();

        if (!in_array($uid, self::MOCK_EDITING_UIDS, true)) {
            return;
        }

        $mockCounts = [46 => 1, 50 => 2];
        $count = $mockCounts[$uid] ?? 1;

        $presenceHtml = sprintf(
            '<div class="t3-page-ce-editing-info">'
            . '<typo3-collaboration-badge count="%d" record-id="tt_content:%d"></typo3-collaboration-badge>'
            . '<span class="collaboration-editing-label">This element is currently being edited.</span>'
            . '</div>',
            $count,
            $uid
        );

        $content = $event->getPreviewContent();
        $event->setPreviewContent($presenceHtml . $content);
    }
}
