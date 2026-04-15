<?php

use TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController;
use TYPO3Incubator\Collaboration\Backend\Controller\StreamController;

return [
    'collaboration_example' => [
        'path' => '/collaboration/sse-example',
        'target' => StreamController::class . '::handleRequest',
    ],
    'collaboration_contextual_history' => [
        'path' => '/collaboration/contextual-history',
        'target' => ElementHistoryController::class . '::mainAction',
    ],
];
