<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Hook;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Incubator\Collaboration\Domain\Model\Dto\EventMessageDto;
use TYPO3Incubator\Collaboration\Service\EventMessageService;

#[Autoconfigure(public: true)]
class DataHandlerHook
{
    public function __construct(
        private readonly EventMessageService $eventMessageService,
        private readonly LocalizationUtility $localizationUtility
    ) {}

    public function postProcessClearCache(array $params): void
    {
        // only when all caches where cleared manually
        if (
            !isset($params['cacheCmd'])
            || $params['cacheCmd'] !== 'all'
        ) {
            return;
        }

        $message = new EventMessageDto(
            $GLOBALS['EXEC_TIME'],
            $GLOBALS['BE_USER']->user['uid'],
            $GLOBALS['BE_USER']->getUsername(),
            'clearCacheEvent',
            json_encode(
                [
                    'title' => $this->localizationUtility->translate(
                        'sse.cache_cleared.title',
                        'collaboration'
                    ),
                    'message' => $this->localizationUtility->translate(
                        'sse.cache_cleared.message',
                        'collaboration'
                    ),
                    'actionLabel' => $this->localizationUtility->translate(
                        'sse.cache_cleared.action_label',
                        'collaboration'
                    ),
                ],
                JSON_THROW_ON_ERROR
            ),
        );
        $this->eventMessageService->addMessage($message);
    }

    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        string|int $id,
        array &$fieldArray,
        DataHandler &$dataHandler
    ): void {
        // set assigned_name for sys_note record
        if (
            $table === 'sys_note'
            && isset($fieldArray['assigned_id'])
        ) {
            // if assigned_id is empty, clear the username as well
            if ($fieldArray['assigned_id'] === '') {
                $fieldArray['assigned_name'] = '';
                return;
            }
            $assignedUser = BackendUtility::getRecord('be_users', (int)$fieldArray['assigned_id']);
            $fieldArray['assigned_name'] = $assignedUser['username'];
        }
    }
}
