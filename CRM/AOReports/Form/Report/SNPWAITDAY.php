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
            'dbAlias' => "SQ2.region",
          ),
          'q1' => array(
            'extends' => 'Activity',
            'name' => 'q1',
            'required' => TRUE,
            'title' => 'Q1',
            'dbAlias' => "ROUND(AVG(SQ2.Q1), 2)",
          ),
          'q2' => array(
            'extends' => 'Activity',
            'name' => 'q2',
            'required' => TRUE,
            'title' => 'Q2',
            'dbAlias' => "ROUND(AVG(SQ2.Q2), 2)",
          ),
          'q3' => array(
            'extends' => 'Activity',
            'name' => 'q3',
            'required' => TRUE,
            'title' => 'Q3',
            'dbAlias' => "ROUND(AVG(SQ2.Q3), 2)",
          ),
          'q4' => array(
            'extends' => 'Activity',
            'name' => 'q3',
            'required' => TRUE,
            'title' => 'Q4',
            'dbAlias' => "ROUND(AVG(SQ2.Q4), 2)",
          ),
          'total' => array(
            'title' => ts('Total count'),
            'dbAlias' => 'COUNT(SQ2.region)',
            'required' => TRUE,
          ),
        ),
      ),
    );
    parent::__construct();
  }

  function from() {
    $this->_from = "
FROM (
SELECT SQ.region,
  IF(SQ.quarter_provided=1, date_diff, NULL) AS Q1,
  IF(SQ.quarter_provided=2, date_diff, NULL) AS Q2,
  IF(SQ.quarter_provided=3, date_diff, NULL) AS Q3,
  IF(SQ.quarter_provided=4, date_diff, NULL) AS Q4
FROM (
      SELECT DATEDIFF( MAX(pa.activity_date_time),
        MIN(ra.activity_date_time)) AS date_diff,
        IF(r.service_region_776 IS NULL OR r.service_region_776='','Unknown', service_region_776) as region,
        QUARTER(MAX(pa.activity_date_time)) as quarter_provided
      FROM civicrm_case_activity rca
        INNER JOIN civicrm_activity ra ON rca.activity_id=ra.id
        INNER JOIN civicrm_activity_contact rac ON ra.id=rac.activity_id
        INNER JOIN civicrm_case_activity pca ON rca.case_id=pca.case_id
        INNER JOIN civicrm_activity pa ON pca.activity_id=pa.id
        LEFT JOIN civicrm_value_chapters_and__18 r ON rac.contact_id=r.entity_id
      WHERE ra.is_deleted = 0 AND ra.activity_type_id = 136 AND rac.record_type_id = 3 AND pa.is_deleted=0 AND pa.activity_type_id=137 AND pa.status_id='2'
      GROUP BY rca.case_id) AS SQ
    ) AS SQ2 ";
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY SQ2.region WITH ROLLUP ";
  }

  function alterDisplay(&$rows) {
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
