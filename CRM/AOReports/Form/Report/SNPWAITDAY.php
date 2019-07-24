<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_SNPWAITDAY extends CRM_Report_Form {

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'family_count' => array(
            'title' => ts('Region'),
            'required' => TRUE,
            'dbAlias' => "temp.region",
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
          'time_diff' => array(
            'title' => ts('Waiting Days'),
            'dbAlias' => 'ROUND(AVG(temp.time_diff), 2)',
            'required' => TRUE,
          ),
          'total' => array(
            'title' => ts('Total count'),
            'dbAlias' => 'COUNT(DISTINCT contact_civireport.id)',
            'required' => TRUE,
          ),
        ),
        'filters' => [
          'status_id' => array(
            'name' => 'status_id',
            'dbAlias' => "temp.status_id",
            'title' => 'Service Navigation Provision Activity Status',
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus(),
          ),
        ],
      ),
    );
    parent::__construct();
  }

  function from() {
    $this->_from = "
    FROM civicrm_contact {$this->_aliases['civicrm_contact']}
    INNER JOIN (
      SELECT rac.contact_id, service_region_776, pa.status_id, DATEDIFF( MAX(pa.activity_date_time), MIN(ra.activity_date_time)) AS time_diff, r.service_region_776 AS region,
      ra.activity_date_time as dof
      FROM civicrm_case_activity rca
      INNER JOIN civicrm_activity ra ON rca.activity_id=ra.id AND ra.is_deleted = 0 AND ra.activity_type_id = 136
      INNER JOIN civicrm_activity_contact rac ON ra.id=rac.activity_id AND rac.record_type_id = 3
      INNER JOIN civicrm_case_activity pca ON rca.case_id=pca.case_id
      INNER JOIN civicrm_activity pa ON pca.activity_id=pa.id AND pa.is_deleted=0 AND pa.activity_type_id=137 AND pa.status_id=2
      LEFT JOIN civicrm_value_chapters_and__18 r ON rac.contact_id=r.entity_id
      GROUP BY r.service_region_776, rca.case_id, ra.activity_type_id, pa.activity_type_id
      HAVING MAX(pa.activity_date_time) < DATE_SUB(LAST_DAY(NOW()),INTERVAL DAY(LAST_DAY(NOW()))- 1 DAY)
      ) temp ON temp.contact_id = {$this->_aliases['civicrm_contact']}.id
    ";
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
        'civicrm_contact_family_count' => $name,
        'civicrm_contact_quarter' => NULL,
        'civicrm_contact_q1' => 0,
        'civicrm_contact_q2' => 0,
        'civicrm_contact_q3' => 0,
        'civicrm_contact_q4' => 0,
        'civicrm_contact_time_diff' => 0,
        'civicrm_contact_total' => 1,
      ];
    }
    $newRows['no-region'] = [
      'civicrm_contact_family_count' => 'Unknown',
      'civicrm_contact_quarter' => NULL,
      'civicrm_contact_q1' => 0,
      'civicrm_contact_q2' => 0,
      'civicrm_contact_q3' => 0,
      'civicrm_contact_q4' => 0,
      'civicrm_contact_time_diff' => 0,
      'civicrm_contact_total' => 1,
    ];


    foreach ($newRows as $key => $row) {
      foreach ($rows as &$row) {
        if (strstr($row['civicrm_contact_family_count'], $key)) {
          $newRows[$key]['civicrm_contact_quarter'] = $row['civicrm_contact_quarter'];
          $newRows[$key]["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_time_diff'];
        }
        elseif ($row['civicrm_contact_family_count'] == '') {
          $newRows['no-region']['civicrm_contact_quarter'] = $row['civicrm_contact_quarter'];
          $newRows['no-region']["civicrm_contact_q{$row['civicrm_contact_quarter']}"] = $row['civicrm_contact_time_diff'];
        }
      }
    }

    unset($this->_columnHeaders["civicrm_contact_quarter"]);

    $rows = $newRows;
  }

}
