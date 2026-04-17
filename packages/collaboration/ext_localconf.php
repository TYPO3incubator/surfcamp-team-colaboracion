<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;
use TYPO3Incubator\Collaboration\Hook\DataHandlerHook;
use TYPO3Incubator\Collaboration\Queue\Message\SysNoteMailMessage;

defined('TYPO3') or die();

// DataHandler Hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] =
    DataHandlerHook::class . '->postProcessClearCache';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    DataHandlerHook::class;

// MessageBus Routing
foreach ([WebhookMessageInterface::class, SysNoteMailMessage::class] as $className) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][$className] = 'doctrine';
}
