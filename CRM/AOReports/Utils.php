<?php

require_once __DIR__ . '../../../ao.variables.php';

class CRM_AOReports_Utils {

  public static function getSNPActivityTableName($activityTypeID, &$form, $ignoreStatus = FALSE) {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => SNP_REGION_CF_ID]);
    $SNPRegionColumnName = $customField['column_name'];
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);
    $tempTableName = 'temp_snp_activity' . substr(sha1(rand()), 0, 7);

    $status = $ignoreStatus ? " a.status_id = 2 " : '(1)';

    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS ' . $tempTableName);

    $sql = "
    CREATE TEMPORARY TABLE $tempTableName DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
    SELECT lfm.entity_id as lead_family_member_id, rel.contact_id_b as parent_id, DATE(a.activity_date_time) as dof, service_region_776 as region, a.status_id
    FROM civicrm_activity a
      INNER JOIN civicrm_case_activity ca ON a.id = ca.activity_id
      INNER JOIN civicrm_activity_contact ac ON ac.activity_id = ca.activity_id
      INNER JOIN civicrm_contact c on ac.contact_id = c.id
      INNER JOIN civicrm_relationship rel ON rel.contact_id_b = c.id
      INNER JOIN civicrm_value_newsletter_cu_3 lfm on rel.contact_id_a = lfm.entity_id
      LEFT JOIN civicrm_value_chapters_and__18 ct ON ct.entity_id = ac.contact_id
WHERE YEAR(a.activity_date_time) = 2019 AND ac.record_type_id = 3 AND rel.relationship_type_id = 1 AND c.is_deleted = 0 AND lfm.lead_family_member__28 = 1 AND a.activity_type_id = 137 AND $status AND a.is_deleted = 0
GROUP BY lfm.entity_id
    ";

    $form->addToDeveloperTab($sql);
    CRM_Core_DAO::executeQuery($sql);
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_parent ON $tempTableName(lead_family_member_id)");
    return $tempTableName;
  }

  public static function getSNPActivityAverageTime(&$form) {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => SNP_REGION_CF_ID]);
    $SNPRegionColumnName = $customField['column_name'];
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);
    $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Service Navigation Provision');
    $tempTableName = 'temp_snp_activity' . substr(sha1(rand()), 0, 7);

    $sql = "
    CREATE TEMPORARY TABLE $tempTableName DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
    SELECT DISTINCT lfm.entity_id as parent_id, DATE(a.activity_date_time) as dof, $SNPRegionColumnName as region, a.status_id,
    CASE pc.duration_331
    WHEN 1 THEN 2.5
    WHEN 2 THEN 10.0
    WHEN 3 THEN 22.5
    WHEN 4 THEN 45
    WHEN 5 THEN 90
    ELSE 0 END
    AS timediff
FROM civicrm_activity a
INNER JOIN civicrm_activity_contact ac ON a.id = ac.activity_id
INNER JOIN civicrm_contact c on ac.contact_id = c.id
INNER JOIN civicrm_value_newsletter_cu_3 lfm on c.id = lfm.entity_id
LEFT JOIN civicrm_value_parent_consul_10 pc on a.id = pc.entity_id
LEFT JOIN $customTableName ct ON ct.entity_id = ac.contact_id
WHERE YEAR(a.activity_date_time) = 2019 AND lfm.lead_family_member__28 = 1 AND ac.record_type_id = 3 AND a.activity_type_id IN (70, 137) AND a.is_deleted = 0 AND c.is_deleted = 0 ";
    CRM_Core_DAO::executeQuery($sql);
    $form->addToDeveloperTab($sql);
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_parent ON $tempTableName(parent_id)");
    return $tempTableName;
  }

  /**
  * Fetch new child whose is a 'Lead Family Member' + family has checked 'Does your child have an ASD diagnosis?'
  */
  public static function getNewChildContactTableName($from = NULL, $to = NULL, $leadFamilyMember = FALSE, &$form) {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => DIAGNOSIS_ON_FILE_CF_ID]);
    $DOFColumnName = $customField['column_name'];
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);

    $clauses = [];
    if ($leadFamilyMember) {
      $columnName = civicrm_api3('CustomField', 'getsingle', ['id' => LEAD_FAMILY_MEMBER_CF_ID])['column_name'];
      $clauses[] = " $columnName = 1 ";
    }
    else {
      $clauses = ["$DOFColumnName IS NOT NULL"];
      if ($from) {
        $from = substr($from, 0, 8);
        $clauses[] = "( {$DOFColumnName} >= {$from} )";
      }
      if ($to) {
        $to = ($type == CRM_Utils_Type::T_DATE) ? substr($to, 0, 8) : $to;
        $clauses[] = "( {$DOFColumnName} <= {$to} )";
      }
    }
    $whereClause = implode (' AND ', $clauses);

    $tempTableName = 'temp_newchild_contacts' . substr(sha1(rand()), 0, 7);

    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS ' . $tempTableName);
    $sql = "
      CREATE TEMPORARY TABLE $tempTableName DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
       SELECT DISTINCT ct.entity_id as new_child_id, rel.contact_id_b as parent_id, $DOFColumnName as dof
       FROM $customTableName ct
       INNER JOIN civicrm_relationship rel ON rel.contact_id_a = ct.entity_id AND rel.relationship_type_id = 1
       WHERE $whereClause
    ";
    CRM_Core_DAO::executeQuery($sql);
    $form->addToDeveloperTab($sql);
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_new_child ON $tempTableName(new_child_id)");
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_parent ON $tempTableName(parent_id)");

    return $tempTableName;
  }

  public static function getNewChildContactTableNameByRegion(&$form) {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => DIAGNOSIS_ON_FILE_CF_ID]);
    $DOFColumnName = $customField['column_name'];
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);

    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => EVENT_CHAPTER_REGION]);
    $CRColumnName = $customField['column_name'];
    $CRcustomTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);

    $tempTableName = 'temp_snp_activity' . substr(sha1(rand()), 0, 7);
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS ' . $tempTableName);
    $sql = "
    CREATE TEMPORARY TABLE $tempTableName DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
    SELECT DISTINCT ct.entity_id as new_child_id, r.contact_id_b as parent_id, $DOFColumnName as dof, $CRColumnName as region
    FROM $CRcustomTableName ec
    LEFT JOIN civicrm_event e ON e.id = ec.entity_id
    LEFT JOIN civicrm_participant p ON p.event_id =  e.id
    INNER JOIN civicrm_relationship r ON r.contact_id_b = p.contact_id AND r.relationship_type_id = 1
    INNER JOIN $customTableName ct ON ct.entity_id = r.contact_id_a AND ct.lead_family_member__28 = 1
    ";

    CRM_Core_DAO::executeQuery($sql);
    $form->addToDeveloperTab($sql);
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_parent ON $tempTableName(parent_id)");
    return $tempTableName;
  }

  public static function getSNPActivitybyMinistryRegion(&$form) {
    $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Service Navigation Provision');

    $tempTableName = 'temp_snp_activity' . substr(sha1(rand()), 0, 7);
    $sql = "
    CREATE TEMPORARY TABLE $tempTableName DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
    SELECT DISTINCT lfm.entity_id as new_child_id, r.contact_id_b as parent_id, DATE(ca.activity_date_time) as dof, service_region_776 as region, ca.status_id
    FROM
      civicrm_relationship r
      INNER JOIN civicrm_value_newsletter_cu_3 lfm ON r.contact_id_a = lfm.entity_id
      INNER JOIN civicrm_activity_contact cac ON cac.contact_id = r.contact_id_b
      INNER JOIN civicrm_contact c ON c.id = cac.contact_id
      INNER JOIN civicrm_activity ca ON ca.id = cac.activity_id
      LEFT JOIN civicrm_value_chapters_and__18 ct ON ct.entity_id = cac.contact_id

    WHERE YEAR(ca.activity_date_time) = 2019 AND r.relationship_type_id = 1 AND lfm.lead_family_member__28 = 1 AND ca.activity_type_id IN (70, 137) AND ca.is_deleted = 0 AND c.is_deleted = 0 AND cac.record_type_id = 3
    ";

    CRM_Core_DAO::executeQuery($sql);
    $form->addToDeveloperTab($sql);
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_parent ON $tempTableName(parent_id)");
    return $tempTableName;
  }

  public static function getNewChildFromClause($entityTable, $entityID = 'id') {
    list($customTableName, $customFieldName) = self::getnewChildTableAndColumn();
    return " LEFT JOIN {$customTableName} temp ON temp.entity_id = {$entityTable}.{$entityID} AND temp.{$customFieldName} = 1 ";
  }

  public static function getNewChildWhereClause($entityTable, $entityID = 'id', $op = 'IN') {
    list($customTableName, $customFieldName) = self::getnewChildTableAndColumn();
    return " {$entityTable}.{$entityID} {$op} (SELECT DISTINCT temp.entity_id FROM {$customTableName} temp WHERE {$customFieldName} = 1 ) ";
  }

  public static function getnewChildTableAndColumn() {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => LEAD_FAMILY_MEMBER_CF_ID]);
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);
    $customFieldName = $customField['column_name'];
    return [$customTableName, $customFieldName];
  }


}
