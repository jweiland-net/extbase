<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE == 'BE') {
	// register Extbase dispatcher for modules
	$TBE_MODULES['_dispatcher'][] = 'Tx_Extbase_Core_Bootstrap';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['extbase'][] = 'tx_extbase_utility_extbaserequirementscheck';

t3lib_div::loadTCA('fe_users');
if (!isset($TCA['fe_users']['ctrl']['type'])) {
	$tempColumns = array(
		'tx_extbase_type' => array(
			'exclude' => 1,
			'label'   => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:fe_users.tx_extbase_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:fe_users.tx_extbase_type.0', '0'),
					array('LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:fe_users.tx_extbase_type.Tx_Extbase_Domain_Model_FrontendUser', 'Tx_Extbase_Domain_Model_FrontendUser')
				),
				'size' => 1,
				'maxitems' => 1,
				'default' => '0'
			)
		)
	);
	t3lib_extMgm::addTCAcolumns('fe_users', $tempColumns, 1);
	t3lib_extMgm::addToAllTCAtypes('fe_users', 'tx_extbase_type');
	$TCA['fe_users']['ctrl']['type'] = 'tx_extbase_type';
}
$TCA['fe_users']['types']['Tx_Extbase_Domain_Model_FrontendUser'] = $TCA['fe_users']['types']['0'];

t3lib_div::loadTCA('fe_groups');
if (!isset($TCA['fe_groups']['ctrl']['type'])) {
	$tempColumns = array(
		'tx_extbase_type' => array(
			'exclude' => 1,
			'label'   => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:fe_groups.tx_extbase_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:fe_groups.tx_extbase_type.0', '0'),
					array('LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:fe_groups.tx_extbase_type.Tx_Extbase_Domain_Model_FrontendUserGroup', 'Tx_Extbase_Domain_Model_FrontendUserGroup')
				),
				'size' => 1,
				'maxitems' => 1,
				'default' => '0'
			)
		)
	);
	t3lib_extMgm::addTCAcolumns('fe_groups', $tempColumns, 1);
	t3lib_extMgm::addToAllTCAtypes('fe_groups', 'tx_extbase_type');
	$TCA['fe_groups']['ctrl']['type'] = 'tx_extbase_type';
}
$TCA['fe_groups']['types']['Tx_Extbase_Domain_Model_FrontendUserGroup'] = $TCA['fe_groups']['types']['0'];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Extbase_Scheduler_Task'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:task.name',
	'description'      => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xml:task.description',
	'additionalFields' => 'Tx_Extbase_Scheduler_FieldProvider'
);

?>