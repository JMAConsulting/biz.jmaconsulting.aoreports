<?php

class CRM_AOReports_Form_Report_ExtendedBatchDetail extends CRM_Report_Form_Contribute_BatchDetail {
  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_chapter_entity']['fields']['chapter_code_from'] = array(
      'name' => 'chapter_code_from',
      'title' => ts('Chapter Code - Credit'),
      'dbAlias' => 'CONCAT(ce_from.chapter_code, " ", covc_from.label)',
      'is_default' => TRUE,
    );
    $this->_columns['civicrm_chapter_entity']['fields']['chapter_code_to'] = array(
      'name' => 'chapter_code_to',
      'title' => ts('Chapter Code - Debit'),
      'dbAlias' => 'CONCAT(ce_to.chapter_code, " ", covc_to.label)',
      'is_default' => TRUE,
    );
    $this->_columns['civicrm_chapter_entity']['fields']['fund_code_from'] = array(
      'name' => 'fund_code_from',
      'title' => ts('Fund Code - Credit'),
      'dbAlias' => 'CONCAT(ce_from.fund_code, " ", covf_from.label)',
      'is_default' => TRUE,
    );
    $this->_columns['civicrm_chapter_entity']['fields']['fund_code_to'] = array(
      'name' => 'fund_code_to',
      'title' => ts('Fund Code - Debit'),
      'dbAlias' => 'CONCAT(ce_to.fund_code, " ", covf_to.label)',
      'is_default' => TRUE,
    );
    $this->_columns['civicrm_chapter_entity']['fields']['fund_id'] = array(
      'name' => 'fund_id',
      'title' => ts('Fund ID'),
      'dbAlias' => 'CASE
        WHEN financial_trxn_civireport.from_financial_account_id IS NOT NULL
        THEN  CONCAT(financial_account_civireport_credit_1.accounting_code, "-", ce_to.chapter_code)
        ELSE  CONCAT(financial_account_civireport_credit_2.accounting_code, "-", ce_to.chapter_code)
        END',
      'is_default' => TRUE,
    );
  }

  public function groupBy() {
    $groupBy = [
      "{$this->_aliases['civicrm_batch']}.id",
    ];
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  public function alterDisplay(&$rows) {
    $prefixValue = Civi::settings()->get('contribution_invoice_settings');
    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('civicrm_contribution_contribution_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_invoice_id'] = CRM_Utils_Array::value('invoice_prefix', $prefixValue) . "" . $row['civicrm_contribution_contribution_id'];
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_batch_batch_id', $row)) {
        $value = $row['civicrm_batch_batch_id'];
        $url = CRM_Utils_System::url("civicrm/biz.jmaconsulting.aoreports:extendedbookkeeping?", 'force=1&batch_id_value=' . $value);
        $rows[$rowNum]['civicrm_batch_batch_id'] = "<a target='_blank' href=\"$url\">$value</a>";
        $rows[$rowNum]['civicrm_batch_batch_id_hover'] = ts('View Details of Batch transactions.');
      }
      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }
    }
  }

}
