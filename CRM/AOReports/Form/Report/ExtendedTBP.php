<?php

class CRM_AOReports_Form_Report_ExtendedTBP extends CRM_CloseAccountingPeriod_Form_Report_TrialBalance {

  public function from() {

    $endDate = NULL;
    $contactID = $this->_params['contact_id_value'];
    $fieldName = 'trxn_date';
    $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
    $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
    $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
    list($from, $to) = $this->getFromTo($relative, $from, $to, NULL, NULL);
    $clauses = ['(1)'];
    if ($from) {
      $clauses[] = "( fieldName >= $from )";
    }
    if ($to) {
      $clauses[] = "( fieldName <= {$to} )";
    }
    if (!empty($clauses)) {
      $clauses =  implode(' AND ', $clauses);
    }
    $params['labelColumn'] = 'name';
    $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $financialAccountTypes = array(
      array_search('Liability', $financialAccountType),
      array_search('Asset', $financialAccountType)
    );
    $priorDate = NULL;
    if ($contactID) {
      $priorDate = CRM_CloseAccountingPeriod_BAO_CloseAccountingPeriod::getPriorFinancialPeriod($contactID);
    }
    if (empty($priorDate)) {
      $financialBalanceField = 'opening_balance';
    }
    else {
      $financialBalanceField = 'current_period_opening_balance';
    }

    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS financial_trxn_civireport');
    $sql = "CREATE TEMPORARY TABLE financial_trxn_civireport (
      id int PRIMARY KEY AUTO_INCREMENT,
      fid int,
      credit float(10,2),
      debit float(10,2),
      financial_account_id int,
      chapter_from varchar(64),
      chapter_to varchar(64),
      fund_from varchar(64),
      fund_to varchar(64)
    ) ENGINE=InnoDB";

    $sql = "
    INSERT IGNORE INTO financial_trxn_civireport(id, fid, credit, debit, financial_account_id, chapter_from, chapter_to, fund_from, fund_to)
    SELECT cft1.id, 0 as fid, 0 AS credit, cft1.total_amount AS debit,
      cft1.to_financial_account_id AS financial_account_id,
      '' AS chapter_from, ce.chapter_code AS chapter_to, '' AS fund_from, ce.fund_code AS fund_to
      FROM civicrm_financial_trxn cft1
      LEFT JOIN civicrm_chapter_entity ce ON ce.entity_id = cft1.id AND ce.entity_table = 'civicrm_financial_trxn'
      WHERE $clauses
    ";
    $sql = str_replace('fieldName', 'cft1.trxn_date', $sql);
    CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);

    $sql = "
    INSERT IGNORE INTO financial_trxn_civireport(id, fid, credit, debit, financial_account_id, chapter_from, chapter_to, fund_from, fund_to)
    SELECT cft2.id, 0 as fid, cft2.total_amount AS credit, 0 AS debit, cft2.from_financial_account_id,
    ce1.chapter_code AS chapter_from, '' AS chapter_to, ce1.fund_code AS fund_from, '' AS fund_to
      FROM civicrm_financial_trxn cft2
      LEFT JOIN civicrm_entity_financial_trxn ceft1 ON ceft1.financial_trxn_id = cft2.id AND entity_table = 'civicrm_contribution'
      LEFT JOIN civicrm_line_item li ON li.contribution_id = ceft1.entity_id
      LEFT JOIN civicrm_chapter_entity ce1 ON ce1.entity_id = li.id AND ce1.entity_table = 'civicrm_line_item'
      WHERE $clauses
    ";
    $sql = str_replace('fieldName', 'cft2.trxn_date', $sql);
    CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);

    $sql = "
    INSERT IGNORE INTO financial_trxn_civireport(id, fid, credit, debit, financial_account_id, chapter_from, chapter_to, fund_from, fund_to)
    SELECT cft3.id, cfi3.id, 0 AS credit, cfi3.amount AS debit, cfi3.financial_account_id,
    '' AS chapter_from, ce2.chapter_code AS chapter_to, '' AS fund_from, ce2.fund_code AS fund_to
      FROM civicrm_financial_item cfi3
        INNER JOIN civicrm_entity_financial_trxn ceft3 ON cfi3.id = ceft3.entity_id
          AND ceft3.entity_table = 'civicrm_financial_item'
        INNER JOIN civicrm_financial_trxn cft3 ON ceft3.financial_trxn_id = cft3.id
          AND cft3.to_financial_account_id IS NULL
          LEFT JOIN civicrm_chapter_entity ce2 ON ce2.entity_id = cft3.id AND ce2.entity_table = 'civicrm_financial_trxn'
      WHERE $clauses
    ";
    $sql = str_replace('fieldName', 'cft3.trxn_date', $sql);
    CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);

    $sql = "
    INSERT IGNORE INTO financial_trxn_civireport(id, fid, credit, debit, financial_account_id, chapter_from, chapter_to, fund_from, fund_to)
    SELECT cft4.id, cfi4.id,  cfi4.amount AS credit, 0 AS debit, cfi4.financial_account_id,
    ce3.chapter_code AS chapter_from, '' AS chapter_to, ce3.fund_code AS fund_from, '' AS fund_to
      FROM civicrm_financial_item cfi4
      INNER JOIN civicrm_entity_financial_trxn ceft4 ON cfi4.id=ceft4.entity_id
        AND ceft4.entity_table='civicrm_financial_item'
      INNER JOIN civicrm_financial_trxn cft4 ON ceft4.financial_trxn_id=cft4.id
        AND cft4.from_financial_account_id IS NULL
        LEFT JOIN civicrm_chapter_entity ce3 ON ce3.entity_id = cfi4.id AND ce3.entity_table = 'civicrm_financial_item'
      WHERE $clauses
    ";
    $sql = str_replace('fieldName', 'cft4.trxn_date', $sql);
    CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);

    $sql = "
    INSERT IGNORE INTO financial_trxn_civireport(id, fid, credit, debit, financial_account_id, chapter_from, chapter_to, fund_from, fund_to)
    SELECT 0 as tid, 0 as fid, IF (financial_account_type_id = " . array_search('Liability', $financialAccountType) . ", {$financialBalanceField}, 0) AS credit, IF (financial_account_type_id = " . array_search('Asset', $financialAccountType) . ", {$financialBalanceField}, 0) AS debit, cfa5.id,
              IF (financial_account_type_id = " . array_search('Liability', $financialAccountType) . ", cec.chapter_code, '') AS chapter_from,
              IF (financial_account_type_id = " . array_search('Asset', $financialAccountType) . ", ced.chapter_code, '') AS chapter_to,
              IF (financial_account_type_id = " . array_search('Liability', $financialAccountType) . ", cec.fund_code, '') AS fund_from,
              IF (financial_account_type_id = " . array_search('Asset', $financialAccountType) . ", ced.fund_code, '') AS fund_to
      FROM civicrm_financial_account cfa5
      INNER JOIN civicrm_financial_accounts_balance cfab ON cfab.financial_account_id = cfa5.id
      LEFT JOIN civicrm_chapter_entity cec ON cec.entity_id = cfa5.id AND cec.entity_table = 'civicrm_financial_item'
      INNER JOIN civicrm_entity_financial_trxn ceft5 ON cfa5.id = ceft5.entity_id AND ceft5.entity_table = 'civicrm_financial_item'
      LEFT JOIN civicrm_chapter_entity ced ON ced.entity_id = ceft5.financial_trxn_id AND ced.entity_table = 'civicrm_financial_trxn'
      WHERE cfa5.financial_account_type_id IN (" . implode(', ', $financialAccountTypes) . ") AND {$financialBalanceField} <> 0
    ";
    CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);


    $sql = "
    FROM financial_trxn_civireport
        INNER JOIN civicrm_financial_account financial_account_civireport ON financial_trxn_civireport.financial_account_id = financial_account_civireport.id
    ";
    $this->_from = $sql;
  }


}
