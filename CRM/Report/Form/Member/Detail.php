<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Report_Form_Member_Detail extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = [
    'Membership',
    'Contribution',
    'Contact',
    'Individual',
    'Household',
    'Organization',
  ];

  protected $_customGroupGroupBy = FALSE;

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array_merge(
          $this->getBasicContactFields(),
          ['preferred_communication_method' => ['title' => ts('Preferred Communication Method'), 'alter_display' => 'alterCommunicationtMethod',]]
        ),
        'filters' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'operator' => 'like',
          ],
          'preferred_communication_method' => ['title' => ts('Preferred Communication Method')],
          'is_deleted' => [
            'title' => ts('Is Deleted'),
            'default' => 0,
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ],
          'id' => ['no_display' => TRUE],
        ],
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'membership_start_date' => [
            'title' => ts('Start Date'),
            'default' => TRUE,
          ],
          'membership_end_date' => [
            'title' => ts('End Date'),
            'default' => TRUE,
          ],
          'owner_membership_id' => [
            'title' => ts('Primary/Inherited?'),
            'default' => TRUE,
          ],
          'join_date' => [
            'title' => ts('Join Date'),
            'default' => TRUE,
          ],
          'source' => ['title' => ts('Source')],
          'is_auto_renew' => [
            'title' => ts('Auto Renew?'),
            'default' => 0,
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'dbAlias' => 'IF(ISNULL(membership_civireport.contribution_recur_id), 0, 1)',
          ],
          'group_id' => [
            'dbAlias' => 'group_temp_table.group_id',
            'title' => ts('Group(s)'),
          ],
        ],
        'filters' => [
          'membership_join_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'membership_start_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'membership_end_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'is_auto_renew' => [
            'title' => ts('Auto Renew?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'dbAlias' => 'membership_civireport.contribution_recur_id',
          ],
          'owner_membership_id' => [
            'title' => ts('Primary Membership'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'tid' => [
            'name' => 'membership_type_id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ],
          'group_id' => [
            'name' => 'group_id',
            'title' => ts('Group(s)'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::nestedGroup(),
          ],
        ],
        'order_bys' => [
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'default' => '0',
            'default_weight' => '1',
            'default_order' => 'ASC',
          ],
        ],
        'grouping' => 'member-fields',
        'group_bys' => [
          'id' => [
            'title' => ts('Membership'),
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_membership_status' => [
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' => [
          'name' => [
            'title' => ts('Status'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'sid' => [
            'name' => 'id',
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ],
        ],
        'grouping' => 'member-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => ['phone' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contribution_id' => [
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'financial_type_id' => ['title' => ts('Financial Type')],
          'contribution_status_id' => ['title' => ts('Contribution Status')],
          'payment_instrument_id' => ['title' => ts('Payment Type')],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'trxn_id' => NULL,
          'receive_date' => NULL,
          'receipt_date' => NULL,
          'fee_amount' => NULL,
          'net_amount' => NULL,
          'total_amount' => [
            'title' => ts('Payment Amount (most recent)'),
            'statistics' => ['sum' => ts('Amount')],
          ],
        ],
        'filters' => [
          'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'payment_instrument_id' => [
            'title' => ts('Payment Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'total_amount' => ['title' => ts('Contribution Amount')],
        ],
        'order_bys' => [
          'receive_date' => [
            'title' => ts('Date Received'),
            'default_weight' => '2',
            'default_order' => 'DESC',
          ],
        ],
        'grouping' => 'contri-fields',
      ],
    ] + $this->getAddressColumns([
      // These options are only excluded because they were not previously present.
      'order_by' => FALSE,
      'group_by' => FALSE,
    ]);
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    // If we have campaigns enabled, add those elements to both the fields, filters and sorting
    $this->addCampaignFields('civicrm_membership', FALSE, TRUE);

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
    unset($this->_columns['civicrm_group']['filters']['gid']);
  }

  public function preProcess() {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
  }

  public function from() {
    $this->setFromBase('civicrm_contact');
    $this->_from .= "
         {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id
                             ";

   if (!empty($this->_params['fields']['group_id']) || !empty($this->_params['group_id_value']) || in_array($this->_params['group_id_op'], ['nll', 'nnll'])) {
     $side = in_array($this->_params['group_id_op'], ['in', 'nnll']) ? 'INNER' : 'LEFT';
     $groupTable = $this->buildGroupTempTable();
     if ($groupTable) {
       $this->_from .= "
       $side JOIN $groupTable group_temp_table ON {$this->_aliases['civicrm_contact']}.id = group_temp_table.contact_id ";
     }
   }


    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    //used when contribution field is selected.
    if ($this->isTableSelected('civicrm_contribution')) {
      $this->_from .= "
             LEFT JOIN civicrm_membership_payment cmp
                 ON {$this->_aliases['civicrm_membership']}.id = cmp.membership_id
             LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                 ON cmp.contribution_id={$this->_aliases['civicrm_contribution']}.id\n";
    }
  }

  public function getOperationPair($type = "string", $fieldName = NULL) {
    //re-name IS NULL/IS NOT NULL for clarity
    if ($fieldName == 'owner_membership_id') {
      $result = [];
      $result['nll'] = ts('Primary members only');
      $result['nnll'] = ts('Non-primary members only');
      $options = parent::getOperationPair($type, $fieldName);
      foreach ($options as $key => $label) {
        if (!array_key_exists($key, $result)) {
          $result[$key] = $label;
        }
      }
    }
    else {
      $result = parent::getOperationPair($type, $fieldName);
    }
    return $result;
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          if ($field['name'] == 'group_id') {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op == 'nll' ) {
              $clauses[] = " group_temp_table.contact_id IS NULL ";
            }
            elseif ($op == 'notin') {
              $groupTable = $this->buildGroupTempTable(TRUE);
              if ($groupTable) {
                $clauses[] = " (group_temp_table.group_id IS NULL OR group_temp_table.contact_id NOT IN (SELECT contact_id FROM $groupTable ))";
              }
            }
            continue;
          }
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $this->_dateClause = $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          elseif ($field['name'] == 'is_auto_renew') {
            if ($this->_params['is_auto_renew_value'] === 0 || $this->_params['is_auto_renew_value'] === 1) {
              $clause = $this->_params['is_auto_renew_value'] == 1 ? "{$this->_aliases['civicrm_membership']}.contribution_recur_id IS NOT NULL" : "{$this->_aliases['civicrm_membership']}.contribution_recur_id IS NULL";
            }
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
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $checkList = [];

    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

    $repeatFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      if ($repeatFound == FALSE ||
        $repeatFound < $rowNum - 1
      ) {
        unset($checkList);
        $checkList = [];
      }
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        foreach ($row as $colName => $colVal) {
          if (in_array($colName, $this->_noRepeats) &&
            $rowNum > 0
          ) {
            if ($rows[$rowNum][$colName] == $rows[$rowNum - 1][$colName] ||
              (!empty($checkList[$colName]) &&
              in_array($colVal, $checkList[$colName]))
              ) {
              $rows[$rowNum][$colName] = "";
              // CRM-15917: Don't blank the name if it's a different contact
              if ($colName == 'civicrm_contact_exposed_id') {
                $rows[$rowNum]['civicrm_contact_sort_name'] = "";
              }
              $repeatFound = $rowNum;
            }
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_membership_owner_membership_id', $row)) {
        $value = $row['civicrm_membership_owner_membership_id'];
        $rows[$rowNum]['civicrm_membership_owner_membership_id'] = ($value != '') ? 'Inherited' : 'Primary';
        $entryFound = TRUE;
      }

      // Convert campaign_id to campaign title
      if (array_key_exists('civicrm_membership_campaign_id', $row)) {
        if ($value = $row['civicrm_membership_campaign_id']) {
          $rows[$rowNum]['civicrm_membership_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }
      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'member/detail', 'List all memberships(s) for this ') ? TRUE : $entryFound;
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'member/detail', 'List all memberships(s) for this ') ? TRUE : $entryFound;

      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * Create a table of the contact ids included by the group filter.
   *
   * This function is called by both the api (tests) and the UI.
   */
  public function buildGroupTempTable($override = FALSE) {
    $filteredGroups = (array) $this->_params['group_id_value'];
    $op = $this->_params['group_id_op'];

    if (in_array($op, ['in', 'notin']) && !empty($filteredGroups)) {
      $op = $op == 'in' || $override ? 'IN' : 'NOT IN';
      $where = sprintf(' group_contact.group_id %s (%s)', $op, implode(', ', $filteredGroups));
    }
    elseif (!empty($this->_params['fields']['group_id'])) {
      $where = '(1)';
    }
    else {
      return FALSE;
    }

    $groupFieldSelect = (!$override && !empty($this->_params['fields']['group_id']) && $op == 'IN');

    $whereUsed = $groupFieldSelect ? '(1)' : $where;

    $query = "
       SELECT DISTINCT group_contact.contact_id,  GROUP_CONCAT(DISTINCT g.title) as group_id
       FROM civicrm_group_contact group_contact
       INNER JOIN civicrm_group g ON g.id = group_contact.group_id
       INNER JOIN civicrm_membership cm ON cm.contact_id = group_contact.contact_id
       WHERE $whereUsed
       AND group_contact.status = 'Added'
       GROUP BY group_contact.contact_id

      UNION DISTINCT

      SELECT group_contact.contact_id, GROUP_CONCAT(DISTINCT g.title) as group_id
      FROM civicrm_group_contact_cache group_contact
      INNER JOIN civicrm_group g ON g.id = group_contact.group_id
      INNER JOIN civicrm_membership cm ON cm.contact_id = group_contact.contact_id
      WHERE $whereUsed
      GROUP BY group_contact.contact_id
       ";
    $query = CRM_Core_I18n_Schema::rewriteQuery($query);

    $groupTempTable = $this->createTemporaryTable('rptgrp', $query);
    CRM_Core_DAO::executeQuery("ALTER TABLE $groupTempTable ADD INDEX i_id(contact_id)");

    if ($groupFieldSelect && !empty($filteredGroups) && !$override) {
      $excludeQuery = str_replace('(1)', $where, $query);
      CRM_Core_DAO::executeQuery("
      DELETE FROM $groupTempTable WHERE contact_id NOT IN (
        SELECT DISTINCT contact_id
        FROM ( $excludeQuery ) temp
      ) ");
    }

    return $groupTempTable;
  }

}
