<?php
if (!defined('TYPO3_MODE')) die ('Access denied!');

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/index.php'] =
	t3lib_extMgm::extPath('nh_tvdragndrop') . 'class.ux_tx_templavoila_module1.php';
?>