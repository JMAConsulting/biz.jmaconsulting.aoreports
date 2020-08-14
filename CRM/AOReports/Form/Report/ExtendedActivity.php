<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_ExtendedActivity extends CRM_Report_Form_Activity {
  protected $_customGroupExtends = [
    'Activity', 'Organization',
  ];

  public function __construct() {
    parent::__construct();
    $details = [];
    $staff = civicrm_api3('GroupContact', 'get', [
      'sequential' => 1,
      'group_id' => "Autism_Ontario_Prov_Staff_485",
      'status' => 'Added',
      'options' => ['limit' => 0],
      'api.Contact.get' => ['return' => "display_name"],
    ]);
    if ($staff['count'] > 0) {
      foreach ($staff['values'] as $ind) {
        $details[$ind['api.Contact.get']['values'][0]['contact_id']] = $ind['api.Contact.get']['values'][0]['display_name'];
      }
    }

    $this->_columns['civicrm_contact']['fields']['contact_source']['title'] = ts('Organization Name');
    $this->_columns['civicrm_contact']['order_bys']['contact_source'] = [
      'name' => 'contact_source',
      'dbAlias' => 'civicrm_contact_contact_source',
      'title' => ts('Organization Name'),
    ];
    $this->_columns['civicrm_contact']['filters']['staff_assignee'] = [
      'name' => 'staff_assignee',
      'title' => ts('Staff assigned to'),
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => $details,
    ];
  }

  /**
   * Build where clause.
   *
   * @todo get rid of $recordType param. It's only because 3 separate contact tables
   * are mis-declared as one that we need it.
   *
   * @param string $recordType
   */
  public function where($recordType = NULL) {
    $this->_where = " WHERE {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (($field['name'] == 'staff_assignee') || (
            $fieldName != 'contact_' . $recordType && (strstr($fieldName, '_target') ||
              strstr($fieldName, '_assignee') ||
              strstr($fieldName, '_source')
            )
          )) {
            continue;
          }
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op && !($fieldName === "contact_{$recordType}" && ($op === 'nnll' || $op === 'nll'))) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
              if ($field['name'] == 'include_case_activities') {
                $clause = NULL;
              }
              if ($fieldName == 'activity_type_id' &&
                empty($this->_params['activity_type_id_value'])
              ) {
                if (empty($this->_params['include_case_activities_value'])) {
                  $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'label', TRUE);
                }
                $actTypes = array_flip($this->activityTypes);
                $clause = "( {$this->_aliases['civicrm_activity']}.activity_type_id IN (" .
                  implode(',', $actTypes) . ") )";
              }
            }
          }

          if ($field['name'] == 'current_user') {
            if (CRM_Utils_Array::value("{$fieldName}_value", $this->_params) ==
              1
            ) {
              // get current user
              if ($contactID = CRM_Core_Session::getLoggedInContactID()) {
                $clause = "{$this->_aliases['civicrm_activity_contact']}.activity_id IN
                           (SELECT activity_id FROM civicrm_activity_contact WHERE contact_id = {$contactID})";
              }
              else {
                $clause = NULL;
              }
            }
            else {
              $clause = NULL;
            }
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where .= " ";
    }
    else {
      $this->_where .= " AND " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  /**
   * @param $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $this->_aliases['civicrm_case_activity'] = 'case_activity_civireport';
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignee', $activityContacts);

    //Assign those recordtype to array which have filter operator as 'Is not empty' or 'Is empty'
    $nullFilters = array();
    foreach (array('target', 'source', 'assignee') as $type) {
      if (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) ==
        'nnll' || !empty($this->_params["contact_{$type}_value"])
      ) {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NOT NULL ";
      }
      elseif (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) ==
        'nll'
      ) {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NULL ";
      }
    }

    // @todo - all this temp table stuff is here because pre 4.4 the activity contact
    // form did not exist.
    // Fixing the way the construct method declares them will make all this redundant.
    // 1. fill temp table with target results
    $this->buildACLClause(array('civicrm_contact_target'));
    $this->select('target');
    $this->from();
    $this->customDataFrom();
    $this->where('target');
    $tempTableName = $this->createTemporaryTable('activity_temp_table', "{$this->_select} {$this->_from} {$this->_where}");

    // 2. add new columns to hold assignee and source results
    // fixme: add when required
    $tempQuery = "
  ALTER TABLE  $tempTableName
  MODIFY COLUMN civicrm_contact_contact_target_id VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_assignee VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_assignee_id VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source_id VARCHAR(128),
  ADD COLUMN civicrm_phone_contact_assignee_phone VARCHAR(128),
  ADD COLUMN civicrm_phone_contact_source_phone VARCHAR(128),
  ADD COLUMN civicrm_email_contact_assignee_email VARCHAR(128),
  ADD COLUMN civicrm_email_contact_source_email VARCHAR(128)";
    $this->executeReportQuery($tempQuery);

    // 3. fill temp table with assignee results
    $this->buildACLClause(array('civicrm_contact_assignee'));
    $this->select('assignee');
    $this->buildAssigneeFrom();

    $this->customDataFrom();
    $this->where('assignee');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery = "INSERT INTO $tempTableName ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    $this->executeReportQuery($tempQuery);

    // 4. fill temp table with source results
    $this->buildACLClause(array('civicrm_contact_source'));
    $this->select('source');
    $this->buildSourceFrom();
    $this->customDataFrom();
    $this->where('source');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery = "INSERT INTO $tempTableName ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    $this->executeReportQuery($tempQuery);

    // 5. show final result set from temp table
    $rows = array();
    $this->select('final');
    $this->_having = "";
    if (!empty($nullFilters)) {
      $this->_having = "HAVING " . implode(' AND ', $nullFilters);
    }
    $this->orderBy();
    foreach ($this->_sections as $alias => $section) {
      if (!empty($section) && $section['name'] == 'activity_date_time') {
        $this->alterSectionHeaderForDateTime($tempTableName, $section['tplField']);
      }
    }

    if ($applyLimit) {
      $this->limit();
    }

    $groupByFromSelect = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, 'civicrm_activity_id');

    $this->_where = " WHERE (1)";
    $this->buildPermissionClause();
    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    $assigneeWhereClause = '';
    if (!empty($this->_params['staff_assignee_value'])) {
      $assigneeWhereClause = sprintf('AND contact_id IN (%s)', implode(',', $this->_params['staff_assignee_value']));
    }

    $sql = "{$this->_select}
      FROM $tempTableName tar
      INNER JOIN civicrm_activity {$this->_aliases['civicrm_activity']} ON {$this->_aliases['civicrm_activity']}.id = tar.civicrm_activity_id
      INNER JOIN civicrm_activity_contact {$this->_aliases['civicrm_activity_contact']} ON {$this->_aliases['civicrm_activity_contact']}.activity_id = {$this->_aliases['civicrm_activity']}.id
      AND {$this->_aliases['civicrm_activity_contact']}.record_type_id = 1
      LEFT JOIN civicrm_contact contact_civireport ON contact_civireport.id = {$this->_aliases['civicrm_activity_contact']}.contact_id
      {$this->_where} {$assigneeWhereClause} {$groupByFromSelect} {$this->_having} {$this->_orderBy} {$this->_limit}";

    CRM_Utils_Hook::alterReportVar('sql', $sql, $this);
    $this->addToDeveloperTab($sql);

    return $sql;
  }

}
