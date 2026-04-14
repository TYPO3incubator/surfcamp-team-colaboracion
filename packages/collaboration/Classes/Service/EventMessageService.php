<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3Incubator\Collaboration\Domain\Model\Dto\EventMessageDto;

class EventMessageService
{
    private const MESSAGE_TABLE = 'sys_event_messages';
    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {}

    public function addMessage(EventMessageDto $eventMessage): void
    {
        $queryBuilder = $this->getConnection();
        $queryBuilder
            ->insert(self::MESSAGE_TABLE)
            ->values($eventMessage->toArray())
            ->executeStatement();
    }

    // ToDo: Add "removeMessage" function

    private function getConnection(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::MESSAGE_TABLE);
    }
}
