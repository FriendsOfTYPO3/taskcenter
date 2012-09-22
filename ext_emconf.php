<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "taskcenter".
 *
 * Auto generated 25-10-2011 13:11
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'User>Task Center',
	'description' => 'The Task Center is the framework for a host of other extensions, see below.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '6.0.0',
	'_md5_values_when_last_written' => 'a:20:{s:16:"ext_autoload.php";s:4:"61f4";s:12:"ext_icon.gif";s:4:"7c85";s:14:"ext_tables.php";s:4:"492b";s:13:"locallang.xlf";s:4:"bdb1";s:38:"classes/class.tx_taskcenter_status.php";s:4:"ac00";s:14:"doc/manual.sxw";s:4:"6598";s:43:"interfaces/interface.tx_taskcenter_task.php";s:4:"bf3c";s:23:"res/item-background.jpg";s:4:"6749";s:21:"res/list-item-act.gif";s:4:"6fa4";s:17:"res/list-item.gif";s:4:"e82d";s:18:"res/mod_styles.css";s:4:"a07d";s:21:"res/mod_template.html";s:4:"eb07";s:15:"res/tasklist.js";s:4:"177b";s:14:"task/clear.gif";s:4:"cc11";s:13:"task/conf.php";s:4:"2c86";s:13:"task/icon.gif";s:4:"7941";s:14:"task/index.php";s:4:"4f50";s:18:"task/locallang.xlf";s:4:"356f";s:22:"task/locallang_mod.xlf";s:4:"a4aa";s:13:"task/task.gif";s:4:"5d27";}',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.0.0-0.0.0'
		),
		'conflicts' => array(),
		'suggests' => array(
			'sys_action' => '6.0.0-0.0.0'
		)
	),
	'suggests' => array('sys_action')
);
?>