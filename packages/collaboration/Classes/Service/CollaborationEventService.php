<?php

namespace TYPO3Incubator\Collaboration\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class CollaborationEventService
{
    private const EVENTS_TABLE = 'sys_collaboration_event';

    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {}

    public function getAllEvents(): array
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->select('*')
            ->from(self::EVENTS_TABLE)
            ->orderBy('timestamp', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function cleanUp(int $uid): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete(self::EVENTS_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::EVENTS_TABLE);
    }
}
