<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_ParentFeedback extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $addPaging = FALSE;
  protected $_optimisedForOnlyFullGroupBy = FALSE;
  protected $_add2groupSupported = FALSE;

  protected $_customFieldGroupIDs;
  protected $_customSQLs;
  protected $_customFieldOptionLabels;
  protected $_maximumCountSupported = 10;
  function __construct() {
    $condition = " AND ( v.component_id IS NULL )";
    $this->activityTypes = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, $condition);

    $this->_columns = array(
      'civicrm_activity' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'title' => ts('Activity ID'),
            'required' => TRUE,
          ),
          'label' => array(
            'name' => 'label',
            'required' => TRUE,
            'title' => ts('Questions'),
            'dbAlias' => "'Questions'",
          ),
        ),
        'filters' => array(
          'activity_date_time' => array(
            'default' => 'this.month',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'default' => CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_type_id', 'Feedback'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ),
        ),
      ),
    );
    for ($i = 0; $i < $this->_maximumCountSupported; $i++) {
      $this->_columns['civicrm_activity']['fields']["$i"] = array(
        'name' => "$i",
        'required' => TRUE,
        'title' => ts('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'),
        'dbAlias' => "0",
      );
    }
    parent::__construct();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            if (CRM_Utils_Array::value('no_display', $field)) {
              continue;
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = " FROM civicrm_activity {$this->_aliases['civicrm_activity']}";
  }

  function where() {
    $activityTypeIDs = CRM_Utils_Array::value('activity_type_id_value', $this->_params) ?: [0];
    $this->_customFieldGroupIDs = array_keys(civicrm_api3('CustomGroup', 'get', ['extends' => "Activity", 'extends_entity_column_value' => ['IN' => $activityTypeIDs]])['values']);

    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
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
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_activity']}.activity_type_id ";
  }

  function postProcess() {
    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
    CRM_Core_DAO::reenableFullGroupByMode();
  }

  public function buildQuery($applyLimit = TRUE) {
    $this->select();
    $this->from();
    $this->customDataFrom();
    $this->buildPermissionClause();
    $this->where();
    $this->groupBy();
    $this->orderBy();

    $sqls = [];
    foreach ($this->_customFieldGroupIDs as $key => $id) {
      $customGroup = civicrm_api3('CustomGroup', 'getsingle', ['id' => $id]);
      $customFields = civicrm_api3('CustomField', 'get', ['custom_group_id' => $id, 'is_active' => TRUE, 'html_type' => ["IN" => ["CheckBox", "Radio", "Multi-Select", "Select"]]])['values'];
      if (empty($customFields)) {
        unset($this->_customFieldGroupIDs[$key]);
        continue;
      }
      foreach ($customFields as $cfID => $customField) {
        $dataType = $customField['data_type'];
        if ($dataType == 'Boolean') {
          $optionValues = [
            1 => [
              'name' => 'yes',
              'label' => ts('Yes'),
              'value' => 1,
            ],
            0 => [
              'name' => 'no',
              'label' => ts('No'),
              'value' => 0,
            ],
          ];
        }
        else {
          $optionValues = civicrm_api3('OptionValue', 'get', ['option_group_id' => $customField['option_group_id']])['values'];
        }
        if (count($optionValues) > $this->_maximumCountSupported) {
          continue;
        }
        $from = $this->_from;
        $templateAlias = [];
        $this->_customFieldOptionLabels[$cfID] = [
          'civicrm_activity_id' => '',
          'civicrm_activity_label' => '',
        ];
        for ($i = 0; $i < $this->_maximumCountSupported; $i++) {
          $templateAlias[] = "civicrm_activity_$i";
          $this->_customFieldOptionLabels[$cfID]["civicrm_activity_$i"] = '';
        }
        $selects = [sprintf("'%s' as civicrm_activity_label", $customField['label'])];
        $count = 0;
        foreach ($optionValues as $optionValue) {
          $name = $optionValue['name'];
          $value = $dataType == 'String' ? "'{$optionValue['value']}'" : $optionValue['value'];
          $this->_customFieldOptionLabels[$cfID][$templateAlias[$count]] = sprintf('<b>%s</b>', $optionValue['label']);
          $selects[] = "COUNT($name.entity_id) as " . $templateAlias[$count];
          $from .= " LEFT JOIN {$customGroup['table_name']} $name ON $name.entity_id = {$this->_aliases['civicrm_activity']}.id AND $name.{$customField['column_name']} = $value ";
          $count++;
        }
        $sqls[$cfID] = "SELECT " . implode(', ', $selects) . $from . $this->_where;
      }
    }
    $this->_customSQLs = $sqls;

    foreach ($this->unselectedOrderByColumns() as $alias => $field) {
      $clause = $this->getSelectClauseWithGroupConcatIfNotGroupedBy($field['table_name'], $field['name'], $field);
      if (!$clause) {
        $clause = "{$field['dbAlias']} as {$alias}";
      }
      $this->_select .= ", $clause ";
    }

    if ($applyLimit && empty($this->_params['charts'])) {
      $this->limit();
    }
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
    $this->addToDeveloperTab($sql);
    return $sql;
  }


  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $newRows = [];
    $templateRows = [
      'civicrm_activity_id' => 0,
      'civicrm_activity_label' => NULL,
    ];
    for ($i = 0; $i < $this->_maximumCountSupported; $i++) {
      $templateRows['civicrm_activity_' . $i] = NULL;
    }
    foreach ($this->_customSQLs as $id => $sql) {
      $row = $templateRows;
      $dao = CRM_Core_DAO::executeQuery($sql);
      $newRows[] = $this->_customFieldOptionLabels[$id];
      while($dao->fetch()) {
        foreach ($templateRows as $key => $dontCare) {
          if ($key != 'civicrm_activity_id' && property_exists($dao, $key)) {
            $row[$key] = $dao->$key;
          }
        }
      }
      $newRows[] = $row;
    }

    $rows = $newRows;
  }

  public function buildInstanceAndButtons() {
    parent::buildInstanceAndButtons();
    CRM_Core_Resources::singleton()->addScript(
    "CRM.$(function($) {
      $('.report-layout thead').hide();
    });"
  );
  }

}
