<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'User>Task Center',
    'description' => 'The Task Center provides the framework into which other extensions hook, for example, the sys_action extension.',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'Friends of TYPO3',
    'author_email' => 'friendsof@typo3.org',
    'author_company' => '',
    'version' => '11.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.23-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'sys_action' => '11.0.0',
        ],
    ],
];
