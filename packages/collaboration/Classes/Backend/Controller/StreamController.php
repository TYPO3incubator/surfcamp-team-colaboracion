<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use EliasHaeussler\SSE;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Stream\Event\StreamEvent;

#[AsController]
final readonly class StreamController
{
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        // go straight to eventAction
        return $this->eventAction();
    }

    public function eventAction(): ResponseInterface
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        set_time_limit(0);

        $stream = SSE\Stream\SelfEmittingEventStream::create();
        $stream->open();

        while (true) {
            $stream->sendMessage('ping', time());

            // get all stored messages in db table
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_event_messages');
            $eventMessages = $queryBuilder
                ->select('*')
                ->from('sys_event_messages')
                ->executeQuery()
                ->fetchAllAssociative();

            if (!empty($eventMessages)) {
                // execute message events
                foreach ($eventMessages as $eventMessage) {
                    $eventData = json_decode($eventMessage['message'], true);
                    $stream->sendEvent(new StreamEvent($eventMessage['name'], $eventData));
                }
                // clear table afterwards
                $queryBuilder
                    ->delete('sys_event_messages')
                    ->executeStatement();
            }

            echo str_repeat(' ', 4096); // force buffer flush

            if (connection_aborted()) {
                break;
            }

            sleep(2);
        }

        $stream->close();
        exit();
    }
}
