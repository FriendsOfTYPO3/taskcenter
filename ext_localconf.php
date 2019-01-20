<?php
defined('TYPO3_MODE') or die();

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('impexp')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']['impexp'][\TYPO3\CMS\Taskcenter\Task\ImportExportTask::class] = [
        'title' => 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_csh.xlf:.alttitle',
        'description' => 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_csh.xlf:.description',
        'icon' => 'EXT:taskcenter/Resources/Public/Images/export.gif'
    ];
}