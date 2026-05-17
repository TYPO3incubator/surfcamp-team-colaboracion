<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Service;

use TYPO3\CMS\Core\Database\Connection;
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

    // Messages strictly newer than the caller's cursor, oldest first. Each SSE
    // worker advances its own cursor, so a worker never re-delivers a message
    // to its own user — and never deletes a message another worker hasn't read.
    public function getMessagesSince(int $sinceTimestamp): array
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->select('*')
            ->from(self::MESSAGE_TABLE)
            ->where(
                $queryBuilder->expr()->gt(
                    'timestamp',
                    $queryBuilder->createNamedParameter($sinceTimestamp, Connection::PARAM_INT)
                )
            )
            ->orderBy('timestamp', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    // TTL-based prune. Replaces the old `DELETE FROM sys_event_messages` (no
    // WHERE), which raced: one worker would wipe rows another worker had not
    // yet read, dropping events on the floor.
    public function pruneOlderThan(int $cutoffTimestamp): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete(self::MESSAGE_TABLE)
            ->where(
                $queryBuilder->expr()->lt(
                    'timestamp',
                    $queryBuilder->createNamedParameter($cutoffTimestamp, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::MESSAGE_TABLE);
    }
}
