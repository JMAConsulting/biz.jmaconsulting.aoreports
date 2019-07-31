<?php

class CRM_AOReports_Form_Report_MinistryRegion extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupGroupBy = FALSE; 

  function __construct() {
    
    $this->_columns = array(
      'families' => array(
        'fields' => array(
          'family' => array(
            'title' => ts('Number of Families'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => FALSE,
          ),
        ),
      ),
    );
    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Number of families served broken down by ministry region'));
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    $this->_columnHeaders["region"]['title'] = "Region";
    $this->_columnHeaders["count"]['title'] = "Number of Families Served";
    $this->_select = "SELECT a.Region as region, SUM(a.Count) as count FROM (SELECT s7.region as Region, SUM(s7.families_this_event_count) AS Count
FROM
(
SELECT s6.region, p.event_id, count(s6.lfm_id) as families_this_event_count

FROM civicrm_participant p INNER JOIN
(
SELECT s2.lfm_id, s2.family_member_id, IF(s5.region IS NULL or s5.region='', 'Unknown', s5.region) AS region FROM
(
SELECT s.lfm_id, s.family_member_id FROM 
(
( 
SELECT lfm.entity_id AS lfm_id, lfm.entity_id AS family_member_id
FROM civicrm_value_newsletter_cu_3 as lfm
WHERE lfm.lead_family_member__28 = 1
)
UNION
( 
SELECT lfm.entity_id AS lfm_id, r.contact_id_b AS sibling_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_a
WHERE r.relationship_type_id=1 AND lfm.lead_family_member__28 = 1
)
UNION
(
SELECT lfm.entity_id AS lfm_id, r.contact_id_a AS sibling_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_b
WHERE r.relationship_type_id=4 AND lfm.lead_family_member__28 = 1
)
UNION
(
SELECT lfm.entity_id AS lfm_id, r.contact_id_b AS parent_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_a
WHERE r.relationship_type_id=4 AND lfm.lead_family_member__28 = 1 
)
UNION
(
SELECT lfm.entity_id AS lfm_id, r2.contact_id_a AS other_child_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_a 
INNER JOIN civicrm_relationship r2 ON r.contact_id_b=r2.contact_id_b AND r2.contact_id_a<>lfm.entity_id 
WHERE r.relationship_type_id=1 AND r2.relationship_type_id=1 AND lfm.lead_family_member__28 = 1
GROUP BY lfm.entity_id, r2.contact_id_a
)
) s 
GROUP BY s.lfm_id, s.family_member_id 
) s2 
INNER JOIN civicrm_contact lfmc ON s2.lfm_id=lfmc.id
INNER JOIN civicrm_contact family_member_c ON s2.family_member_id=family_member_c.id 
INNER JOIN

( 
SELECT s4.lfm_id, MIN(r.service_region_776) AS region FROM
(
SELECT s3.lfm_id, s3.family_member_id FROM
(
(
SELECT lfm.entity_id AS lfm_id, lfm.entity_id AS family_member_id
FROM civicrm_value_newsletter_cu_3 as lfm
WHERE lfm.lead_family_member__28 = 1
)
UNION
(
SELECT lfm.entity_id AS lfm_id, r.contact_id_b AS sibling_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_a
WHERE r.relationship_type_id=1 AND lfm.lead_family_member__28 = 1 
)
UNION
(
SELECT lfm.entity_id AS lfm_id, r.contact_id_b AS sibling_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_a
WHERE r.relationship_type_id=4 AND lfm.lead_family_member__28 = 1
)
UNION
(
SELECT lfm.entity_id AS lfm_id, r.contact_id_a AS sibling_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_b
WHERE r.relationship_type_id=4 AND lfm.lead_family_member__28 = 1 
)
UNION
(
SELECT lfm.entity_id AS lfm_id, r2.contact_id_a AS sibling_id
FROM civicrm_value_newsletter_cu_3 as lfm
INNER JOIN civicrm_relationship r ON lfm.entity_id=r.contact_id_a
INNER JOIN civicrm_relationship r2 ON r.contact_id_b=r2.contact_id_b AND r2.contact_id_a<>lfm.entity_id
WHERE r.relationship_type_id=1 AND r2.relationship_type_id=1 AND lfm.lead_family_member__28 = 1
GROUP BY lfm.entity_id, r2.contact_id_a
)
) s3 GROUP BY s3.lfm_id, s3.family_member_id
) s4
LEFT JOIN civicrm_value_chapters_and__18 r ON r.entity_id = s4.family_member_id
GROUP BY s4.lfm_id) AS s5
ON s5.lfm_id=s2.lfm_id
WHERE lfmc.is_deleted=0 AND family_member_c.is_deleted=0
) AS s6
ON s6.family_member_id=p.contact_id
INNER JOIN civicrm_event e ON p.event_id=e.id
WHERE p.status_id=1 AND YEAR(e.start_date)=2019 AND QUARTER(e.start_date)=2
GROUP BY s6.region, p.event_id
) AS s7
GROUP BY s7.region
UNION
SELECT event.region as Region, COUNT(event.count_service_nav) as Count 
FROM
(SELECT 1 as count_service_nav, IF(ct.service_region_776 IS NULL OR ct.service_region_776 = '', 'Unknown', ct.service_region_776) as region
FROM civicrm_activity a
INNER JOIN civicrm_case_activity ca ON a.id = ca.activity_id
INNER JOIN civicrm_activity_contact ac ON ac.activity_id = ca.activity_id
INNER JOIN civicrm_contact c on ac.contact_id = c.id
INNER JOIN civicrm_relationship rel ON rel.contact_id_b = c.id
INNER JOIN civicrm_value_newsletter_cu_3 lfm on rel.contact_id_a = lfm.entity_id
LEFT JOIN civicrm_value_chapters_and__18 ct ON ct.entity_id = ac.contact_id
WHERE YEAR(a.activity_date_time) = 2019 AND QUARTER(a.activity_date_time) = 2 AND a.is_current_revision = 1 AND ac.record_type_id = 3 AND rel.relationship_type_id = 1 AND c.is_deleted = 0 AND lfm.lead_family_member__28 = 1 AND a.activity_type_id = 137 AND  a.status_id = 2  AND a.is_deleted = 0
GROUP BY ct.service_region_776, a.id, lfm.entity_id) AS event 
GROUP BY region
UNION
SELECT event.region as Region, COUNT(event.count_consult) as Count 
FROM
(SELECT 1 as count_consult, IF(ct.service_region_776 IS NULL OR ct.service_region_776 = '', 'Unknown', ct.service_region_776) as region
FROM civicrm_activity a
INNER JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
INNER JOIN civicrm_contact c on ac.contact_id = c.id
INNER JOIN civicrm_relationship rel ON rel.contact_id_b = c.id
INNER JOIN civicrm_value_newsletter_cu_3 lfm on rel.contact_id_a = lfm.entity_id      
LEFT JOIN civicrm_value_chapters_and__18 ct ON ct.entity_id = ac.contact_id
WHERE YEAR(a.activity_date_time) = 2019 AND QUARTER(a.activity_date_time) = 2 AND a.is_current_revision = 1 AND ac.record_type_id = 3 AND rel.relationship_type_id = 1 AND c.is_deleted = 0 AND lfm.lead_family_member__28 = 1 AND a.activity_type_id = 70 AND  a.status_id = 2  AND a.is_deleted = 0
GROUP BY ct.service_region_776, a.id, lfm.entity_id) AS event 
GROUP BY region) as a
GROUP BY Region
";
  }

  function from() {
    $this->_from = NULL;

  }

  function where() {
    $clauses = array();
    
    if (!empty($clauses)) {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
  }

  function groupBy() {
    return FALSE;
  }

  function orderBy() {
    return FALSE;
  }

  function postProcess() {

    $this->beginPostProcess();
    
    $sql = $this->buildQuery(FALSE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }
  
  function alterDisplay(&$rows) {
  }

}
