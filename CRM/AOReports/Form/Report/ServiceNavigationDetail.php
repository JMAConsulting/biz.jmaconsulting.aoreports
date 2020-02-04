<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_ServiceNavigationDetail extends CRM_AOReports_Form_Report_ServiceNavigation {

    protected $_summary = NULL;
    protected $addPaging = FALSE;
    protected $_optimisedForOnlyFullGroupBy = FALSE;
    protected $_add2groupSupported = FALSE;
    protected $_exposeContactID = FALSE;

    function __construct() {
      $this->_columns = array(
        'civicrm_contact' => array(
          'dao' => 'CRM_Contact_DAO_Contact',
          'fields' => array(
            'contact_id' => array(
              'title' => ts('Contact ID'),
              'dbAlias' => 'DISTINCT contact_civireport.id',
              'required' => TRUE,
            ),
            'contact_name' => array(
              'title' => 'Contact Name',
              'required' => TRUE,
              'dbAlias' => "contact_civireport.sort_name",
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
          ),
          'filters' => array(
            'dof' => array(
              'name' => 'dof',
              'title' => 'Duration',
              'default' => 'this.month',
              'operatorType' => CRM_Report_Form::OP_DATE,
              'type' => CRM_Utils_Type::T_DATE,
            ),
            'activity_type' => array(
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
      if (empty($this->_params['activity_date_time_value']) && (!empty($this->_params['dof_relative']) || !empty($this->_params['dof_from']) || !empty($this->_params['dof_to']))) {
        $tempTableWhere = $this->dateClause('a.activity_date_time', $this->_params['dof_relative'], $this->_params['dof_from'], $this->_params['dof_to'], CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME);
      }
      elseif (!empty($this->_params['activity_date_time_value'])) {
        $tempTableWhere = "YEAR(a.activity_date_time) = {$this->_params['activity_date_time_value']}";
      }
      $tableName = E::getSNPActivityTableName($this->_params['activity_type_value'], $this, $this->_params['status_id_value'], $this->_params['status_id_op'], $tempTableWhere);
      $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']}
        INNER JOIN {$tableName} temp ON temp.parent_id = {$this->_aliases['civicrm_contact']}.id ";
    }

    function groupBy() {
      $this->_groupBy = "GROUP BY COALESCE(temp.region, ''), QUARTER(temp.dof), contact_civireport.id";
    }

    function where() {
      $clauses = ['(temp.region IS NULL OR temp.region = \'\')'];
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

    function alterDisplay(&$rows) {
      foreach ($rows as $rowNum => $row) {
        $rows[$rowNum]['civicrm_contact_contact_name'] = sprintf("<a href='%s'>%s</a>", CRM_Utils_System::url('civicrm/contact/view', 'reset=1&id=' . $row['civicrm_contact_contact_id']), $rows[$rowNum]['civicrm_contact_contact_name']);
      }
    }

}
