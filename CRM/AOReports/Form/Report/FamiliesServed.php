<?php
use CRM_AOReports_Utils as E;

class CRM_AOReports_Form_Report_FamiliesServed extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $addPaging = FALSE;
  protected $_optimisedForOnlyFullGroupBy = FALSE;
  protected $_add2groupSupported = FALSE;
  protected $_exposeContactID = FALSE;

  protected $_dateClause;

  protected $_customGroupGroupBy = FALSE;
  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'total' => array(
            'title' => ts('Contact ID'),
            'dbAlias' => 'COUNT(DISTINCT contact_civireport.id)',
            'required' => TRUE,
          ),
          'family_count' => array(
            'title' => ts('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'),
            'required' => TRUE,
            'dbAlias' => "'New Families Served'",
          ),
          'year' => array(
            'title' => ts('Year'),
            'no_display' => TRUE,
            'dbAlias' => 'YEAR(temp.dof)',
            'required' => TRUE,
          ),
          'quarter' => array(
            'title' => ts('Quarter'),
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
            if (!CRM_Utils_Array::value('no_display', $field)) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
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
      LEFT JOIN civicrm_value_donation_cust_2 lang ON lang.entity_id = temp.parent_id AND lang.language_10 IS NOT NULL
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

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    if ($fields['dof_relative'] === NULL) {
      $errors['dof_relative'] = ts("Duration is a required filter");
    }
    return $errors;
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY QUARTER(temp.dof)";
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
    $originalSQL = $this->buildQuery(TRUE);

    $quarters = [
      1 => NULL,
      2 => NULL,
      3 => NULL,
      4 => NULL,
    ];
    $defaultYear = '';

    $newRows = [
      [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => ts('Total New Families Served'),
        'civicrm_contact_year' => '',
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
      ],
      [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => ts('Francophone New Families Served'),
        'civicrm_contact_year' => '',
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
      ],
      [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => ts('Total Families Served'),
        'civicrm_contact_year' => '',
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
      ],
      [
        'civicrm_contact_total' => 1,
        'civicrm_contact_family_count' => ts('Francophone Families Served'),
        'civicrm_contact_year' => '',
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
      ],
    ];

    foreach ($newRows as $key => $row) {
      if ($key == 0) {
        foreach ($rows as $value) {
          $newRows[$key]['civicrm_contact_quarter'] = $value['civicrm_contact_quarter'];
          $newRows[$key]["civicrm_contact_q{$value['civicrm_contact_quarter']}"] = $value['civicrm_contact_total'];
          if ($value['civicrm_contact_year']) {
            $defaultYear = $quarters[$value['civicrm_contact_quarter']] = $value['civicrm_contact_year'];
          }
        }
      }
      else {
        if ($key == 2) {
          $sql = str_replace($this->_dateClause, '(1)', $originalSQL);
        }
        else {
          $sql = str_replace('lang.language_10 IS NOT NULL', 'lang.language_10 IS NOT NULL AND lang.language_10 LIKE \'%French%\'', $originalSQL);
          if ($key == 3) {
           $sql = str_replace($this->_dateClause, '(1)', $originalSQL);
          }
        }
        $data = CRM_Core_DAO::executeQuery($sql)->fetchAll();
        if (!empty($data)) {
          foreach ($data as $value) {
            $newRows[$key]['civicrm_contact_quarter'] = $value['civicrm_contact_quarter'];
            $newRows[$key]["civicrm_contact_q{$value['civicrm_contact_quarter']}"] = $value['civicrm_contact_total'];
            if ($value['civicrm_contact_year']) {
              $defaultYear = $quarters[$value['civicrm_contact_quarter']] = $value['civicrm_contact_year'];
            }
          }
        }
        else {
          $newRows[$key]["civicrm_contact_year"] = $defaultYear;
        }
      }
    }

    unset($this->_columnHeaders["civicrm_contact_total"]);
    unset($this->_columnHeaders["civicrm_contact_quarter"]);

    $rows = $newRows;
  }

  public function buildInstanceAndButtons() {
    parent::buildInstanceAndButtons();
    CRM_Core_Resources::singleton()->addScript(
    "CRM.$(function($) {
      $('.report-layout td.report-label').removeClass('report-label');
    });"
  );
  }


}
