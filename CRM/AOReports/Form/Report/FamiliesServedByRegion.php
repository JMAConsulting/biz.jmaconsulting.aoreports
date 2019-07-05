<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_FamiliesServedByRegion extends CRM_AOReports_Form_Report_FamiliesServed {

  function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['filters']['region'] = array(
      'name' => 'region',
      'dbAlias' => "temp.region",
      'title' => 'Region',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => ['' => '- select -'] + CRM_Core_OptionGroup::values('chapter_20180619153429'),
    );
  }

  function from() {
    $tableName = E::getNewChildContactTableNameByRegion();
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      INNER JOIN {$tableName} temp ON temp.new_child_id = {$this->_aliases['civicrm_contact']}.id
    ";
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY temp.region, QUARTER(temp.dof)";
  }

  function alterDisplay(&$rows) {
    $originalSQL = $this->buildQuery(TRUE);
    $newRows = [];
    $defaultYear = '';

    $regions = CRM_Core_OptionGroup::values('chapter_20180619153429');
    foreach ($regions as $value => $name) {
      $newRows[$value] = [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => ts('Number of Families Served on %1 Region', [1 => $name]),
        'civicrm_contact_year' => '',
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
        'civicrm_contact_total_count' => 0,
      ];
    }

    foreach ($newRows as $key => $row) {
      foreach ($rows as &$row) {
        if (strstr($row['civicrm_contact_family_count'], $key)) {
          $newRows[$key]['civicrm_contact_quarter'] = $row['civicrm_contact_quarter'];
          $newRows[$key]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_total'];
        }
      }
    }

    foreach ($newRows as $key => $row) {
      $newRows[$key]["civicrm_contact_total_count"] = $newRows[$key]["civicrm_contact_q1"] + $newRows[$key]["civicrm_contact_q2"] + $newRows[$key]["civicrm_contact_q3"] + $newRows[$key]["civicrm_contact_q4"];
    }

    unset($this->_columnHeaders["civicrm_contact_total"]);
    unset($this->_columnHeaders["civicrm_contact_quarter"]);

    $rows = $newRows;
  }

}
