<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Incubator\Collaboration\Domain\Model\Dto\EventMessageDto;
use TYPO3Incubator\Collaboration\Service\EventMessageService;
use TYPO3Incubator\Collaboration\Service\LockedRecordsService;

readonly class ContextualEditEventMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LockedRecordsService $lockedRecordsService,
        private EventMessageService $eventMessageService,
        private LocalizationUtility $localizationUtility
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        // check if record is in edit mode, either contextual or in expand view
        if (
            str_contains($request->getRequestTarget(), 'record/edit')
        ) {
            $queryParams = $request->getQueryParams();
            $editRecords = [];

            // get all records the user is editing
            if (isset($queryParams['edit'])) {
                foreach ($queryParams['edit'] as $table => $records) {
                    foreach ($records as $uids => $record) {
                        $editRecords[$table] = is_string($uids) ? GeneralUtility::intExplode(',', $uids) : $uids;
                    }
                }
            }

            // now create an event message if a record is locked
            foreach ($editRecords as $table => $recordId) {
                $recordLockedBy = $this->lockedRecordsService->recordLockedBy($table, $recordId);
                if (!empty($recordLockedBy)) {
                    $usersToInform = [];
                    foreach ($recordLockedBy as $user) {
                        $usersToInform[] = $user->getUserid();
                    }
                    // create a message for the event with the given data
                    $message = new EventMessageDto(
                        $GLOBALS['EXEC_TIME'],
                        $GLOBALS['BE_USER']->user['uid'],
                        $GLOBALS['BE_USER']->getUsername(),
                        'lockedRecordEvent',
                        json_encode(
                            [
                                'title' => $this->localizationUtility->translate(
                                    'sse.parallel_editing.title',
                                    'collaboration',
                                    [$GLOBALS['BE_USER']->getUsername()]
                                ),
                                'message' => $this->localizationUtility->translate(
                                    'sse.parallel_editing.message',
                                    'collaboration'
                                ),
                            ],
                            JSON_THROW_ON_ERROR
                        ),
                        implode(',', $usersToInform),
                    );
                    $this->eventMessageService->addMessage($message);
                }
            }
        }
        return $handler->handle($request);
    }
}
