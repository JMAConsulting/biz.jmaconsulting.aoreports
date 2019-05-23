<?php

require_once 'aoreports.civix.php';
use CRM_Aoreports_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function aoreports_civicrm_config(&$config) {
  _aoreports_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function aoreports_civicrm_xmlMenu(&$files) {
  _aoreports_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function aoreports_civicrm_install() {
  _aoreports_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function aoreports_civicrm_postInstall() {
  _aoreports_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function aoreports_civicrm_uninstall() {
  _aoreports_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function aoreports_civicrm_enable() {
  _aoreports_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function aoreports_civicrm_disable() {
  _aoreports_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function aoreports_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _aoreports_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function aoreports_civicrm_managed(&$entities) {
  _aoreports_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function aoreports_civicrm_caseTypes(&$caseTypes) {
  _aoreports_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function aoreports_civicrm_angularModules(&$angularModules) {
  _aoreports_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function aoreports_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _aoreports_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function aoreports_civicrm_entityTypes(&$entityTypes) {
  _aoreports_civix_civicrm_entityTypes($entityTypes);
}

function aoreports_civicrm_alterReportVar($type, &$columns, &$form) {
 if ('CRM_AOReports_Form_Report_ExtendedActivity' == get_class($form) && $type == 'sql' && is_string($columns) && strstr($_GET['q'], 'instance/52')) {
  $contactID = CRM_Core_Session::singleton()->get('userID');
    $match = "AND contact_id = {$contactID}";
    $replace = "AND contact_id = 108716";
    $columns = str_replace($match, $replace, $columns);
  }
  if ('CRM_Report_Form_Contribute_Detail' == get_class($form) && $type == 'columns') {
    $columns['civicrm_financial_trxn']['fields']['financial_trxn_id'] = [
      'name' => 'id',
      'no_display' => TRUE,
      'required' => TRUE,
    ];
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function aoreports_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function aoreports_civicrm_navigationMenu(&$menu) {
  _aoreports_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _aoreports_civix_navigationMenu($menu);
} // */
