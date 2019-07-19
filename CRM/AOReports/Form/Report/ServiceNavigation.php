<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_ServiceNavigation extends CRM_AOReports_Form_Report_FamiliesServed {

  function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['filters']['language_10'] = array(
      'name' => 'language_10',
      'dbAlias' => "lang.language_10",
      'title' => 'Language',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'options' => CRM_Core_OptionGroup::values('language_20180621140924'),
    );

    $this->_columns['civicrm_contact']['filters']['status_id'] = array(
      'name' => 'status_id',
      'dbAlias' => "temp.status_id",
      'title' => 'Service Navigation Provision Activity Status',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => CRM_Core_PseudoConstant::activityStatus(),
    );
    $this->_columns['civicrm_contact']['filters']['activity_type'] = array(
      'name' => 'activity_type',
      'dbAlias' => "1",
      'title' => 'Activity Type',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'options' => [
        CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Service Navigation Provision') => ts('Service Navigation Provision'),
        CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Service Navigation Request') => ts('Service Navigation Request'),
      ],
    );
    $this->_columns['civicrm_contact']['fields']['family_count'] = array(
      'title' => ts('Region'),
      'required' => TRUE,
      'dbAlias' => "temp.region",
    );
    $this->_columns['civicrm_contact']['fields']['total_count'] = array(
      'title' => ts('YTD 2019'),
      'required' => TRUE,
      'dbAlias' => "0",
    );
    $this->_columns['civicrm_contact']['fields']['q1']['title'] = ts('Q1 2019');
    $this->_columns['civicrm_contact']['fields']['q2']['title'] = ts('Q2 2019');
    $this->_columns['civicrm_contact']['fields']['q3']['title'] = ts('Q3 2019');
    $this->_columns['civicrm_contact']['fields']['q4']['title'] = ts('Q4 2019');
  }

  function from() {
    $tableName = E::getSNPActivityTableName($this->_params['activity_type_value'], $this);
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
          if ($fieldName == 'activity_type') {
            continue;
          }
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $this->_dateClause = $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          elseif ($fieldName == 'language_10' && !empty(CRM_Utils_Array::value("{$fieldName}_value", $this->_params))) {
            $clause = "lang.language_10  LIKE '%" .  CRM_Utils_Array::value("{$fieldName}_value", $this->_params) . "%'";
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
    $this->_groupBy = "GROUP BY temp.region, QUARTER(temp.dof)";
  }

  function alterDisplay(&$rows) {
    $originalSQL = $this->buildQuery(TRUE);
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
