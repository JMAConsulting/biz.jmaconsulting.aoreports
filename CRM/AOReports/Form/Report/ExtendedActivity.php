<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_ExtendedActivity extends CRM_Report_Form_Activity {

  /**
   * @param $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignee', $activityContacts);

    //Assign those recordtype to array which have filter operator as 'Is not empty' or 'Is empty'
    $nullFilters = array();
    foreach (array('target', 'source', 'assignee') as $type) {
      if (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) ==
        'nnll' || !empty($this->_params["contact_{$type}_value"])
      ) {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NOT NULL ";
      }
      elseif (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) ==
        'nll'
      ) {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NULL ";
      }
    }

    // @todo - all this temp table stuff is here because pre 4.4 the activity contact
    // form did not exist.
    // Fixing the way the construct method declares them will make all this redundant.
    // 1. fill temp table with target results
    $this->buildACLClause(array('civicrm_contact_target'));
    $this->select('target');
    $this->from();
    $this->customDataFrom();
    $this->where('target');
    $tempTableName = $this->createTemporaryTable('activity_temp_table', "{$this->_select} {$this->_from} {$this->_where}");

    // 2. add new columns to hold assignee and source results
    // fixme: add when required
    $tempQuery = "
  ALTER TABLE  $tempTableName
  MODIFY COLUMN civicrm_contact_contact_target_id VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_assignee VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_assignee_id VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source_id VARCHAR(128),
  ADD COLUMN civicrm_phone_contact_assignee_phone VARCHAR(128),
  ADD COLUMN civicrm_phone_contact_source_phone VARCHAR(128),
  ADD COLUMN civicrm_email_contact_assignee_email VARCHAR(128),
  ADD COLUMN civicrm_email_contact_source_email VARCHAR(128)";
    $this->executeReportQuery($tempQuery);

    // 3. fill temp table with assignee results
    $this->buildACLClause(array('civicrm_contact_assignee'));
    $this->select('assignee');
    $this->buildAssigneeFrom();

    $this->customDataFrom();
    $this->where('assignee');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery = "INSERT INTO $tempTableName ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    $this->executeReportQuery($tempQuery);

    // 4. fill temp table with source results
    $this->buildACLClause(array('civicrm_contact_source'));
    $this->select('source');
    $this->buildSourceFrom();
    $this->customDataFrom();
    $this->where('source');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery = "INSERT INTO $tempTableName ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    $this->executeReportQuery($tempQuery);

    // 5. show final result set from temp table
    $rows = array();
    $this->select('final');
    $this->_having = "";
    if (!empty($nullFilters)) {
      $this->_having = "HAVING " . implode(' AND ', $nullFilters);
    }
    $this->orderBy();
    foreach ($this->_sections as $alias => $section) {
      if (!empty($section) && $section['name'] == 'activity_date_time') {
        $this->alterSectionHeaderForDateTime($tempTableName, $section['tplField']);
      }
    }

    if ($applyLimit) {
      $this->limit();
    }

    $groupByFromSelect = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, 'civicrm_activity_id');

    $this->_where = " WHERE (1)";
    $this->buildPermissionClause();
    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
    $contactID = CRM_Core_Session::singleton()->get('userID');

    $sql = "{$this->_select}
      FROM $tempTableName tar
      INNER JOIN civicrm_activity {$this->_aliases['civicrm_activity']} ON {$this->_aliases['civicrm_activity']}.id = tar.civicrm_activity_id
      INNER JOIN civicrm_activity_contact {$this->_aliases['civicrm_activity_contact']} ON {$this->_aliases['civicrm_activity_contact']}.activity_id = {$this->_aliases['civicrm_activity']}.id
      AND {$this->_aliases['civicrm_activity_contact']}.record_type_id = 1
      LEFT JOIN civicrm_contact contact_civireport ON contact_civireport.id = {$this->_aliases['civicrm_activity_contact']}.contact_id
      {$this->_where} AND contact_id = {$contactID} {$groupByFromSelect} {$this->_having} {$this->_orderBy} {$this->_limit}";

    CRM_Utils_Hook::alterReportVar('sql', $this, $this);
    $this->addToDeveloperTab($sql);

    return $sql;
  }

}
