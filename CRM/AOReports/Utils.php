<?php

require_once __DIR__ . '../../../ao.variables.php';

class CRM_AOReports_Utils {

  /**
  * Fetch new child whose is a 'Lead Family Member' + family has checked 'Does your child have an ASD diagnosis?'
  */
  public static function getNewChildContactTableName() {
    $customField = civicrm_api3('CustomField', 'getsingle', ['id' => LEAD_FAMILY_MEMBER_CF_ID]);
    $columnName = $customField['column_name'];
    $customTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $customField['custom_group_id'], 'return' => 'table_name']);

    $parentCustomField = civicrm_api3('CustomField', 'getsingle', ['id' => ASD_CF_ID]);
    $parentColumnName = $customField['column_name'];
    $parentCustomTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => $parentCustomField['custom_group_id'], 'return' => 'table_name']);


    $tempTableName = 'temp_newchild_contacts';

    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS ' . $tempTableName);
    $sql = "
      CREATE TEMPORARY TABLE $tempTableName DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
       SELECT ct.entity_id as new_child_id, rel.contact_id_b as parent_id FROM $customTableName ct
       INNER JOIN civicrm_relationship rel ON rel.contact_id_a = ct.entity_id AND rel.relationship_type_id IN (1, 4)
       LEFT JOIN $parentCustomTableName pt ON pt.entity_id = rel.contact_id_b
       LEFT JOIN civicrm_activity_contact ac ON ac.contact_id = ct.entity_id
       LEFT JOIN civicrm_activity a ON a.id = ac.activity_id
       WHERE $columnName = 1 AND $parentColumnName = 1
    ";
    CRM_Core_DAO::executeQuery($sql);

    return $tempTableName;
  }

}
