<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_ServiceNavigation extends CRM_AOReports_Form_Report_FamiliesServed {

  function __construct() {
    parent::__construct();
    $sql = "
      SELECT DISTINCT cc.id, cc.display_name
        FROM civicrm_activity_contact cac INNER JOIN civicrm_contact cc ON cc.id = cac.contact_id
        AND cac.record_type_id = 1
        INNER JOIN civicrm_activity ca ON ca.id = cac.activity_id AND ca.activity_type_id IN (136,137) ORDER BY cc.display_name ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = [];
    while($dao->fetch()) {
      $contacts[$dao->id] = $dao->display_name;
    }
    // Unset certain service navigators
    $unsetIds = [118019, 108716, 108720, 404318];
    foreach ($contacts as $id => $contact) {
      if (in_array($id, $unsetIds)) {
        unset($contacts[$id]);
      }
    }

    $this->_columns['civicrm_contact']['filters']['language_10'] = array(
      'name' => 'language_10',
      'dbAlias' => "lang.language_10",
      'title' => 'Language',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'options' => ['' => '-any-'] + CRM_Core_OptionGroup::values('language_20180621140924'),
    );

    $this->_columns['civicrm_contact']['filters']['activity_type'] = array(
      'name' => 'activity_type',
      'dbAlias' => "1",
      'title' => 'Activity Type',
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => [
        CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Service Navigation Provision') => ts('Service Navigation Provision'),
        CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Service Navigation Request') => ts('Service Navigation Request'),
        70 => ts('Individual Consultation'),
        5 => ts('Event Registration'),
      ],
    );
    $this->_columns['civicrm_contact']['filters']['activity_date_time'] = array(
      'name' => 'activity_date_time',
      'alias' => 'dof',
      'title' => ts('Year'),
      'required' => TRUE,
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'options' => ['' => '-select-'] + array_combine(range(date('Y'), 2000), range(date('Y'), 2000)),
    );

    $this->_columns['civicrm_contact']['filters']['assignee'] = array(
      'name' => 'assignee',
      'dbAlias' => "temp.contact_id",
      'title' => 'Service Navigator',
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'options' => ['' => '-any-'] + $contacts,
    );
    $this->_columns['civicrm_contact']['filters']['status_id'] = array(
      'name' => 'status_id',
      'dbAlias' => "temp.status_id",
      'title' => ts('Activity Status'),
      'type' => CRM_Utils_Type::T_STRING,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => CRM_Core_PseudoConstant::activityStatus(),
    );

    $this->_columns['civicrm_contact']['fields']['family_count'] = array(
      'title' => ts('Region'),
      'required' => TRUE,
      'dbAlias' => "temp.region",
    );
    $this->_columns['civicrm_contact']['fields']['total_count'] = array(
      'title' => ts('YTD'),
      'required' => TRUE,
      'dbAlias' => "0",
    );

    $this->_columns['civicrm_contact']['fields']['q1']['title'] = ts('Jan-Mar');
    $this->_columns['civicrm_contact']['fields']['q2']['title'] = ts('April-Jun');
    $this->_columns['civicrm_contact']['fields']['q3']['title'] = ts('Jul-Sep');
    $this->_columns['civicrm_contact']['fields']['q4']['title'] = ts('Oct-Dec');
  }

  function from() {
    $tableName = E::getSNPActivityTableName($this->_params['activity_type_value'], $this, $this->_params['status_id_value'], $this->_params['status_id_op'], $this->_params['activity_date_time_value']);
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      INNER JOIN {$tableName} temp ON temp.parent_id = {$this->_aliases['civicrm_contact']}.id ";

    if (!empty($this->_params['language_10_value'])) {
      $this->_from .= "
      INNER JOIN civicrm_value_donation_cust_2 lang ON lang.entity_id = temp.parent_id AND lang.language_10 IS NOT NULL
      ";
    }
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($fieldName == 'activity_type' || $fieldName == 'activity_date_time') {
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
          elseif ($fieldName == 'status_id') {
            continue;
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
      if ($name == 'Unknown') {continue;}
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
    $newRows['no-region'] = [
      'civicrm_contact_total' => 1,
      'civicrm_contact_family_count' => 'Unknown',
      'civicrm_contact_year' => '',
      'civicrm_contact_quarter' => NULL,
      'civicrm_contact_q1' => 0,
      'civicrm_contact_q2' => 0,
      'civicrm_contact_q3' => 0,
      'civicrm_contact_q4' => 0,
      'civicrm_contact_total_count' => 0,
    ];

    // Process and then remove any rows where civicrm_contact_family_count is NULL adding the count onto the blank i.e. civicrm_contact_family_count = '' row.
    $emptyq1 = $emptyq2 = $emptyq3 = $emptyq4 = 0;
    foreach ($rows as $k => $v){
      if (empty($v['civicrm_contact_family_count'])) {
        $variable = 'emptyq' . $v['civicrm_contact_quarter'];
        $$variable += $v['civicrm_contact_total'];
        if ($v['civicrm_contact_family_count'] !== '') {
          unset($rows[$k]);
        }
        else {
          $rows[$k]['civicrm_contact_total'] = $$variable;
        }
      }
    }

    foreach ($newRows as $key => $row1) {
      foreach ($rows as &$row) {
        if (strstr($row['civicrm_contact_family_count'], $key)) {
          $newRows[$key]['civicrm_contact_quarter'] = $row['civicrm_contact_quarter'];
          $newRows[$key]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_total'];
        }
        elseif ($row['civicrm_contact_family_count'] == '') {
          $newRows['no-region']['civicrm_contact_quarter'] = $row['civicrm_contact_quarter'];
          $newRows['no-region']["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_total'];
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
