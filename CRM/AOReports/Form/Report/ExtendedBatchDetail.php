<?php

class CRM_AOReports_Form_Report_ExtendedBatchDetail extends CRM_Report_Form_Contribute_BatchDetail {
    
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
