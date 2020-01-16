<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_EventsThisQuarter extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;
  protected $_add2groupSupported = FALSE;
  protected $addPaging = FALSE;
  protected $_csvSupported = TRUE;

  protected $_customGroupExtends = array(
    'Event',
  );
  protected $_customGroupGroupBy = FALSE;
  protected $_eventTemplateCustomFieldID = 327;

  function __construct() {
    $this->_columns = array(
      'civicrm_event' => [
        'dao' => 'CRM_Event_DAO_Event',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'event_type_id' => array(
            'title' => ts('Event Type'),
            'no_display' => FALSE,
            'required' => TRUE,
          ),
          'event_start_date' => array(
            'title' => ts('Date'),
          ),
          'title' => array(
            'title' => ts('Name'),
          ),
          'children' => array(
            'title' => ts('Children'),
            'dbAlias' => "(SELECT ROUND(COALESCE(SUM(qty), 0), 0)
              FROM civicrm_line_item li
               INNER JOIN civicrm_participant p ON li.entity_id = p.id AND li.entity_table = 'civicrm_participant'
               INNER JOIN civicrm_participant_status_type pst ON pst.id = p.status_id AND pst.class = 'Positive'
               INNER JOIN civicrm_price_field pf ON li.price_field_id = pf.id
               WHERE p.event_id = event_civireport.id AND pf.name LIKE '%child%'
             )",
          ),
          'parents' => array(
            'title' => ts('Parents'),
            'dbAlias' => "(SELECT ROUND(COALESCE(SUM(qty), 0), 0)
              FROM civicrm_line_item li
               INNER JOIN civicrm_participant p ON li.entity_id = p.id AND li.entity_table = 'civicrm_participant'
               INNER JOIN civicrm_participant_status_type pst ON pst.id = p.status_id AND pst.class = 'Positive'
               INNER JOIN civicrm_price_field pf ON li.price_field_id = pf.id
               WHERE p.event_id = event_civireport.id AND pf.name LIKE '%parent%'
             )",
          ),
          'siblings' => array(
            'title' => ts('Siblings / Friends'),
            'dbAlias' => "(SELECT ROUND(COALESCE(SUM(qty), 0), 0)
              FROM civicrm_line_item li
               INNER JOIN civicrm_participant p ON li.entity_id = p.id AND li.entity_table = 'civicrm_participant'
               INNER JOIN civicrm_participant_status_type pst ON pst.id = p.status_id AND pst.class = 'Positive'
               INNER JOIN civicrm_price_field pf ON li.price_field_id = pf.id
               WHERE p.event_id = event_civireport.id AND pf.name LIKE '%sibling%'
             )",
          ),
          'professionals' => array(
            'title' => ts('Professionals'),
            'dbAlias' => "(SELECT ROUND(COALESCE(SUM(qty), 0), 0)
              FROM civicrm_line_item li
               INNER JOIN civicrm_participant p ON li.entity_id = p.id AND li.entity_table = 'civicrm_participant'
               INNER JOIN civicrm_participant_status_type pst ON pst.id = p.status_id AND pst.class = 'Positive'
               INNER JOIN civicrm_price_field pf ON li.price_field_id = pf.id
               WHERE p.event_id = event_civireport.id AND pf.name LIKE '%professional%'
             )",
          ),
          'volunteers' => array(
            'title' => ts('Volunteers'),
            'dbAlias' => "(SELECT ROUND(COALESCE(SUM(qty), 0), 0)
              FROM civicrm_line_item li
               INNER JOIN civicrm_participant p ON li.entity_id = p.id AND li.entity_table = 'civicrm_participant'
               INNER JOIN civicrm_participant_status_type pst ON pst.id = p.status_id AND pst.class = 'Positive'
               INNER JOIN civicrm_price_field pf ON li.price_field_id = pf.id
               WHERE p.event_id = event_civireport.id AND pf.name LIKE '%volunteer%'
             )",
          ),
        ),
        'filters' => [
          'event_type_id' => array(
            'title' => ts('Event Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ),
          'event_start_date' => array(
            'title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'alias' => 'event_civireport',
          ),
          'is_active' => array(
            'title' => ts('Is Active?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ),
        ],
        'order_bys' => [
          'event_type_id' => array(
            'title' => ts('Event Type'),
          ),
        ],
      ],
      'civicrm_participant' => [
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => [
          'participant_status_id' => [
            'title' => ts('Registrant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(),
          ],
        ],
      ],
    );
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
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => $this->_eventTemplateCustomFieldID]);
    $columnName = $customField['column_name'];
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);

    $this->_from = "
         FROM  civicrm_event {$this->_aliases['civicrm_event']}
               LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']} ON {$this->_aliases['civicrm_participant']}.event_id = {$this->_aliases['civicrm_event']}.id
               LEFT JOIN $customTableName temp ON temp.entity_id = {$this->_aliases['civicrm_event']}.id AND temp.{$columnName} IS NOT NULL
     ";
  }

  function where() {
    $clauses = array("{$this->_aliases['civicrm_event']}.is_template = 0");
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause("{$this->_aliases[$tableName]}.".$field['name'], $relative, $from, $to, $field['type']);
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

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_event']}.id ";
  }

  function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    $eventType = CRM_Core_OptionGroup::values('event_type');
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    $newRows = [];
    if ($this->_outputMode == 'csv') {
      foreach ($rows as $rowNum => $row) {
        $rows[$rowNum]['civicrm_event_event_type_id'] = CRM_Utils_Array::value($row['civicrm_event_event_type_id'], $eventType);
      }
      return;
    }
    else {
      unset($this->_columnHeaders['civicrm_event_event_type_id']);
    }

    foreach ($eventType as $id => $type) {
      $newRows[$type] = [];
      foreach ($rows as $rowNum => $row) {
        if ($row['civicrm_event_event_type_id'] == $id) {
          unset($row['civicrm_event_event_type_id']);
          foreach (['civicrm_event_title'] as $column) {
            if (!empty($row[$column])) {
              $url = CRM_Utils_System::url("civicrm/event/manage/settings",
                'reset=1&action=update&id=' . $row['civicrm_event_id']
              );
              $row[$column] = sprintf("<a href='%s' target='_blank'>%s</a>", $url, $row[$column]);
            }
          }

          $newRows[$type][$rowNum] = $row;
          unset($rows[$rowNum]);
        }
      }
      if (empty($newRows[$type])) {
        unset($newRows[$type]);
      }
    }
    $rows = $newRows;
  }

  public function getTemplateFileName() {
    return 'CRM/Aoreports/Form/Report/EventsThisQuarter.tpl';
  }

}
