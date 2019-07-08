<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_ExtendServiceNavigationByRegion extends CRM_AOReports_Form_Report_FamiliesServedByRegion {

  function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['filters']['status_id'] = array(
      'name' => 'activity_status_id',
      'dbAlias' => "temp.status_id",
      'title' => 'Region',
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'options' => CRM_Core_PseudoConstant::activityStatus(),
    );
  }

  function from() {
    $tableName = E::getSNPActivitybyMinistryRegion();
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      INNER JOIN {$tableName} temp ON temp.parent_id = {$this->_aliases['civicrm_contact']}.id
      INNER JOIN civicrm_value_donation_cust_2 lang ON lang.entity_id = temp.parent_id AND lang.language_10 IS NOT NULL
    ";
  }

}
