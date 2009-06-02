<?php
if (!defined('TYPO3_MODE')) die ('Access denied!');

$TYPO3_CONF_VARS['BE']['AJAX']['tx_nhtvdragndrop_ajax::moveRecord'] =
	'EXT:nh_tvdragndrop/class.tx_nhtvdragndrop_ajax.php:tx_nhtvdragndrop_ajax->moveRecord';

$TYPO3_CONF_VARS['BE']['AJAX']['tx_nhtvdragndrop_ajax::unlinkRecord'] =
	'EXT:nh_tvdragndrop/class.tx_nhtvdragndrop_ajax.php:tx_nhtvdragndrop_ajax->unlinkRecord';

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/index.php'] =
	t3lib_extMgm::extPath('nh_tvdragndrop') . 'class.ux_tx_templavoila_module1.php';
?>