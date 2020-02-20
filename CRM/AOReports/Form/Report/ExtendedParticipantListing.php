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

  public function from($includeLineItem = TRUE) {
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
    if ($includeLineItem) {
      $this->_from .= "
            LEFT JOIN civicrm_line_item line_item_civireport
                  ON line_item_civireport.entity_table = 'civicrm_participant' AND
                     line_item_civireport.entity_id = {$this->_aliases['civicrm_participant']}.id AND
                     line_item_civireport.qty > 0
           LEFT JOIN civicrm_price_field pf ON pf.id = line_item_civireport.price_field_id
      ";
    }

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
    $labels = [];
    $totalParticipant = 0;
    $select = " SELECT CONCAT(pf.label, ' - ', SUM(participant_count)) as c,  SUM(participant_count) as count ";
    $sql = "{$select} {$this->_from} {$this->_where} GROUP BY line_item_civireport.price_field_id ";
    $extensionInfo = civicrm_api3('Extension', 'get', ['key' => 'biz.jmaconsulting.waitlisttickets']);
    $pendingFromWaitlistStatus = CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'participant_status_id', 'Pending from waitlist');
    $onWaitListStatus = CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'participant_status_id', 'On waitlist');
    if (!empty($extensionInfo['values']) && $extensionInfo['values'][$extensionInfo['id']]['status'] === 'installed' && (in_array($pendingFromWaitlistStatus, $this->_formValues['sid_value']) || in_array($onWaitListStatus, $this->_formValues['sid_value']))) {
      $waitListWhere = str_replace('line_item_civireport', 'wait_list_tickets', $this->_where);
      $this->from(FALSE);
      $waitListFrom  = $this->_from . " LEFT JOIN civicrm_wait_list_tickets as wait_list_tickets ON wait_list_tickets.participant_id = participant_civireport.id LEFT JOIN civicrm_price_field pf ON pf.id = wait_list_tickets.price_field_id ";
      $sql .= "UNION SELECT CONCAT(pf.label, ' - ', SUM(wait_list_tickets.participant_count)) as c,  SUM(wait_list_tickets.participant_count) as count {$waitListFrom} {$waitListWhere} GROUP BY wait_list_tickets.price_field_id";
    }
    $this->addToDeveloperTab($sql);
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (empty($dao->count)) {
        continue;
      }
      $totalParticipant += (int) $dao->count;
      $labels[] = str_replace('# of', '', $dao->c);
    }

    $statistics['counts']['count'] = [
      'value' => $totalParticipant . sprintf('(%s)', implode(', ', $labels)),
      'title' => ts('Total Participants'),
      'type' => CRM_Utils_Type::T_STRING,
    ];

    return $statistics;
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
    $eventType = CRM_Core_OptionGroup::values('event_type');
    $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      // convert display name to links
      if (array_key_exists('civicrm_participant_event_id', $row)) {
        $eventId = $row['civicrm_participant_event_id'];
        if ($eventId) {
          $rows[$rowNum]['civicrm_participant_event_id'] = CRM_Event_PseudoConstant::event($eventId, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('event/income',
            'reset=1&force=1&id_op=in&id_value=' . $eventId,
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );
          $rows[$rowNum]['civicrm_participant_event_id_link'] = $url;
          $rows[$rowNum]['civicrm_participant_event_id_hover'] = ts("View Event Income Details for this Event");
        }
        $entryFound = TRUE;
      }

      // handle event type id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_event_event_type_id', $rowNum, $eventType);

      // handle participant status id
      if (array_key_exists('civicrm_participant_status_id', $row)) {
        $statusId = $row['civicrm_participant_status_id'];
        if ($statusId) {
          $rows[$rowNum]['civicrm_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($statusId, FALSE, 'label');
        }
        $entryFound = TRUE;
      }

      // handle participant role id
      if (array_key_exists('civicrm_participant_role_id', $row)) {
        $roleId = $row['civicrm_participant_role_id'];
        if ($roleId) {
          $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $roleId);
          $roleId = array();
          foreach ($roles as $role) {
            $roleId[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
          }
          $rows[$rowNum]['civicrm_participant_role_id'] = implode(', ', $roleId);
        }
        $entryFound = TRUE;
      }

      // Handle registered by name
      if (array_key_exists('civicrm_participant_registered_by_name', $row)) {
        $registeredById = $row['civicrm_participant_registered_by_name'];
        if ($registeredById) {
          $registeredByContactId = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Participant", $registeredById, 'contact_id', 'id');
          $rows[$rowNum]['civicrm_participant_registered_by_name'] = CRM_Contact_BAO_Contact::displayName($registeredByContactId);
          $rows[$rowNum]['civicrm_participant_registered_by_name_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $registeredByContactId, $this->_absoluteUrl);
          $rows[$rowNum]['civicrm_participant_registered_by_name_hover'] = ts('View Contact Summary for Contact that registered the participant.');
        }
      }

      // Handle value seperator in Fee Level
      if (array_key_exists('civicrm_participant_participant_fee_level', $row)) {
        $rows[$rowNum]['civicrm_participant_participant_fee_level'] = $row['civicrm_line_item_line_item_label'];
        $entryFound = TRUE;
      }

      // Convert display name to link
      $displayName = CRM_Utils_Array::value('civicrm_contact_sort_name_linked', $row);
      $cid = CRM_Utils_Array::value('civicrm_contact_id', $row);
      $id = CRM_Utils_Array::value('civicrm_participant_participant_record', $row);

      if ($displayName && $cid && $id) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          "reset=1&force=1&id_op=eq&id_value=$cid",
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );

        $viewUrl = CRM_Utils_System::url("civicrm/contact/view/participant",
          "reset=1&id=$id&cid=$cid&action=view&context=participant"
        );

        $contactTitle = ts('View Contact Details');
        $participantTitle = ts('View Participant Record');

        $rows[$rowNum]['civicrm_contact_sort_name_linked'] = "<a title='$contactTitle' href=$url>$displayName</a>";
        // Add a "View" link to the participant record if this isn't a CSV/PDF/printed document.
        if ($this->_outputMode !== 'csv' && $this->_outputMode !== 'pdf' && $this->_outputMode !== 'print') {
          $rows[$rowNum]['civicrm_contact_sort_name_linked'] .=
            "<span style='float: right;'><a title='$participantTitle' href=$viewUrl>" .
            ts('View') . "</a></span>";
        }
        $entryFound = TRUE;
      }

      // Convert campaign_id to campaign title
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_participant_campaign_id', $rowNum, $this->campaigns);

      // handle contribution status
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_contribution_status_id', $rowNum, $contributionStatus);

      // handle payment instrument
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_payment_instrument_id', $rowNum, $paymentInstruments);

      // handle financial type
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_financial_type_id', $rowNum, $financialTypes);

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'event/participantListing', 'View Event Income Details') ? TRUE : $entryFound;

      // display birthday in the configured custom format
      if (array_key_exists('civicrm_contact_birth_date', $row)) {
        $birthDate = $row['civicrm_contact_birth_date'];
        if ($birthDate) {
          $rows[$rowNum]['civicrm_contact_birth_date'] = CRM_Utils_Date::customFormat($birthDate, '%Y%m%d');
        }
        $entryFound = TRUE;
      }
      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'event/ParticipantListing', 'List all participant(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
 * @param $rows
 * @param $entryFound
 * @param $row
 * @param int $rowId
 * @param $rowNum
 * @param $types
 *
 * @return bool
 */
private function _initBasicRow(&$rows, &$entryFound, $row, $rowId, $rowNum, $types) {
  if (!array_key_exists($rowId, $row)) {
    return FALSE;
  }

  $value = $row[$rowId];
  if ($value) {
    $rows[$rowNum][$rowId] = $types[$value];
  }
  $entryFound = TRUE;
}


}
