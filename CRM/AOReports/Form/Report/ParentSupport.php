<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_ParentSupport extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $addPaging = FALSE;
  protected $_optimisedForOnlyFullGroupBy = FALSE;
  protected $_add2groupSupported = FALSE;

  //protected $_customGroupExtends = array('Activity');
  protected $_customGroupGroupBy = FALSE;
  protected $_customFieldID = 332;
  protected $_customTableName;
  protected $_columnName;
  protected $_optionValues;
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
        ),
        'filters' => array(
          'activity_date_time' => array(
            'default' => 'this.month',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ),
          'new_child' => array(
            'title' => ts('Child contact is new?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ),
        ),
      ),
    );
    parent::__construct();

    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => $this->_customFieldID]);
    $this->_columnName = $customField['column_name'];
    $this->_customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);
    $optionValues = civicrm_api3('OptionValue', 'get', ['option_group_id' => $customField['option_group_id']])['values'];
    $this->_columns = array_merge($this->_columns, array(
      $this->_customTableName => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          $customField['name'] => [
            'extends' => 'Activity',
            'name' => $customField['name'],
            'required' => TRUE,
            'title' => ts('Question'),
            'dbAlias' => "'" . $customField['label'] . "'",
          ],
        ),
      ),
    ));
    $this->_optionValues = [];
    foreach ($optionValues as $value) {
     $this->_optionValues[$value['name']] = $value['value'];
      $this->_columns[$this->_customTableName]['fields'][$value['name']] = [
        'extends' => 'Activity',
        'name' => $value['name'],
        'title' => $value['label'],
        'dbAlias' => "COUNT({$value['name']}.entity_id)",
      ];
    }
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
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $this->_from = " FROM civicrm_activity {$this->_aliases['civicrm_activity']}";
    foreach ($this->_optionValues as $name => $value) {
      $this->_from .= " LEFT JOIN $this->_customTableName $name ON $name.entity_id = {$this->_aliases['civicrm_activity']}.id AND $name.$this->_columnName LIKE '%$value%' ";
    }
    $this->_from .= " LEFT JOIN civicrm_activity_contact ac ON ac.activity_id = {$this->_aliases['civicrm_activity']}.id AND record_type_id = $targetID ";
  }

  function where() {
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
            if ($fieldName == 'new_child' && $this->_params['new_child_value'] != NULL) {
              $relative = CRM_Utils_Array::value("activity_date_time_relative", $this->_params);
              $from     = CRM_Utils_Array::value("activity_date_time_from", $this->_params);
              $to       = CRM_Utils_Array::value("activity_date_time_to", $this->_params);
              list($from, $to) = $this->getFromTo($relative, $from, $to, $fromTime, $toTime);

              $op = ($this->_params['new_child_value'] != 1) ? 'NOT IN' : 'IN';
              $clause = " ac.contact_id $op ( SELECT new_child_id FROM " . E::getNewChildContactTableName($from, $to) . " ) ";
              continue;
            }
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

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
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

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
