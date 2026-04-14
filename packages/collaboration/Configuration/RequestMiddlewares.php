<?php

use TYPO3Incubator\Collaboration\Middleware\ContextualEditEventMiddleware;

return [
    'backend' => [
        'typo3incubator/collaboration/contextual-edit-event' => [
            'target' => ContextualEditEventMiddleware::class,
        ],
    ],
];
