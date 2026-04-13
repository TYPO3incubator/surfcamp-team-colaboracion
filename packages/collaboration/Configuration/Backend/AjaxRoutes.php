<?php

use TYPO3Incubator\Collaboration\Backend\Controller\ModuleController;

return [
    'collaboration_example' => [
        'path' => '/collaboration/sse-example',
        'target' => ModuleController::class . '::handleRequest',
    ],
];
