<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_ExtendSummary extends CRM_Report_Form_Contact_Summary {

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_participant'] = [
      'dao' => 'CRM_Event_DAO_Participant',
      'fields' => [
        'id' => [
          'no_display' => TRUE,
          'required' => TRUE,
        ],
        'filters' => [
          'event_id' => array(
            'name' => 'event_id',
            'title' => ts('Event'),
            'options' => $this->getEventFilterOptions(),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ]
      ]
    ];
  }

  function getEventFilterOptions() {
    $events = array();
    $query = "
        select id, start_date, title from civicrm_event
        where (is_template IS NULL OR is_template = 0) AND is_active
        order by title ASC, start_date
    ";
    $dao = CRM_Core_DAO::executeQuery($query);
    while($dao->fetch()) {
       $events[$dao->id] = "{$dao->title} - " . CRM_Utils_Date::customFormat(substr($dao->start_date, 0, 10)) . " (ID {$dao->id})";
    }
    return $events;
  }

  public function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom} ";
    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();
    $this->joinCountryFromAddress();

    $this->_from .= "LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
      ON {$this->_aliases['civicrm_participant']}.contact_id = {$this->_aliases['civicrm_contact']}.id ";
  }

}
