<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_ExtendServiceNavigationByDateRange extends CRM_AOReports_Form_Report_ServiceNavigation {
  function __construct() {
    parent::__construct();
    unset($this->_columns['civicrm_contact']['filters']['language_10']);
    $this->_columns['civicrm_contact']['fields']['time_diff'] = array(
      'title' => ts('Time Diff'),
      'required' => TRUE,
      'dbAlias' => "SUM(temp.timediff)",
    );
    
    unset($this->_columns['civicrm_contact']['fields']['q1'],
     $this->_columns['civicrm_contact']['fields']['q2'],
     $this->_columns['civicrm_contact']['fields']['q3'],
     $this->_columns['civicrm_contact']['fields']['q4'],
     $this->_columns['civicrm_contact']['filters']['activity_date_time']
   );

    $this->_columns['civicrm_contact']['fields']['total_count']['title'] = ts('Total');
  }

  function from() {
    $relative = CRM_Utils_Array::value("dof_relative", $this->_params);
    $from     = CRM_Utils_Array::value("dof_from", $this->_params);
    $to       = CRM_Utils_Array::value("dof_to", $this->_params);
    $dateClause = $this->dateClause('a.activity_date_time', $relative, $from, $to, CRM_Utils_Type::T_DATE);
    
    $tableName = E::getSNPActivityAverageTime($this, NULL, $dateClause);
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
  
  function groupBy() {
    $this->_groupBy = "GROUP BY COALESCE(temp.region, '')";
  }

  function alterDisplay(&$rows) {
    $newRows = [];
    $defaultYear = '';
    $total = $timeDiff = 0;

    $regions = CRM_Core_OptionGroup::values('service_region_20190320122604');
    foreach ($regions as $value => $name) {
      $newRows[$value] = [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => $name,
        'civicrm_contact_year' => '',
        'civicrm_contact_total_count' => 0,
      ];
    }

    foreach ($newRows as $key => $row1) {
      foreach ($rows as &$row) {
        if (strstr($row['civicrm_contact_family_count'], $key)) {
          $newRows[$key]['civicrm_contact_total_count'] = $row['civicrm_contact_total'] == 0 ? 0 : round(($row['civicrm_contact_time_diff'] / $row['civicrm_contact_total']), 2);
          $total += $row['civicrm_contact_total'];
          $timeDiff += $row['civicrm_contact_time_diff'];
        }
      }
    }

    $newRows['total'] = [
      'civicrm_contact_total' => 0,
      'civicrm_contact_family_count' => ts('Grand Total'),
      'civicrm_contact_year' => '',
      'civicrm_contact_total_count' => round(($timeDiff / $total), 2),
    ];

    unset($this->_columnHeaders["civicrm_contact_total"]);
    unset($this->_columnHeaders["civicrm_contact_quarter"]);
    unset($this->_columnHeaders["civicrm_contact_time_diff"]);

    $rows = $newRows;
  }

}
