<?php

return [
    'user_task' => [
        'parent' => 'user',
        'position' => 'top',
        'access' => 'user',
        'path' => '/module/user/task',
        'iconIdentifier' => 'module-taskcenter',
        'labels' => 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => 'TYPO3\\CMS\\Taskcenter\\Controller\\TaskModuleController::mainAction',
            ],
        ],
    ],
];
