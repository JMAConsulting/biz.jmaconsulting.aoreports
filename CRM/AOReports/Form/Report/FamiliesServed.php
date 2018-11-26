<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_FamiliesServed extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $addPaging = FALSE;
  protected $_optimisedForOnlyFullGroupBy = FALSE;
  protected $_add2groupSupported = FALSE;
  protected $_exposeContactID = FALSE;

  protected $_customGroupGroupBy = FALSE;
  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'total' => array(
            'no_display' => TRUE,
            'title' => ts('Contact ID'),
            'dbAlias' => 'COUNT(DISTINCT id)',
            'required' => TRUE,
          ),
          'preferred_language' => array(
            'title' => ts('New Families Served'),
            'required' => TRUE,
          ),
          'year' => array(
            'title' => ts('Year'),
            'no_display' => TRUE,
            'dbAlias' => 'YEAR(temp.dof)',
            'required' => TRUE,
          ),
          'quarter' => array(
            'title' => ts('Quarter'),
            'no_display' => TRUE,
            'dbAlias' => 'QUARTER(temp.dof)',
            'required' => TRUE,
          ),
          'q1' => array(
            'extends' => 'Activity',
            'name' => 'q1',
            'required' => TRUE,
            'title' => 'Q1',
            'dbAlias' => "0",
          ),
          'q2' => array(
            'extends' => 'Activity',
            'name' => 'q2',
            'required' => TRUE,
            'title' => 'Q2',
            'dbAlias' => "0",
          ),
          'q3' => array(
            'extends' => 'Activity',
            'name' => 'q3',
            'required' => TRUE,
            'title' => 'Q3',
            'dbAlias' => "0",
          ),
          'q4' => array(
            'extends' => 'Activity',
            'name' => 'q3',
            'required' => TRUE,
            'title' => 'Q4',
            'dbAlias' => "0",
          ),
        ),
        'filters' => array(
          'dof' => array(
            'name' => 'dof',
            'title' => 'Duration',
            'default' => 'this.month',
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      ),
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
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $tableName = E::getNewChildContactTableName();
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      LEFT JOIN {$tableName} temp ON temp.parent_id = {$this->_aliases['civicrm_contact']}.id
    ";
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
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.preferred_language, YEAR(temp.dof), QUARTER(temp.dof)";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.preferred_language";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
    CRM_Core_DAO::reenableFullGroupByMode();
  }

  function alterDisplay(&$rows) {
    $plFound = NULL;
    $newRows = [
      'total' => [
        'civicrm_contact_total' => 1,
        'civicrm_contact_preferred_language' => ts('Total New Families Served'),
        'civicrm_contact_year' => '',
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
      ],
    ];
    $quarters = [
      1 => NULL,
      2 => NULL,
      3 => NULL,
      4 => NULL,
    ];
    $defaultYear = '';
    $plOptions = CRM_Contact_BAO_Contact::buildOptions('preferred_language');
    // custom code to alter rows
    foreach ($rows as $rowNum => $row) {
      if (empty($newRows[$plFound])) {
        $plFound = $row['civicrm_contact_preferred_language'];
        $newRows[$plFound] = $row;
      }
      $defaultYear = $quarters[$row['civicrm_contact_quarter']] = $row['civicrm_contact_year'];
      $newRows[$plFound]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_total'];
      $newRows['total']["civicrm_contact_q{$row['civicrm_contact_quarter']}"] += $row['civicrm_contact_total'];
      $newRows[$plFound]['civicrm_contact_preferred_language'] = $plOptions[$plFound];
    }

    if ($rows) {
      $rows = $newRows;
    }
    foreach ($quarters as $quarter => $year) {
      $year = $year ?: $defaultYear;
      $this->_columnHeaders["civicrm_contact_q{$quarter}"]['title'] = $this->_columnHeaders["civicrm_contact_q{$quarter}"]['title'] . " $year";
    }
  }


}
