<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use EliasHaeussler\SSE;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Service\EventMessageService;
use TYPO3Incubator\Collaboration\Service\PresenceService;
use TYPO3Incubator\Collaboration\Stream\Event\StreamEvent;

#[AsController]
final class StreamController
{
    public function __construct(
        private readonly EventMessageService $eventMessageService,
        private readonly PresenceService $presenceService,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $pageId = isset($queryParams['pageId']) ? (int)$queryParams['pageId'] : null;
        $module = isset($queryParams['module']) ? (string)$queryParams['module'] : null;
        $recordTable = isset($queryParams['recordTable']) ? (string)$queryParams['recordTable'] : null;
        $recordUid = isset($queryParams['recordUid']) ? (int)$queryParams['recordUid'] : null;

        return $this->eventAction($pageId, $module, $recordTable, $recordUid);
    }

    private function eventAction(
        ?int $pageId,
        ?string $module,
        ?string $recordTable,
        ?int $recordUid,
    ): ResponseInterface {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Release PHP session lock so the long-running SSE doesn't block other requests.
        session_write_close();

        $sessionId = $GLOBALS['BE_USER']?->getSession()?->getIdentifier() ?? '';

        set_time_limit(0);
        // Keep PHP alive after the browser disconnects so we can detect the abort
        // and clean up the presence row instead of leaving stale "still editing" state.
        ignore_user_abort(true);

        $currentUserUid = (int)$GLOBALS['BE_USER']->user['uid'];

        // Shutdown safety net: if PHP terminates for any reason we attempt to remove the row.
        $presenceService = $this->presenceService;
        register_shutdown_function(static function () use ($presenceService, $currentUserUid, $sessionId): void {
            if ($currentUserUid > 0 && $sessionId !== '') {
                try {
                    $presenceService->deleteSession($currentUserUid, $sessionId);
                } catch (\Throwable) {
                }
            }
        });

        $stream = SSE\Stream\SelfEmittingEventStream::create();
        $stream->open();

        $iteration = 0;

        // Extend MySQL wait_timeout so the endless loop outlives the default 8h threshold.
        try {
            $this->connectionPool
                ->getConnectionForTable('tx_collaboration_presence')
                ->executeStatement('SET SESSION wait_timeout = 28800');
        } catch (\Exception $e) {}

        while (true) {
            try {
                $stream->sendMessage('ping', time());

                // Event messages: lockedRecordEvent, clearCacheEvent
                $eventMessages = $this->eventMessageService->getAllMessages();
                if (!empty($eventMessages)) {
                    foreach ($eventMessages as $eventMessage) {
                        $eventData = json_decode($eventMessage['message'], true);
                        if ($eventMessage['users_to_inform'] !== '') {
                            $usersToInform = GeneralUtility::intExplode(',', $eventMessage['users_to_inform']);
                            if (!in_array($currentUserUid, $usersToInform)) {
                                continue;
                            }
                        }
                        $stream->sendEvent(new StreamEvent($eventMessage['name'], $eventData));
                    }
                    usleep(500000);
                    $this->eventMessageService->cleanUp();
                }

                // Presence updates - whenever pageId + module are provided.
                if ($pageId !== null && $module !== null && $currentUserUid > 0 && $sessionId !== '') {
                    $this->presenceService->heartbeat(
                        $currentUserUid,
                        $sessionId,
                        $pageId,
                        $module,
                        $recordTable,
                        $recordUid,
                    );

                    $payload = $this->presenceService->buildPresencePayload($pageId, $currentUserUid);
                    $stream->sendEvent(new StreamEvent('presenceUpdate', $payload));

                    if ($iteration % 10 === 0) {
                        $this->presenceService->expireStale();
                    }
                }

                $iteration++;
                usleep(500000);
            } catch (\Doctrine\DBAL\Exception\ConnectionLost $e) {
                try {
                    $connection = $this->connectionPool->getConnectionForTable('tx_collaboration_presence');
                    $connection->close();
                    $connection->connect();
                    $connection->executeStatement('SET SESSION wait_timeout = 28800');
                } catch (\Exception $reconnectException) {
                    break;
                }
                continue;
            }

            echo str_repeat(' ', 4096);

            if (connection_aborted()) {
                if ($sessionId !== '' && $currentUserUid > 0) {
                    $this->presenceService->deleteSession($currentUserUid, $sessionId);
                }
                break;
            }
        }

        $stream->close();
        exit();
    }
}
