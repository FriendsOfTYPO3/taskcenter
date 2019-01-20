<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'User>Task Center',
    'description' => 'The Task Center is the framework for a host of other extensions.',
    'category' => 'module',
    'state' => 'stable',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Friends of TYPO3',
    'author_email' => 'friendsof@typo3.org',
    'author_company' => '',
    'version' => '10.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-10.9.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'sys_action' => '10.0.0',
        ],
    ],
];
