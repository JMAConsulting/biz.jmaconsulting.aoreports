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
        ],
        'filters' => [
          'event_id' => array(
            'name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => array(
              'entity' => 'Event',
              'select' => array('minimumInputLength' => 0),
            ),
          ),
        ]
      ]
    ];
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
