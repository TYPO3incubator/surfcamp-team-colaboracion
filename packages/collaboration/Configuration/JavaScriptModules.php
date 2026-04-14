<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'imports' => [
        '@typo3/collaboration/' => 'EXT:collaboration/Resources/Public/JavaScript/',
        '@collaboration/event-stream/' => 'EXT:collaboration/Resources/Public/JavaScript/',
    ],
];
