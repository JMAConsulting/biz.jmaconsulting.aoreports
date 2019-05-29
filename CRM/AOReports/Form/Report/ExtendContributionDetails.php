<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_ExtendContributionDetails extends CRM_Report_Form_Contribute_Detail {

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_batch']['filters']['prior_batch_date'] = [
      'title' => ts('Auto batch for prior month?'),
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'type' => CRM_Utils_Type::T_INT,
      'dbAlias' => '(1)'
      'options' => [
        '0' => ts('No'),
        '1' => ts('Yes'),
      ],
    ];
  }

  /**
   * Append the joins that are required regardless of context.
   */
  public function appendAdditionalFromJoins() {
    if (!empty($this->_params['ordinality_value'])) {
      $this->_from .= "
              INNER JOIN (SELECT c.id, IF(COUNT(oc.id) = 0, 0, 1) AS ordinality FROM civicrm_contribution c LEFT JOIN civicrm_contribution oc ON c.contact_id = oc.contact_id AND oc.receive_date < c.receive_date GROUP BY c.id) {$this->_aliases['civicrm_contribution_ordinality']}
                      ON {$this->_aliases['civicrm_contribution_ordinality']}.id = {$this->_aliases['civicrm_contribution']}.id";
    }
    $this->joinPhoneFromContact();
    $this->joinAddressFromContact();
    $this->joinEmailFromContact();

    // include contribution note
    if (!empty($this->_params['fields']['contribution_note']) ||
      !empty($this->_params['note_value'])
    ) {
      $this->_from .= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                      ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_contribution' AND
                           {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_note']}.entity_id )";
    }
    //for contribution batches
    if (!empty($this->_params['fields']['batch_id']) ||
      !empty($this->_params['bid_value']) ||
      !empty($this->_params['prior_batch_date_value'])
    ) {
      $this->_from .= "
        LEFT JOIN civicrm_entity_financial_trxn eft
          ON eft.entity_id = {$this->_aliases['civicrm_contribution']}.id AND
            eft.entity_table = 'civicrm_contribution'
        LEFT JOIN civicrm_entity_batch {$this->_aliases['civicrm_batch']}
          ON ({$this->_aliases['civicrm_batch']}.entity_id = eft.financial_trxn_id
          AND {$this->_aliases['civicrm_batch']}.entity_table = 'civicrm_financial_trxn')";

        if (!empty($this->_params['prior_batch_date_value'])) {
          $this->_from .= "
            LEFT JOIN civicrm_easybatch_entity ee ON {$this->_aliases['civicrm_batch']}.id = ee.batch_id AND MONTH(ee.batch_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND YEAR(ee.batch_date) = YEAR(CURRENT_DATE)
          ";
        }
    }

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }


  public function where() {
    $this->storeWhereHavingClauseArray();

    if (!empty($this->_params['prior_batch_date_value'])) {
      $this->_whereClauses[] = "civicrm_easybatch_entity.id IS NOT NULL";

    }

    if (empty($this->_whereClauses)) {
      $this->_where = "WHERE ( 1 ) ";
      $this->_having = "";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $this->_whereClauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($this->_havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $this->_havingClauses);
    }
  }

}
