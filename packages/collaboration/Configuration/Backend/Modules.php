<?php

use TYPO3Incubator\Collaboration\Backend\Controller\ModuleController;

return [
    'sse_test' => [
        'parent' => 'content',
        'position' => ['after' => 'web_FormFormbuilder'],
        'access' => 'user',
        'path' => '/module/web/example',
        'labels' => 'SSE Test',
        'extensionName' => 'Collaboration',
        'iconIdentifier' => 'tx_examples-backend-module',
        'routes' => [
            '_default' => [
                'target' => ModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
