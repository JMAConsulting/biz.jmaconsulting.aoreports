<?php

require_once __DIR__ . '../../../ao.variables.php';

class CRM_AOReports_Utils {

  /**
  * Fetch new child whose is a 'Lead Family Member' + family has checked 'Does your child have an ASD diagnosis?'
  */
  public static function getNewChildContactTableName($from = NULL, $to = NULL, $leadFamilyMember = FALSE) {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => DIAGNOSIS_ON_FILE_CF_ID])['column_name'];
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

    $tempTableName = 'temp_newchild_contacts';

    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS ' . $tempTableName);
    $sql = "
      CREATE TEMPORARY TABLE $tempTableName DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
       SELECT DISTINCT ct.entity_id as new_child_id, rel.contact_id_b as parent_id, $DOFColumnName as dof
       FROM $customTableName ct
       INNER JOIN civicrm_relationship rel ON rel.contact_id_a = ct.entity_id AND rel.relationship_type_id IN (1, 4)
       LEFT JOIN civicrm_activity_contact ac ON ac.contact_id = ct.entity_id
       LEFT JOIN civicrm_activity a ON a.id = ac.activity_id
       WHERE $whereClause
    ";
    CRM_Core_DAO::executeQuery($sql);
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_new_child ON $tempTableName(new_child_id)");
    CRM_Core_DAO::executeQuery("CREATE INDEX ind_parent ON $tempTableName(parent_id)");

    return $tempTableName;
  }

}
