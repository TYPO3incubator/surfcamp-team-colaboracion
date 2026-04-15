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

    protected static function getBackendUserAuthentication(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
