<?php

class CRM_AOReports_Form_Report_ExtendedBookkeeping extends CRM_Report_Form_Contribute_Bookkeeping {

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_batch']['fields']['batch_id'] = [
      'title' => ts('Batch ID'),
      'is_required' => TRUE,
      'no_display' => TRUE,
    ];
    $this->_columns['civicrm_batch']['filters']['prior_batch_date'] = [
      'title' => ts('Limit to auto batches?'),
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'type' => CRM_Utils_Type::T_INT,
      'dbAlias' => '(1)',
      'options' => [
        '0' => ts('No'),
        '1' => ts('Yes'),
      ],
    ];
    $this->_columns['civicrm_batch']['filters']['batch_id'] = [
      'name' => 'id',
      'dbAlias' => 'batch.id',
      'title' => ts('Batch title'),
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => CRM_Contribute_PseudoConstant::batch(),
    ];
    $this->_columns['civicrm_batch']['filters']['created_date'] = [
      'title' => ts('Batch Date'),
      'operatorType' => CRM_Report_Form::OP_DATE,
    ];
    $this->_columns['civicrm_financial_trxn']['filters']['payment_processor_id'] = [
      'name' => 'payment_processor_id',
      'dbAlias' => 'financial_trxn_civireport.payment_processor_id',
      'title' => ts('Payment Processor'),
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => [
        '1' => ts('Dummy'),
        '3' => ts('Moneris'),
      ],
    ];

    $this->_columns['civicrm_chapter_entity']['fields']['chapter_code_from'] = array(
      'name' => 'chapter_code_from',
      'title' => ts('Chapter Code - Credit'),
      'dbAlias' => 'CONCAT(ce_from.chapter_code, " ", covc_from.label)',
    );
    $this->_columns['civicrm_chapter_entity']['fields']['chapter_code_to'] = array(
      'name' => 'chapter_code_to',
      'title' => ts('Chapter Code - Debit'),
      'dbAlias' => 'CONCAT(ce_to.chapter_code, " ", covc_to.label)',
    );
    $this->_columns['civicrm_chapter_entity']['fields']['fund_code_from'] = array(
      'name' => 'fund_code_from',
      'title' => ts('Fund Code - Credit'),
      'dbAlias' => 'CONCAT(ce_from.fund_code, " ", covf_from.label)',
    );
    $this->_columns['civicrm_chapter_entity']['fields']['fund_code_to'] = array(
      'name' => 'fund_code_to',
      'title' => ts('Fund Code - Debit'),
      'dbAlias' => 'CONCAT(ce_to.fund_code, " ", covf_to.label)',
    );
    $this->_columns['civicrm_chapter_entity']['fields']['fund_id'] = array(
      'name' => 'fund_id',
      'title' => ts('Fund ID'),
      'dbAlias' => 'CASE
        WHEN financial_trxn_civireport.from_financial_account_id IS NOT NULL
        THEN  CONCAT(financial_account_civireport_credit_1.accounting_code, "-", ce_to.chapter_code)
        ELSE  CONCAT(financial_account_civireport_credit_2.accounting_code, "-", ce_to.chapter_code)
        END',
    );
  }

  public function from() {
    $this->_from = NULL;

    $this->_from = "FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                    ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                         {$this->_aliases['civicrm_contribution']}.is_test = 0
              LEFT JOIN civicrm_membership_payment payment
                    ON ( {$this->_aliases['civicrm_contribution']}.id = payment.contribution_id )
              LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                    ON payment.membership_id = {$this->_aliases['civicrm_membership']}.id
              LEFT JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}
                    ON ({$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}.entity_id AND
                        {$this->_aliases['civicrm_entity_financial_trxn']}.entity_table = 'civicrm_contribution')
              LEFT JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']}
                    ON {$this->_aliases['civicrm_financial_trxn']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}.financial_trxn_id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_debit
                    ON {$this->_aliases['civicrm_financial_trxn']}.to_financial_account_id = {$this->_aliases['civicrm_financial_account']}_debit.id
              LEFT JOIN civicrm_contact debit_contact ON {$this->_aliases['civicrm_financial_account']}_debit.contact_id = debit_contact.id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_1
                    ON {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_1.id
              LEFT JOIN civicrm_contact credit_contact_1 ON {$this->_aliases['civicrm_financial_account']}_credit_1.contact_id = credit_contact_1.id
              LEFT JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}_item
                    ON ({$this->_aliases['civicrm_financial_trxn']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.financial_trxn_id AND
                        {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_table = 'civicrm_financial_item')
              LEFT JOIN civicrm_financial_item fitem
                    ON fitem.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_2
                    ON fitem.financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_2.id
              LEFT JOIN civicrm_contact credit_contact_2 ON {$this->_aliases['civicrm_financial_account']}_credit_2.contact_id = credit_contact_2.id
              LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
                    ON  fitem.entity_id = {$this->_aliases['civicrm_line_item']}.id AND fitem.entity_table = 'civicrm_line_item'
              ";

    if ($this->isTableSelected('civicrm_batch')) {
      $this->_from .= "LEFT JOIN civicrm_entity_batch ent_batch
                    ON  {$this->_aliases['civicrm_financial_trxn']}.id = ent_batch.entity_id AND ent_batch.entity_table = 'civicrm_financial_trxn'
              LEFT JOIN civicrm_batch batch
                    ON  ent_batch.batch_id = batch.id
              LEFT JOIN civicrm_easybatch_entity ee
               ON batch.id = ee.batch_id
                    ";
    }
    $this->_from .= "
    LEFT JOIN civicrm_line_item li ON li.contribution_id = contribution_civireport.id
    LEFT JOIN civicrm_chapter_entity ce_from ON ce_from.entity_id = li.id AND ce_from.entity_table = 'civicrm_line_item'
    LEFT JOIN civicrm_chapter_entity ce_to ON ce_to.entity_id = financial_trxn_civireport.id AND ce_to.entity_table = 'civicrm_financial_trxn'
    LEFT JOIN civicrm_option_group cogf ON cogf.name = 'fund_codes'
    LEFT JOIN civicrm_option_group cogc ON cogc.name = 'chapter_codes'
    LEFT JOIN civicrm_option_value covf_from ON (covf_from.value = ce_from.fund_code AND covf_from.option_group_id = cogf.id)
    LEFT JOIN civicrm_option_value covf_to ON (covf_to.value = ce_to.fund_code AND covf_to.option_group_id = cogf.id)
    LEFT JOIN civicrm_option_value covc_from ON (covc_from.value = ce_from.chapter_code AND covc_from.option_group_id = cogc.id)
    LEFT JOIN civicrm_option_value covc_to ON (covc_to.value = ce_to.chapter_code AND covc_to.option_group_id = cogc.id)
    ";
  }



}
