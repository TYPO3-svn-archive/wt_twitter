<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'List',
	array(
		'Twitter' => 'list'
	),
	array( // don't cache some actions
		'Twitter' => ''
	)
);
?>