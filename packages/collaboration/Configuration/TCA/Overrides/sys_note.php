<?php
defined('TYPO3') or die();

# add field for assigning note to a be_user, except for cli-user and admin
$fields = [
    'user' => [
        'exclude' => 1,
        'label' => 'Assign user',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => 'Choose a user',
                    'value' => 0,
                ]
            ],
            'foreign_table' => 'be_users',
            'foreign_table_where' => 'AND username NOT IN (\'_cli_\', \'admin\')',
        ],
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'sys_note',
    $fields
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_note',
    'user'
);
