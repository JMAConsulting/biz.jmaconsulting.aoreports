<?php

class CRM_AOReports_Form_Report_ExtendedBatchDetail extends CRM_Report_Form_Contribute_BatchDetail {
  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_financial_trxn']['fields']['payment_id']['no_display'] = TRUE;
    $this->_columns['civicrm_financial_trxn']['fields']['card_type_id']['dbAlias'] = 'GROUP_CONCAT(DISTINCT financial_trxn_civireport.card_type_id)';
    $this->_columns['civicrm_easybatch_entity']['filters']['payment_processor_id'] = [
      'name' => 'payment_processor_id',
      'dbAlias' => 'easybatch_entity_civireport.payment_processor_id',
      'title' => ts('Payment Processor'),
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => [
        '1' => ts('Dummy Processor'),
        '3' => ts('Moneris'),
      ],
    ];
    $this->_columns['civicrm_easybatch_entity']['filters']['is_automatic'] = [
      'title' => ts('Is Auto Batch?'),
      'operatorType' => CRM_Report_Form::OP_SELECT,
      'type' => CRM_Utils_Type::T_INT,
      'options' => [
        '0' => ts('No'),
        '1' => ts('Yes'),
      ],
    ];
    unset($this->_columns['civicrm_contribution']['fields']['invoice_id']);
  }

  public function select() {
    $select = array();

    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            switch ($fieldName) {
              case 'credit_accounting_code':
              case 'credit_name':
                $select[] = " CASE
                            WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
                            THEN  {$this->_aliases['civicrm_financial_account']}_credit_1.{$field['name']}
                            ELSE  {$this->_aliases['civicrm_financial_account']}_credit_2.{$field['name']}
                            END AS civicrm_financial_account_{$fieldName} ";
                break;

              case 'amount':
              case 'debit_amount':
                $select[] = " CASE
                            WHEN  ceft1.entity_id IS NOT NULL
                            THEN ceft1.amount
                            ELSE ceft.amount
                            END AS civicrm_entity_financial_trxn_{$fieldName} ";
                break;

              case 'credit_contact_id':
                $select[] = " CASE
                            WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
                            THEN  credit_contact_1.{$field['name']}
                            ELSE  credit_contact_2.{$field['name']}
                            END AS civicrm_financial_account_{$fieldName} ";
                break;

              default:
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                break;
            }
            if (!$field['no_display']) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }
    // Rearrange select clause
    $order = [
      'civicrm_batch_batch_id',
      'civicrm_contribution_invoice_id',
      'civicrm_contribution_contact_id',
      'civicrm_financial_trxn_payment_id',
      'civicrm_financial_trxn_trxn_date',
      'civicrm_financial_account_debit_accounting_code',
      'civicrm_financial_account_debit_name',
      'civicrm_financial_account_debit_account_type_code',
      'civicrm_financial_trxn_total_amount',
      'civicrm_financial_trxn_trxn_id',
      'civicrm_entity_financial_trxn_debit_amount',
      'civicrm_financial_trxn_payment_instrument_id',
      'civicrm_financial_trxn_check_number',
      'civicrm_contribution_source',
      'civicrm_financial_trxn_currency',
      'civicrm_financial_trxn_status_id',
      'civicrm_entity_financial_trxn_amount',
      'civicrm_financial_account_credit_accounting_code',
      'civicrm_financial_account_credit_name',
      'civicrm_financial_account_credit_account_type_code',
      'civicrm_financial_item_description',
    ];
    $this->_columnHeaders = array_replace(array_flip($order), $this->_columnHeaders);
    unset($this->_columnHeaders['civicrm_contribution_contribution_id']);
    $this->_selectClauses = $select;

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
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
        $url = CRM_Utils_System::url("civicrm/report/instance/75?", 'force=1&batch_id_value=' . $value);
        $rows[$rowNum]['civicrm_batch_batch_id'] = "<a target='_blank' href=\"$url\">$value</a>";
        $rows[$rowNum]['civicrm_batch_batch_id_hover'] = ts('View Details of Batch transactions.');
      }
      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $ct = [];
        $row['civicrm_financial_trxn_card_type_id'] = explode(',', $row['civicrm_financial_trxn_card_type_id']);
        foreach ($row['civicrm_financial_trxn_card_type_id'] as $cardType) {
          if ($cardType == '') continue;
          $ct = $this->getLabels($cardType, 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        }
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = implode(', ', $ct);
        $entryFound = TRUE;
      }
    }
  }

}
