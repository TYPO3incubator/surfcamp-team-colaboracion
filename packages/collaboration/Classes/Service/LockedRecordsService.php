<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Domain\Model\Dto\UsersDto;

class LockedRecordsService
{
    /** @var array<int, array> */
    private array $pageLocksCache = [];

    public function recordLockedBy(string $table, int $recordId): array
    {
        $recordLockedByUsers = [];
        $queryBuilder =  GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_lockedrecords');
        $usersLockingRecord = $queryBuilder
            ->select('*')
            ->from('sys_lockedrecords')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_lockedrecords.record_uid',
                    $queryBuilder->createNamedParameter($recordId)
                ),
                $queryBuilder->expr()->eq(
                    'sys_lockedrecords.record_table',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->neq(
                    'sys_lockedrecords.userid',
                    $queryBuilder->createNamedParameter(
                        static::getBackendUserAuthentication()->user['uid'],
                        Connection::PARAM_INT
                    )
                ),
                $queryBuilder->expr()->gt(
                    'sys_lockedrecords.tstamp',
                    $queryBuilder->createNamedParameter(
                        $GLOBALS['EXEC_TIME'] - 2 * 3600,
                        Connection::PARAM_INT
                    )
                )
            )
            ->groupBy('sys_lockedrecords.userid')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($usersLockingRecord as $userLockingRecord) {
            $recordLockedByUsers[] = new UsersDto($userLockingRecord['userid'], $userLockingRecord['username']);
        }

        return $recordLockedByUsers;
    }

    /**
     * Returns all locked records on a given page with full be_users data.
     * Does NOT filter out current user — PresenceService handles that.
     */
    public function recordsLockedOnPage(int $pageId): array
    {
        if (isset($this->pageLocksCache[$pageId])) {
            return $this->pageLocksCache[$pageId];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_lockedrecords');

        $result = $queryBuilder
            ->select(
                'l.record_table',
                'l.record_uid',
                'l.userid',
                'l.username',
                'l.tstamp',
                'u.realName',
                'u.username AS be_username',
                'u.uid AS be_uid'
            )
            ->from('sys_lockedrecords', 'l')
            ->join(
                'l',
                'be_users',
                'u',
                $queryBuilder->expr()->eq('l.userid', $queryBuilder->quoteIdentifier('u.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'l.record_pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'l.tstamp',
                    $queryBuilder->createNamedParameter(
                        time() - 2 * 3600,
                        Connection::PARAM_INT
                    )
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $this->pageLocksCache[$pageId] = $result;
        return $result;
    }

    protected static function getBackendUserAuthentication(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
