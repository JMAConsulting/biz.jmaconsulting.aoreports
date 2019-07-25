<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_FamiliesServedByRegion extends CRM_AOReports_Form_Report_FamiliesServed {

  function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['fields']['family_count']['title'] = 'Region';
    $this->_columns['civicrm_contact']['fields']['region'] = array(
      'name' => 'region',
      'required' => TRUE,
      'dbAlias' => "temp.region",
      'title' => 'Region',
    );
    $this->_columns['civicrm_contact']['filters']['region'] = array(
      'name' => 'region',
      'dbAlias' => "temp.region",
      'title' => 'Region',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => ['' => '- select -'] + CRM_Core_OptionGroup::values('chapter_20180619153429'),
    );
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          if ($fieldName == 'region') {
            continue;
          }
          $clause = NULL;
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

  function from() {
    $tableName = E::getNewChildContactTableNameByRegion($this);
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

    $regions = CRM_Core_OptionGroup::values('service_region_20190320122604');
    if (!empty($this->_params['region_value'])) {
      foreach	($regions as $k => $v) {
        if (!in_array($k, $this->_params['region_value'])) {
          unset($regions[$k]);
        }
      }
    }
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

    foreach ($newRows as $key => $row) {
      foreach ($rows as &$row) {
        if (strstr($row['civicrm_contact_family_count'], $key)) {
          $newRows[$key]['civicrm_contact_quarter'] = $row['civicrm_contact_quarter'];
          $newRows[$key]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_total'] ?: 0;
        }
      }
    }

    foreach ($newRows as $key => $row) {
      $newRows[$key]["civicrm_contact_total_count"] = $newRows[$key]["civicrm_contact_q1"] + $newRows[$key]["civicrm_contact_q2"] + $newRows[$key]["civicrm_contact_q3"] + $newRows[$key]["civicrm_contact_q4"];
    }

    unset($this->_columnHeaders["civicrm_contact_total"]);
    unset($this->_columnHeaders["civicrm_contact_quarter"]);
    unset($this->_columnHeaders["civicrm_contact_region"]);

    $rows = $newRows;
  }

}
