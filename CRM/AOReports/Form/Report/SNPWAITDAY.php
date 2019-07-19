<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_SNPWAITDAY extends CRM_Report_Form {

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'total' => array(
            'title' => ts('Contact ID'),
            'dbAlias' => 'COUNT(DISTINCT contact_civireport.id)',
            'display' => FALSE,
            'required' => TRUE,
          ),
          'family_count' => array(
            'title' => ts('Region'),
            'required' => TRUE,
            'dbAlias' => "temp.region",
          ),
          'time_diff' => array(
            'title' => ts('Waiting Days'),
            'dbAlias' => 'AVG(temp.time_diff)',
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
      SELECT rac.contact_id, service_region_776, pa.status_id, DATEDIFF( MAX(pa.activity_date_time), MIN(ra.activity_date_time)) AS time_diff, r.service_region_776 AS region
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
    $this->_groupBy = "GROUP BY temp.region";
  }

  function alterDisplay(&$rows) {
    foreach ($rows as $key => &$row) {
      if (CRM_Utils_System::isNull($row['civicrm_contact_family_count'])) {
        unset($rows[$key]);
      }
    }

    unset($this->_columnHeaders["civicrm_contact_total"]);

    $rows = $newRows;
  }

}
