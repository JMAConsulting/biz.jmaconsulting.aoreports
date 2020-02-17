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
            'title' => ts('Event Date and Time'),
          ),
          'created_date' => array(
            'title' => ts('Created Date'),
          ),
          'title' => array(
            'title' => ts('Name of Event'),
          ),
          'description' => array(
            'title' => ts('Description'),
          ),
          'is_public' => [
            'title' => ts('Is this ceremony open to public?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ],
          'is_active' => array(
            'title' => ts('Is Active?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ),
          'name' => [
            'title' => ts('Name of Event Location?'),
            'dbAlias' => 'a.name',
          ],
          'street_address' => [
            'title' => ts('Event Street Address'),
            'dbAlias' => 'a.street_address',
          ],
          'supplemental_address_1' => [
            'title' => ts('Event Supplemental Address'),
            'dbAlias' => 'a.supplemental_address_1',
          ],
          'city' => [
            'title' => ts('Event City'),
            'dbAlias' => 'a.city',
          ],
          'postal_code' => [
            'title' => ts('Event Postal Code'),
            'dbAlias' => 'CONCAT(COALESCE(a.postal_code_suffix, \'\'), a.postal_code)',
          ],
          'first_name' => [
            'title' => ts('Creator First Name'),
            'dbAlias' => 'cc.first_name',
          ],
          'last_name' => [
            'title' => ts('Creator Last Name'),
            'dbAlias' => 'cc.last_name',
          ],
          'email_address' => [
            'dbAlias' => 'e.email',
            'title' => ts('Creator Email Address'),
          ],
          'phone' => [
            'dbAlias' => 'p.phone',
            'title' => ts('Creator Phone'),
          ],
          'mailing_address' => [
            'dbAlias' => 'CONCAT(ca.street_address, "  ", ca.city, "  ", COALESCE(ca.postal_code_suffix, \'\'), ca.postal_code)',
            'title' => ts('Creator Mailing Address'),
          ],
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
          'first_name' => [
            'dbAlias' => 'cc.first_name',
            'title' => ts('Creator First Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'last_name' => [
            'dbAlias' => 'cc.last_name',
            'title' => ts('Creator Last Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'email_address' => [
            'dbAlias' => 'e.email',
            'title' => ts('Email Address of Submitter'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'phone' => [
            'dbAlias' => 'p.phone',
            'title' => ts('Phone of Submitter'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'street_address' => [
            'title' => ts('Street Address'),
            'dbAlias' => 'a.street_address',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'city' => [
            'title' => ts('City'),
            'dbAlias' => 'a.city',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'postal_code' => [
            'title' => ts('Postal Code'),
            'alias' => 'civicrm_event_postal_code',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
        ],
        'order_bys' => [
          'event_type_id' => array(
            'title' => ts('Event Type'),
          ),
          'event_start_date' => array(
            'title' => ts('Event Date and Time'),
          ),
          'created_date' => array(
            'title' => ts('Created Date'),
          ),
          'title' => array(
            'title' => ts('Name of Event'),
          ),
          'description' => array(
            'title' => ts('Description'),
          ),
          'is_public' => [
            'title' => ts('Is this ceremony open to public?'),
          ],
          'name' => [
            'title' => ts('Name of Event Location?'),
            'dbAlias' => 'a.name',
          ],
          'street_address' => [
            'title' => ts('Event Street Address'),
            'dbAlias' => 'a.street_address',
          ],
          'city' => [
            'title' => ts('Event City'),
            'dbAlias' => 'a.city',
          ],
          'postal_code' => [
            'title' => ts('Event Postal Code'),
            'dbAlias' => 'CONCAT(a.postal_code_suffix, a.postal_code)',
          ],
          'first_name' => [
            'title' => ts('Creator First Name'),
            'dbAlias' => 'cc.first_name',
          ],
          'last_name' => [
            'title' => ts('Creator Last Name'),
            'dbAlias' => 'cc.last_name',
          ],
          'email_address' => [
            'dbAlias' => 'e.email',
            'title' => ts('Creator Email Address'),
          ],
          'phone' => [
            'dbAlias' => 'p.phone',
            'title' => ts('Creator Phone'),
          ],
          'what_is_your_name' => [
            'dbAlias' => 'value_flag_raising_66_civireport.what_is_your_name__847',
            'title' => 'What is your name?',
          ],
          'what_is_your_email_address' => [
            'dbAlias' => 'value_flag_raising_66_civireport.what_is_your_email_address__848',
            'title' => 'What is your Email address?',
          ],
          'what_is_your_mailing_address__849' => [
            'dbAlias' => 'value_flag_raising_66_civireport.what_is_your_mailing_address__849',
            'title' => 'What is your Mailing address?',
          ],
          'autism_ontario_representation_850' => [
            'dbAlias' => 'value_flag_raising_66_civireport.autism_ontario_representation_850',
            'title' => 'Autism Ontario representation',
          ],
          'do_you_require_a_flag__846' => [
            'dbAlias' => 'value_flag_raising_66_civireport.do_you_require_a_flag__846',
            'title' => 'Do you require a flag?',
          ],
          'what_is_your_phone_number__857' => [
            'dbAlias' => 'value_flag_raising_66_civireport.what_is_your_phone_number__857',
            'title' => 'What is your phone number?',
          ],
          'what_is_your_phone_number__857' => [
            'dbAlias' => 'value_flag_raising_66_civireport.what_is_your_phone_number__857',
            'title' => 'What is your phone number?',
          ],
          'do_you_want_autism_ontario_to_pr_888' => [
            'dbAlias' => 'value_flag_raising_66_civireport.do_you_want_autism_ontario_to_pr_888',
            'title' => 'Do you want Autism Ontario to Promote your event?',
          ],
          'chapter_325' => [
            'dbAlias' => 'value_flag_raising_66_civireport.chapter_325',
            'title' => 'Chapter',
          ],
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

  function from() {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => $this->_eventTemplateCustomFieldID]);
    $columnName = $customField['column_name'];
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);

    $this->_from = "
         FROM  civicrm_event {$this->_aliases['civicrm_event']}
               LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']} ON {$this->_aliases['civicrm_participant']}.event_id = {$this->_aliases['civicrm_event']}.id
               LEFT JOIN $customTableName temp ON temp.entity_id = {$this->_aliases['civicrm_event']}.id AND temp.{$columnName} IS NOT NULL
               LEFT JOIN civicrm_loc_block l ON l.id = {$this->_aliases['civicrm_event']}.loc_block_id
               LEFT JOIN civicrm_address a ON a.id = l.address_id
               LEFT JOIN civicrm_contact cc ON cc.id = {$this->_aliases['civicrm_event']}.created_id
               LEFT JOIN civicrm_address ca ON ca.contact_id = cc.id AND ca.is_primary = 1
               LEFT JOIN civicrm_email e ON e.contact_id = cc.id AND ca.is_primary = 1
               LEFT JOIN civicrm_phone p ON p.contact_id = cc.id AND ca.is_primary = 1
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

    if (!empty($this->_columnHeaders['civicrm_value_flag_raising_66_custom_888'])) {
      $column = ['civicrm_value_flag_raising_66_custom_888' => $this->_columnHeaders['civicrm_value_flag_raising_66_custom_888']];
      unset($this->_columnHeaders['civicrm_value_flag_raising_66_custom_888']);
      array_splice($this->_columnHeaders, 3, 0, $column);
    }
    foreach ($eventType as $id => $type) {
      $newRows[$type] = [];
      foreach ($rows as $rowNum => $row) {
        if ($row['civicrm_event_event_type_id'] == $id) {
          unset($row['civicrm_event_event_type_id']);
          foreach (['civicrm_event_title', 'civicrm_event_event_start_date'] as $column) {
            if (!empty($row[$column])) {
              $url = CRM_Utils_System::url("civicrm/event/manage/settings",
                'reset=1&action=update&id=' . $row['civicrm_event_id']
              );
              if ($column == 'civicrm_event_event_start_date') {
                $row[$column] = CRM_Utils_Date::customFormat($row[$column]);
              }

              $row[$column] = sprintf("<a href='%s' target='_blank'>%s</a>", $url, $row[$column]);
            }
          }

          $row['civicrm_value_flag_raising_66_custom_846'] = CRM_Utils_Array::value('civicrm_value_flag_raising_66_custom_846', $row) == NULL ? '' : $this->alterBoolean($row['civicrm_value_flag_raising_66_custom_846']);
          $row['civicrm_value_flag_raising_66_custom_888'] = CRM_Utils_Array::value('civicrm_value_flag_raising_66_custom_888', $row) == NULL ? '' : $this->alterBoolean($row['civicrm_value_flag_raising_66_custom_888']);
          if (!empty($row['civicrm_value_flag_raising_66_custom_888'])) {
            $column = ['civicrm_value_flag_raising_66_custom_888' => $row['civicrm_value_flag_raising_66_custom_888']];
            unset($row['civicrm_value_flag_raising_66_custom_888']);
            array_splice($row, 3, 0, $column);
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
