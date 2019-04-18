<?php

class CRM_AOReports_Form_Report_ExtendedParticipantListing extends CRM_Report_Form_Event_ParticipantListing {
  public function __construct() {
    parent::__construct();
      $this->_columns['civicrm_line_item']['fields'] = [
        'line_item_label' => [
            'no_display' => TRUE,
            'required' => TRUE,
            'dbAlias' => "GROUP_CONCAT(CONCAT(pf.label, ' - ', participant_count))",
          ],
        ];
  }

  public function from() {
    $this->_from = "
        FROM civicrm_participant {$this->_aliases['civicrm_participant']}
             LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']}
                    ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND
                        {$this->_aliases['civicrm_event']}.is_template = 0
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                    ON ({$this->_aliases['civicrm_participant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id  )
             {$this->_aclFrom}
      ";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    // Include participant note.
    if ($this->isTableSelected('civicrm_note')) {
      $this->_from .= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                   ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_participant' AND
                   {$this->_aliases['civicrm_participant']}.id = {$this->_aliases['civicrm_note']}.entity_id )";
    }

    if ($this->_contribField) {
      $this->_from .= "
             LEFT JOIN civicrm_participant_payment pp
                    ON ({$this->_aliases['civicrm_participant']}.id  = pp.participant_id)
             LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                    ON (pp.contribution_id  = {$this->_aliases['civicrm_contribution']}.id)
      ";
    }
    $this->_from .= "
          LEFT JOIN civicrm_line_item line_item_civireport
                ON line_item_civireport.entity_table = 'civicrm_participant' AND
                   line_item_civireport.entity_id = {$this->_aliases['civicrm_participant']}.id AND
                   line_item_civireport.qty > 0
         LEFT JOIN civicrm_price_field pf ON pf.id = line_item_civireport.price_field_id
    ";

    if ($this->_balance) {
      $this->_from .= "
            LEFT JOIN civicrm_entity_financial_trxn eft
                  ON (eft.entity_id = {$this->_aliases['civicrm_contribution']}.id)
            LEFT JOIN civicrm_financial_trxn ft
                  ON (ft.id = eft.financial_trxn_id AND eft.entity_table = 'civicrm_contribution') AND
                     (ft.is_payment = 1)
      ";
    }
  }

  public function statistics(&$rows) {

    $statistics = parent::statistics($rows);
    $select = " SELECT SUM( {$this->_aliases['civicrm_line_item']}.participant_count ) as count ";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      $statistics['counts']['count'] = [
        'value' => $dao->count,
        'title' => ts('Total Participants'),
        'type' => CRM_Utils_Type::T_INT,
      ];
    }

    return $statistics;
  }



}
