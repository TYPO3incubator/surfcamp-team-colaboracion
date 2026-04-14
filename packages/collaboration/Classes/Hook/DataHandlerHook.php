<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Hook;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3Incubator\Collaboration\Domain\Model\Dto\EventMessageDto;
use TYPO3Incubator\Collaboration\Service\EventMessageService;

#[Autoconfigure(public: true)]
class DataHandlerHook
{
    public function __construct(
        private readonly EventMessageService $eventMessageService
    ) {}

    public function postProcessClearCache(): void
    {
        $message = new EventMessageDto(
            $GLOBALS['EXEC_TIME'],
            $GLOBALS['BE_USER']->user['uid'],
            $GLOBALS['BE_USER']->getUsername(),
            'clearCacheEvent',
            json_encode(
                [
                    'data' => 'Cache has cleared. You might want to reload the Backend.'
                ],
                JSON_THROW_ON_ERROR
            ),
        );
        $this->eventMessageService->addMessage($message);
    }
}
