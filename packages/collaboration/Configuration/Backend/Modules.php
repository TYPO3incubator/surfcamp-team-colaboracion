<?php

use TYPO3Incubator\Collaboration\Backend\Controller\ModuleController;

return [
    'sse_test' => [
        'position' => ['after' => 'web_FormFormbuilder'],
        'access' => 'user',
        'path' => '/module/web/example',
        'labels' => 'SSE Test',
        'extensionName' => 'Collaboration',
        'iconIdentifier' => 'tx_examples-backend-module',
        'controllerActions' => [
            ModuleController::class => ['event'],
        ],
    ],
];
