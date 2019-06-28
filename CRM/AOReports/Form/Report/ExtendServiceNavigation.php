<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_ExtendServiceNavigation extends CRM_AOReports_Form_Report_ServiceNavigation {

  function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['fields']['time_diff'] = array(
      'title' => ts('Time Diff'),
      'required' => TRUE,
      'no_display' => TRUE,
      'dbAlias' => "SUM(temp.timediff)",
    );
  }

  function from() {
    $tableName = E::getSNPActivityAverageTime();
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      INNER JOIN {$tableName} temp ON temp.parent_id = {$this->_aliases['civicrm_contact']}.id
      INNER JOIN civicrm_value_donation_cust_2 lang ON lang.entity_id = temp.parent_id AND lang.language_10 IS NOT NULL
    ";
  }

}
