<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_ExtendServiceNavigation extends CRM_AOReports_Form_Report_ServiceNavigation {

  function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['fields']['time_diff'] = array(
      'title' => ts('Time Diff'),
      'required' => TRUE,
      'dbAlias' => "SUM(temp.timediff)",
    );

    $this->_columns['civicrm_contact']['filters']['time'] = array(
      'name' => 'time',
      'dbAlias' => "1",
      'title' => 'Waiting Time',
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'options' => [
        1 => ts('In Days'),
        2 => ts('In Hours'),
      ],
    );
  }

  function from() {
    $tableName = E::getSNPActivityAverageTime();
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      INNER JOIN {$tableName} temp ON temp.parent_id = {$this->_aliases['civicrm_contact']}.id
      INNER JOIN civicrm_value_donation_cust_2 lang ON lang.entity_id = temp.parent_id AND lang.language_10 IS NOT NULL
    ";
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (in_array($fieldName, ['activity_type', 'time'])) {
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
    $originalSQL = $this->buildQuery(TRUE);
    $newRows = [];
    $defaultYear = '';

    $regions = CRM_Core_OptionGroup::values('service_region_20190320122604');
    foreach ($regions as $value => $name) {
      $title =  ts('SNPNFS %1 Count of unique parents/caregiver contacts who received SNP services', [1 => $name]);
      if ($this->_params['time_value'] == 2) {
        $title = ts('Average number of time families waited to be connected for SNP services in %1 region', [1 => $name]);
      }
      elseif ($this->_params['time_value'] == 1) {
        $title = ts('Average number of days families waited to be connected for SNP services in %1 region', [1 => $name]);
      }

      $newRows[$value] = [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => $title,
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
          $newRows[$key]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_total'] == 0 ? 0 : round(($row['civicrm_contact_time_diff'] / $row['civicrm_contact_total']));
          if ($this->_params['time_value'] == 2) {
            $newRows[$key]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] *= 24;
          }
        }
      }
    }

    foreach ($newRows as $key => $row) {
      $newRows[$key]["civicrm_contact_total_count"] = $newRows[$key]["civicrm_contact_q1"] + $newRows[$key]["civicrm_contact_q2"] + $newRows[$key]["civicrm_contact_q3"] + $newRows[$key]["civicrm_contact_q4"];
    }

    unset($this->_columnHeaders["civicrm_contact_total"]);
    unset($this->_columnHeaders["civicrm_contact_quarter"]);
    unset($this->_columnHeaders["civicrm_contact_time_diff"]);

    $rows = $newRows;
  }

}
