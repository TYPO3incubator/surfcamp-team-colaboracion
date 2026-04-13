<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Hook;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHook
{
    public function postProcessClearCache(): void
    {
        $message = json_encode(
            [
                'data' => 'Cache has cleared. You might want to reload the Backend.'
            ],
            JSON_THROW_ON_ERROR
        );
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_event_messages');
        $queryBuilder
            ->insert('sys_event_messages')
            ->values([
                'timestamp' => $GLOBALS['EXEC_TIME'],
                'owner' => $GLOBALS['BE_USER']->getUsername(),
                'name' => 'clearCacheEvent',
                'message' => $message,
            ])
            ->executeStatement();
    }
}
