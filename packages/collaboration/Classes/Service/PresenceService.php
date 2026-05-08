<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Service;

use TYPO3\CMS\Backend\Backend\Avatar\DefaultAvatarProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class PresenceService
{
    private const TABLE = 'tx_collaboration_presence';
    private const IDLE_THRESHOLD = 30;
    private const GONE_THRESHOLD = 120;

    // Matches the 2 h read-window in BackendUtility::isRecordLocked(): rows older than this
    // are already invisible in the core UI; we delete them so the table stops growing
    // unboundedly when users close their browser without logging off.
    private const SYS_LOCKED_TTL = 7200;
    // Grace window for `sys_lockedrecords` orphan cleanup: a Save flow inserts a fresh lock
    // and the SSE only reconnects ~1 s later, so we must not reap brand-new locks.
    private const SYS_LOCKED_ORPHAN_GRACE = 30;

    /** @var array<int, array{displayName: string, avatarUrl: ?string}> */
    private array $userInfoCache = [];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly DefaultAvatarProvider $avatarProvider,
    ) {}

    /**
     * SSE heartbeat from {@see StreamController}: refreshes page/module/record context
     * but never touches `field`, which is owned by the focus AJAX path.
     */
    public function heartbeat(
        int $userId,
        string $sessionId,
        int $pageId,
        string $module,
        ?string $recordTable = null,
        ?int $recordUid = null,
    ): void {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $now = time();

        // Only overwrite record_table/record_uid when this heartbeat itself carries a record
        // context (e.g. /record/edit URLs). On non-record pages (web_layout) we leave the
        // existing values intact so AJAX-owned focus state isn't blown away.
        $connection->executeStatement(
            'INSERT INTO ' . self::TABLE
            . ' (userid, session_id, page_id, module, record_table, record_uid, field, first_seen, last_seen)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE'
            . ' page_id = VALUES(page_id),'
            . ' module = VALUES(module),'
            . ' record_table = IF(VALUES(record_table) <> \'\', VALUES(record_table), record_table),'
            . ' record_uid = IF(VALUES(record_uid) > 0, VALUES(record_uid), record_uid),'
            . ' last_seen = VALUES(last_seen)',
            [
                $userId,
                $sessionId,
                $pageId,
                $module,
                (string)($recordTable ?? ''),
                (int)($recordUid ?? 0),
                '',
                $now,
                $now,
            ]
        );
    }

    /**
     * Focus AJAX from {@see AjaxController}: writes record + field, never module/page beyond
     * the initial INSERT (the SSE heartbeat is authoritative for those).
     */
    public function setFieldFocus(
        int $userId,
        string $sessionId,
        int $pageId,
        string $table,
        int $uid,
        string $field,
    ): void {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $now = time();

        $connection->executeStatement(
            'INSERT INTO ' . self::TABLE
            . ' (userid, session_id, page_id, module, record_table, record_uid, field, first_seen, last_seen)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE'
            . ' record_table = VALUES(record_table),'
            . ' record_uid = VALUES(record_uid),'
            . ' field = VALUES(field),'
            . ' last_seen = VALUES(last_seen)',
            [
                $userId,
                $sessionId,
                $pageId,
                'edit',
                $table,
                $uid,
                $field,
                $now,
                $now,
            ]
        );
    }

    /**
     * Blur AJAX: drop the field (and the record-edit context) but keep the row so the user
     * still shows up as present on the page.
     */
    public function clearFieldFocus(int $userId, string $sessionId): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->executeStatement(
            'UPDATE ' . self::TABLE
            . ' SET record_table = ?, record_uid = ?, field = ?, last_seen = ?'
            . ' WHERE userid = ? AND session_id = ?',
            ['', 0, '', time(), $userId, $sessionId]
        );
    }

    /**
     * @return list<array{uid: int, displayName: string, avatarUrl: ?string, module: string,
     *                    editingRecord: ?array{table: string, uid: int}, activeField: ?string,
     *                    activeSince: int, idle: bool}>
     */
    public function getPagePresence(int $pageId): array
    {
        $now = time();
        $cutoff = $now - self::GONE_THRESHOLD;

        $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $rows = $qb
            ->select('userid', 'module', 'record_table', 'record_uid', 'field', 'first_seen', 'last_seen')
            ->from(self::TABLE)
            ->where(
                $qb->expr()->eq('page_id', $qb->createNamedParameter($pageId, Connection::PARAM_INT)),
                $qb->expr()->gt('last_seen', $qb->createNamedParameter($cutoff, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $users = [];
        foreach ($rows as $row) {
            $userId = (int)$row['userid'];
            $info = $this->resolveUserInfo($userId);
            if ($info === null) {
                continue;
            }
            $age = $now - (int)$row['last_seen'];
            $sessionAge = $now - (int)$row['first_seen'];
            $recordTable = (string)$row['record_table'];
            $recordUid = (int)$row['record_uid'];
            $users[] = [
                'uid' => $userId,
                'displayName' => $info['displayName'],
                'avatarUrl' => $info['avatarUrl'],
                'module' => (string)$row['module'],
                'editingRecord' => $recordTable !== '' && $recordUid > 0
                    ? ['table' => $recordTable, 'uid' => $recordUid]
                    : null,
                'activeField' => ($row['field'] ?? '') !== '' ? (string)$row['field'] : null,
                'activeSince' => max(1, (int)round($sessionAge / 60)),
                'idle' => $age > self::IDLE_THRESHOLD,
            ];
        }

        return $users;
    }

    /**
     * Record editors grouped by "table:uid".
     *
     * @return array<string, array{count: int, users: list<array{uid: int, displayName: string,
     *                                                            avatarUrl: ?string, activeField: ?string,
     *                                                            idle: bool}>}>
     */
    public function getRecordEditors(int $pageId): array
    {
        $now = time();
        $cutoff = $now - self::GONE_THRESHOLD;

        $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $rows = $qb
            ->select('userid', 'record_table', 'record_uid', 'field', 'last_seen')
            ->from(self::TABLE)
            ->where(
                $qb->expr()->eq('page_id', $qb->createNamedParameter($pageId, Connection::PARAM_INT)),
                $qb->expr()->neq('record_table', $qb->createNamedParameter('')),
                $qb->expr()->gt('record_uid', $qb->createNamedParameter(0, Connection::PARAM_INT)),
                $qb->expr()->gt('last_seen', $qb->createNamedParameter($cutoff, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $grouped = [];
        foreach ($rows as $row) {
            $userId = (int)$row['userid'];
            $info = $this->resolveUserInfo($userId);
            if ($info === null) {
                continue;
            }
            $key = (string)$row['record_table'] . ':' . (int)$row['record_uid'];
            $age = $now - (int)$row['last_seen'];

            if (!isset($grouped[$key])) {
                $grouped[$key] = ['count' => 0, 'users' => []];
            }
            // Deduplicate: one user per record
            foreach ($grouped[$key]['users'] as $existing) {
                if ($existing['uid'] === $userId) {
                    continue 2;
                }
            }
            $grouped[$key]['users'][] = [
                'uid' => $userId,
                'displayName' => $info['displayName'],
                'avatarUrl' => $info['avatarUrl'],
                'activeField' => ($row['field'] ?? '') !== '' ? (string)$row['field'] : null,
                'idle' => $age > self::IDLE_THRESHOLD,
            ];
            $grouped[$key]['count']++;
        }

        return $grouped;
    }

    /**
     * @return array{pageUsers: list<array>, editingRecords: array<string, array>, currentUserUid: int}
     */
    public function buildPresencePayload(int $pageId, int $currentUserUid): array
    {
        $pageUsers = $this->getPagePresence($pageId);
        $editingRecords = $this->getRecordEditors($pageId);

        usort($pageUsers, static function (array $a, array $b) use ($currentUserUid): int {
            if ($a['uid'] === $currentUserUid) return -1;
            if ($b['uid'] === $currentUserUid) return 1;
            return $a['uid'] <=> $b['uid'];
        });

        return [
            'pageUsers' => $pageUsers,
            'editingRecords' => $editingRecords,
            'currentUserUid' => $currentUserUid,
        ];
    }

    public function expireStale(): void
    {
        $now = time();
        $cutoff = $now - self::GONE_THRESHOLD;
        $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $qb
            ->delete(self::TABLE)
            ->where($qb->expr()->lt('last_seen', $qb->createNamedParameter($cutoff, Connection::PARAM_INT)))
            ->executeStatement();

        // Two-stage prune of TYPO3 core's `sys_lockedrecords` (core never deletes; rows
        // would otherwise accumulate and the legacy "being edited by …" warning would lie):
        //
        //   1. Hard TTL: rows older than core's own 2 h read-window are invisible anyway,
        //      drop them so the table doesn't grow unboundedly.
        //   2. Orphan cleanup: drop locks whose owner has no active presence row pointing
        //      at the same record. The 30 s grace window keeps fresh save-induced locks
        //      alive long enough for the SSE to reconnect on the new edit-form page.
        $sysLockedConnection = $this->connectionPool->getConnectionForTable('sys_lockedrecords');
        $sysLockedConnection->executeStatement(
            'DELETE FROM sys_lockedrecords WHERE tstamp < ?',
            [$now - self::SYS_LOCKED_TTL]
        );
        $sysLockedConnection->executeStatement(
            'DELETE l FROM sys_lockedrecords l'
            . ' WHERE l.tstamp < ?'
            . ' AND NOT EXISTS ('
            . '   SELECT 1 FROM ' . self::TABLE . ' p'
            . '   WHERE p.userid = l.userid'
            . '     AND p.record_table = l.record_table'
            . '     AND p.record_uid = l.record_uid'
            . ' )',
            [$now - self::SYS_LOCKED_ORPHAN_GRACE]
        );
    }

    public function deleteSession(int $userId, string $sessionId): void
    {
        // Look up the record this session was editing before we drop the row, so we can
        // also clear the matching `sys_lockedrecords` entry without waiting for the
        // periodic orphan sweep in expireStale().
        $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $row = $qb
            ->select('record_table', 'record_uid')
            ->from(self::TABLE)
            ->where(
                $qb->expr()->eq('userid', $qb->createNamedParameter($userId, Connection::PARAM_INT)),
                $qb->expr()->eq('session_id', $qb->createNamedParameter($sessionId)),
            )
            ->executeQuery()
            ->fetchAssociative();

        $this->connectionPool->getConnectionForTable(self::TABLE)->delete(self::TABLE, [
            'userid' => $userId,
            'session_id' => $sessionId,
        ]);

        if ($row && (string)$row['record_table'] !== '' && (int)$row['record_uid'] > 0) {
            $this->connectionPool
                ->getConnectionForTable('sys_lockedrecords')
                ->delete('sys_lockedrecords', [
                    'userid' => $userId,
                    'record_table' => (string)$row['record_table'],
                    'record_uid' => (int)$row['record_uid'],
                ]);
        }
    }

    private function resolveUserInfo(int $userId): ?array
    {
        if (isset($this->userInfoCache[$userId])) {
            return $this->userInfoCache[$userId];
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
        // Only the columns the avatar provider + display-name resolution actually use.
        // `be_users` carries large blob columns (TSconfig, userMounts, …) we don't need.
        $row = $qb
            ->select('uid', 'realName', 'username')
            ->from('be_users')
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        $info = [
            'displayName' => $row['realName'] ?: $row['username'],
            'avatarUrl' => $this->resolveAvatarUrl($row),
        ];
        $this->userInfoCache[$userId] = $info;
        return $info;
    }

    private function resolveAvatarUrl(array $backendUserRecord): ?string
    {
        $image = $this->avatarProvider->getImage($backendUserRecord, 32);
        return $image?->getUrl();
    }
}
