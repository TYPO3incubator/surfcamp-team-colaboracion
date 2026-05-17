<?php

use TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController;
use TYPO3Incubator\Collaboration\Backend\Controller\AjaxController;
use TYPO3Incubator\Collaboration\Backend\Controller\StreamController;

return [
    'collaboration_stream' => [
        'path' => '/collaboration/stream',
        'target' => StreamController::class . '::handleRequest',
    ],
    'collaboration_contextual_history' => [
        'path' => '/collaboration/contextual-history',
        'target' => ElementHistoryController::class . '::mainAction',
    ],
    'collaboration_focus' => [
        'path' => '/collaboration/focus',
        'target' => AjaxController::class . '::focusAction',
    ],
];
