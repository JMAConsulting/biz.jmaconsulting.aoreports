<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_ExtendServiceNavigation extends CRM_AOReports_Form_Report_ServiceNavigation {

  function __construct() {
    parent::__construct();
    unset($this->_columns['civicrm_contact']['filters']['language_10']);
    $this->_columns['civicrm_contact']['fields']['time_diff'] = array(
      'title' => ts('Time Diff'),
      'required' => TRUE,
      'dbAlias' => "SUM(temp.timediff)",
    );

    $this->_columns['civicrm_contact']['fields']['q1']['title'] = ts('Q1');
    $this->_columns['civicrm_contact']['fields']['q2']['title'] = ts('Q2');
    $this->_columns['civicrm_contact']['fields']['q3']['title'] = ts('Q3');
    $this->_columns['civicrm_contact']['fields']['q4']['title'] = ts('Q4');
    $this->_columns['civicrm_contact']['fields']['total_count']['title'] = ts('YTD');
  }

  function from() {
    $tableName = E::getSNPActivityAverageTime($this, $this->_params['activity_date_time_value']);
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      INNER JOIN {$tableName} temp ON temp.parent_id = {$this->_aliases['civicrm_contact']}.id
    ";
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (in_array($fieldName, ['activity_type', 'time', 'activity_date_time'])) {
            continue;
          }
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $this->_dateClause = $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
  }

  function alterDisplay(&$rows) {
    $newRows = [];
    $defaultYear = '';

    $regions = CRM_Core_OptionGroup::values('service_region_20190320122604');
    foreach ($regions as $value => $name) {
      $newRows[$value] = [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => $name,
        'civicrm_contact_year' => '',
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
        'civicrm_contact_total_count' => 0,
      ];
    }

    foreach ($newRows as $key => $row1) {
      foreach ($rows as &$row) {
        if (strstr($row['civicrm_contact_family_count'], $key)) {
          $newRows[$key]['civicrm_contact_quarter'] = $row['civicrm_contact_quarter'];
          $newRows[$key]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_total'] == 0 ? 0 : round(($row['civicrm_contact_time_diff'] / $row['civicrm_contact_total']), 2);
        }
      }
    }

    $sql = "{$this->_select} {$this->_from} {$this->_where} GROUP BY temp.region";
    $this->addToDeveloperTab($sql);
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!in_array($dao->civicrm_contact_family_count, array_keys($newRows))) {
        continue;
      }
      $newRows[$dao->civicrm_contact_family_count]["civicrm_contact_total_count"] = $dao->civicrm_contact_time_diff;
      if ($dao->civicrm_contact_total > 0) {
        $newRows[$dao->civicrm_contact_family_count]["civicrm_contact_total_count"] = round(($dao->civicrm_contact_time_diff / $dao->civicrm_contact_total) , 2);
      }
    }

    unset($this->_columnHeaders["civicrm_contact_total"]);
    unset($this->_columnHeaders["civicrm_contact_quarter"]);
    unset($this->_columnHeaders["civicrm_contact_time_diff"]);

    $rows = $newRows;
  }

}
