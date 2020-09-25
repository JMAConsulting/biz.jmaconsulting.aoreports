<?php

require_once 'aoreports.civix.php';
use CRM_AOReports_ExtensionUtil as E;

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
  if ('CRM_Report_Form_Activity' == get_class($form) && $type == 'rows' && strstr($_GET['q'], 'instance/47')) {
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $form, FALSE, 'report');
    foreach ($columns as $rowNum => $row) {
      if (array_key_exists('civicrm_activity_activity_type_id', $row)) {
        if (!empty($columns[$rowNum]['civicrm_contact_contact_target_id'])) {
          $targets = explode(';', $columns[$rowNum]['civicrm_contact_contact_target_id']);
          $cid = $targets[0];
        }
        else {
          $cid = $columns[$rowNum]['civicrm_contact_contact_source_id'];
        }

        $actActionLinks = CRM_Activity_Selector_Activity::actionLinks(array_search($row['civicrm_activity_activity_type_id'], $activityType),
          CRM_Utils_Array::value('civicrm_activity_source_record_id', $columns[$rowNum]),
          FALSE,
          $columns[$rowNum]['civicrm_activity_id']
        );

        $actLinkValues = [
          'id' => $columns[$rowNum]['civicrm_activity_id'],
          'cid' => $cid,
          'cxt' => $context,
        ];
        $actUrl = CRM_Utils_System::url($actActionLinks[CRM_Core_Action::VIEW]['url'],
          CRM_Core_Action::replace($actActionLinks[CRM_Core_Action::VIEW]['qs'], $actLinkValues), TRUE
        );
        $columns[$rowNum]['civicrm_activity_activity_type_id_link'] = $actUrl;
      }
    }
  }
  if ('CRM_Report_Form_Contribute_Bookkeeping' == get_class($form) && $type == 'columns') {
    $columns['civicrm_batch']['filters']['batch_id'] = [
      'name' => 'id',
      'dbAlias' => 'batch.id',
      'title' => ts('Batch title'),
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => CRM_Contribute_PseudoConstant::batch(),
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
