<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

Tx_Extbase_Utility_Extension::registerPlugin(
	$_EXTKEY,
	'List',
	'wt_twitter - Frontend Tweets'
);

$TCA['tt_content']['types']['list']['subtypes_excludelist']['wttwitter_list'] = 'layout,select_key,recursive,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist']['wttwitter_list'] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue('wttwitter_list', 'FILE:EXT:wt_twitter/Configuration/Flexforms/flexform.xml');

if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_wttwitter_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY) . 'Resources/Private/BackEnd/TwitterWizicon.php';
}

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/Main/', 'Main TypoScript');
t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/NewsTicker/', 'Newsticker');
?>