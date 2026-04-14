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
        $data = json_decode($request->getBody()->getContents(), true);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_collaboration_event');

        $payload = [
            'table' => $data['table'],
            'uid' => $data['uid'],
            'field' => $data['field'],
            'user_id' => $GLOBALS['BE_USER']->user['uid'],
        ];

        if (!$data['heartbeat']) {
            $connection->insert('sys_collaboration_event', [
                'timestamp' => time(),
                'user_id' => $GLOBALS['BE_USER']->user['uid'],
                'page_id' => $payload['uid'],
                'type' => 'focus',
                'payload' => $payload,
            ]);
        } else {
            $connection->update(
                'sys_collaboration_event',
                [
                    'timestamp' => time(),
                ],
                [
                    'user_id' => $GLOBALS['BE_USER']->user['uid'],
                    'payload' => json_encode([
                        'table' => $data['table'],
                        'uid' => $data['uid'],
                        'field' => $data['field'],
                        'user_id' => $GLOBALS['BE_USER']->user['uid'],
                    ]),
                ]
            );
        }



        return new JsonResponse(['status' => 'ok']);
    }
}