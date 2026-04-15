<?php

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AjaxController
{
    public function focusAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        // Sync ping from client — no DB write needed; stream re-emits state.
        if (!empty($data['sync'])) {
            return new JsonResponse(['status' => 'ok']);
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_collaboration_event');

        $userId = $GLOBALS['BE_USER']->user['uid'];

        // Blur: drop this user's active focus row.
        if (!empty($data['blur'])) {
            $connection->delete('sys_collaboration_event', [
                'user_id' => $userId,
                'type' => 'focus',
            ]);
            return new JsonResponse(['status' => 'ok']);
        }

        $payload = [
            'table' => $data['table'],
            'uid' => $data['uid'],
            'field' => $data['field'],
            'user_id' => $userId,
        ];

        if (empty($data['heartbeat'])) {
            // New focus → ensure only one focus row per user.
            $connection->delete('sys_collaboration_event', [
                'user_id' => $userId,
                'type' => 'focus',
            ]);
            $connection->insert('sys_collaboration_event', [
                'timestamp' => time(),
                'user_id' => $userId,
                'page_id' => $payload['uid'],
                'type' => 'focus',
                'payload' => $payload,
            ]);
        } else {
            $affected = $connection->update(
                'sys_collaboration_event',
                ['timestamp' => time()],
                ['user_id' => $userId, 'type' => 'focus']
            );
            if ($affected === 0) {
                $connection->insert('sys_collaboration_event', [
                    'timestamp' => time(),
                    'user_id' => $userId,
                    'page_id' => $payload['uid'],
                    'type' => 'focus',
                    'payload' => $payload,
                ]);
            }
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
