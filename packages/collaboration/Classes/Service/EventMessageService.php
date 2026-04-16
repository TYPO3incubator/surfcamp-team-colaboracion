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

    // add a message to the db table with given values
    public function addMessage(EventMessageDto $eventMessage): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->insert(self::MESSAGE_TABLE)
            ->values($eventMessage->toArray())
            ->executeStatement();
    }

    // return an array with all active messages
    public function getAllMessages(): array
    {
        // get all stored messages in db table
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->select('*')
            ->from(self::MESSAGE_TABLE)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    // truncate table
    public function cleanUp(): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete(self::MESSAGE_TABLE)
            ->executeStatement();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::MESSAGE_TABLE);
    }
}
