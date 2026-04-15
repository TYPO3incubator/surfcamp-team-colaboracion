<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Hook;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
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

    public function postProcessClearCache(): void
    {
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
}
