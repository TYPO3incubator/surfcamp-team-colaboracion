<?php

use TYPO3Incubator\Collaboration\Backend\Controller\StreamController;

return [
    'collaboration_example' => [
        'path' => '/collaboration/sse-example',
        'target' => StreamController::class . '::handleRequest',
    ],
];
