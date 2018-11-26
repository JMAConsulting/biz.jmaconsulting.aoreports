<?php
use CRM_AOReports_ExtensionUtil as E;

class CRM_AOReports_Form_Report_FamilyReport extends CRM_Report_Form_Contact_Relationship {
  /**
   * Class constructor.
   */
   protected $_optimisedForOnlyFullGroupBy = FALSE;
  public function __construct() {
    parent::__construct();

    $contact_type = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, '_');

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'required' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
            'name' => 'first_name',
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
            'name' => 'last_name',
          ),
          'birth_date' => array(
            'name' => 'birth_date',
            'title' => ts('Birth Date (Contact A)'),
          ),
          'created_date' => array(
            'name' => 'created_date',
            'title' => ts('Created Date (Contact A)'),
          ),
        ),
        'filters' => array(
          'sort_name_a' => array(
            'title' => ts('Contact A'),
            'name' => 'sort_name',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
        ),
        'order_bys' => array(
          'first_name' => array(
            'title' => ts('First Name (Contact A)'),
            'name' => 'first_name',
          ),
          'last_name' => array(
            'title' => ts('Last Name (Contact A)'),
            'name' => 'last_name',
          ),
        ),
        'grouping' => 'contact_a_fields',
      ),
      'civicrm_relationship_type' => array(
        'dao' => 'CRM_Contact_DAO_RelationshipType',
        'fields' => array(
          'label_a_b' => array(
            'title' => ts('Relationship'),
            'default' => TRUE,
          ),
        ),
        'order_bys' => array(
          'label_a_b' => array(
            'title' => ts('Relationship A-B'),
            'name' => 'label_a_b',
          ),
          'label_b_A' => array(
            'title' => ts('Relationship B-A'),
            'name' => 'label_b_a',
          ),
        ),
        'grouping' => 'relation-fields',
      ),
      'civicrm_contact_b' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'contact_b',
        'fields' => array(
          'id' => array(
            'required' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
            'name' => 'first_name',
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
            'name' => 'last_name',
          ),
          'b_birth_date' => array(
            'name' => 'birth_date',
            'title' => ts('Birth Date (Contact B)'),
          ),
          'created_date' => array(
            'name' => 'created_date',
            'title' => ts('Created Date (Contact B)'),
          ),
        ),
        'filters' => array(
          'sort_name_b' => array(
            'title' => ts('Contact B'),
            'name' => 'sort_name',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
        ),
        'order_bys' => array(
          'first_name_b' => array(
            'title' => ts('First Name (Contact B)'),
            'name' => 'first_name',
          ),
          'last_name_b' => array(
            'title' => ts('Last Name (Contact B)'),
            'name' => 'last_name',
          ),
        ),
        'grouping' => 'contact_b_fields',
      ),
      'civicrm_email_b' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'alias' => 'email_b',
        'fields' => array(
          'email_b' => array(
            'title' => ts('Email (Contact B)'),
            'name' => 'email',
          ),
        ),
        'grouping' => 'contact_b_fields',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'alias' => 'phone_a',
        'fields' => array(
          'phone_a' => array(
            'title' => ts('Phone (Contact A)'),
            'name' => 'phone',
          ),
          'phone_ext_a' => array(
            'title' => ts('Phone Ext (Contact A)'),
            'name' => 'phone_ext',
          ),
        ),
        'grouping' => 'contact_a_fields',
      ),
      'civicrm_phone_b' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'alias' => 'phone_b',
        'fields' => array(
          'phone_b' => array(
            'title' => ts('Phone (Contact B)'),
            'name' => 'phone',
          ),
          'phone_ext_b' => array(
            'title' => ts('Phone Ext (Contact B)'),
            'name' => 'phone_ext',
          ),
        ),
        'grouping' => 'contact_b_fields',
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => array(
          'description' => array(
            'title' => ts('Relationship Description'),
          ),
          'relationship_id' => array(
            'title' => ts('Rel ID'),
            'name' => 'id',
          ),
        ),
        'filters' => array(
          'is_active' => array(
            'title' => ts('Relationship Status'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array(
              '' => ts('- Any -'),
              1 => ts('Active'),
              0 => ts('Inactive'),
            ),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'is_valid' => array(
            'title' => ts('Relationship Dates Validity'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array(
              NULL => ts('- Any -'),
              1 => ts('Not expired'),
              0 => ts('Expired'),
            ),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'relationship_type_id' => array(
            'title' => ts('Relationship'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'active_period_date' => array(
            'title' => ts('Active Period'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),

        'order_bys' => array(
          'start_date' => array(
            'title' => ts('Start Date'),
            'name' => 'start_date',
          ),
        ),
        'grouping' => 'relation-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => ['name' => 'street_address'],
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array(
            'title' => ts('State/Province'),
          ),
        ),
        'filters' => array(
          'country_id' => array(
            'title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ),
          'state_province_id' => array(
            'title' => ts('State/Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email_a' => array(
            'title' => ts('Email (Contact A)'),
            'name' => 'email',
          ),
        ),
        'grouping' => 'contact_a_fields',
      ),
    );

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
  }

  public function from() {
    $this->_from = "
        FROM civicrm_relationship {$this->_aliases['civicrm_relationship']}

             INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                        ON ( {$this->_aliases['civicrm_relationship']}.contact_id_a =
                             {$this->_aliases['civicrm_contact']}.id )

             INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact_b']}
                        ON ( {$this->_aliases['civicrm_relationship']}.contact_id_b =
                             {$this->_aliases['civicrm_contact_b']}.id )

             {$this->_aclFrom} ";

      $this->_from .= "
            INNER  JOIN civicrm_address {$this->_aliases['civicrm_address']}
                         ON (( {$this->_aliases['civicrm_address']}.contact_id =
                               {$this->_aliases['civicrm_contact']}.id  OR
                               {$this->_aliases['civicrm_address']}.contact_id =
                               {$this->_aliases['civicrm_contact_b']}.id ) AND
                               {$this->_aliases['civicrm_address']}.is_primary = 1 ) ";

    $this->_from .= "
        INNER JOIN civicrm_relationship_type {$this->_aliases['civicrm_relationship_type']}
                        ON ( {$this->_aliases['civicrm_relationship']}.relationship_type_id  =
                             {$this->_aliases['civicrm_relationship_type']}.id  ) ";

    // Include Email Field.
    if ($this->_emailField_a) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                       ON ( {$this->_aliases['civicrm_contact']}.id =
                            {$this->_aliases['civicrm_email']}.contact_id AND
                            {$this->_aliases['civicrm_email']}.is_primary = 1 )";
    }
    if ($this->_emailField_b) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email_b']}
                       ON ( {$this->_aliases['civicrm_contact_b']}.id =
                            {$this->_aliases['civicrm_email_b']}.contact_id AND
                            {$this->_aliases['civicrm_email_b']}.is_primary = 1 )";
    }
    // Include Phone Field.
    if ($this->_phoneField_a) {
      $this->_from .= "
             LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                       ON ( {$this->_aliases['civicrm_contact']}.id =
                            {$this->_aliases['civicrm_phone']}.contact_id AND
                            {$this->_aliases['civicrm_phone']}.is_primary = 1 )";
    }
    if ($this->_phoneField_b) {
      $this->_from .= "
             LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone_b']}
                       ON ( {$this->_aliases['civicrm_contact_b']}.id =
                            {$this->_aliases['civicrm_phone_b']}.contact_id AND
                            {$this->_aliases['civicrm_phone_b']}.is_primary = 1 )";
    }
  }

  /**
   * @param $rows
   */
  public function alterDisplay(&$rows) {
    // Custom code to alter rows.
    $entryFound = FALSE;

    foreach ($rows as $rowNum => $row) {

      // Handle ID to label conversion for contact fields
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contact/relationship', 'View Relationships') ? TRUE : $entryFound;

      // Handle contact subtype A
      // @todo refactor into separate function
      if (array_key_exists('civicrm_contact_contact_sub_type_a', $row)) {
        if ($value = $row['civicrm_contact_contact_sub_type_a']) {
          $rowValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $rowLabels = array();
          foreach ($rowValues as $rowValue) {
            if ($rowValue) {
              $rowLabels[] = CRM_Core_Pseudoconstant::getLabel('CRM_Contact_BAO_Contact', 'contact_sub_type', $rowValue);
            }
          }
          $rows[$rowNum]['civicrm_contact_contact_sub_type_a'] = implode(', ', $rowLabels);
        }
        $entryFound = TRUE;
      }

      // Handle contact subtype B
      // @todo refactor into separate function
      if (array_key_exists('civicrm_contact_b_contact_sub_type_b', $row)) {
        if ($value = $row['civicrm_contact_b_contact_sub_type_b']) {
          $rowValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $rowLabels = array();
          foreach ($rowValues as $rowValue) {
            if ($rowValue) {
              $rowLabels[] = CRM_Core_Pseudoconstant::getLabel('CRM_Contact_BAO_Contact', 'contact_sub_type', $rowValue);
            }
          }
          $rows[$rowNum]['civicrm_contact_b_contact_sub_type_b'] = implode(', ', $rowLabels);
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      // Handle contact name A
      // @todo refactor into separate function
      foreach(['first_name', 'last_name', 'b_first_name', 'b_last_name'] as $name) {
        $id = (in_array($name, ['b_first_name', 'b_last_name'])) ? 'civicrm_contact_b_id' : 'civicrm_contact_id';
        if (array_key_exists('civicrm_contact_' . $name, $row) &&
          array_key_exists($id, $row)
        ) {
          $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
            'reset=1&force=1&id_op=eq&id_value=' . $row[$id],
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );
          $rows[$rowNum]["civicrm_contact_{$name}_link"] = $url;
          $rows[$rowNum]["civicrm_contact_{$name}_hover"] = ts('View Contact Detail Report for this contact');
          $entryFound = TRUE;
        }
      }


      // Handle contact name B
      // @todo refactor into separate function
      if (array_key_exists('civicrm_contact_b_sort_name_b', $row) &&
        array_key_exists('civicrm_contact_b_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_b_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_b_sort_name_b']
          = $rows[$rowNum]['civicrm_contact_b_sort_name_b'] . ' (' .
          $rows[$rowNum]['civicrm_contact_b_id'] . ')';
        $rows[$rowNum]['civicrm_contact_b_sort_name_b_link'] = $url;
        $rows[$rowNum]['civicrm_contact_b_sort_name_b_hover'] = ts('View Contact Detail Report for this contact');
        $entryFound = TRUE;
      }

      // Handle relationship
      if (array_key_exists('civicrm_relationship_relationship_id', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = "/civicrm/contact/view/rel?reset=1&action=update&rtype=a_b&cid=" .
          $row['civicrm_contact_id'] . "&id=" .
          $row['civicrm_relationship_relationship_id'];
        $rows[$rowNum]['civicrm_relationship_relationship_id_link'] = $url;
        $rows[$rowNum]['civicrm_relationship_relationship_id_hover'] = ts("Edit this relationship.");
        $entryFound = TRUE;
      }

      // Handle permissioned relationships
      if (array_key_exists('civicrm_relationship_is_permission_a_b', $row)) {
        $rows[$rowNum]['civicrm_relationship_is_permission_a_b']
          = ts(self::permissionedRelationship($row['civicrm_relationship_is_permission_a_b']));
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_relationship_is_permission_b_a', $row)) {
        $rows[$rowNum]['civicrm_relationship_is_permission_b_a']
          = ts(self::permissionedRelationship($row['civicrm_relationship_is_permission_b_a']));
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * Add count statistics.
   *
   * @param array $statistics
   * @param int $count
   */
  public function countStat(&$statistics, $count) {
    parent::countStat($statistics, $count);
    $familiesCount = CRM_Core_DAO::singleValueQuery(" SELECT COUNT(*) FROM ( SELECT contact_b_civireport.id " . $this->_from . " " . $this->_where . " GROUP BY contact_b_civireport.id ) as temp ");
    $frenchFamiliesCount = CRM_Core_DAO::singleValueQuery(" SELECT COUNT(*)
      FROM ( SELECT contact_b_civireport.id " . $this->_from . " " . $this->_where . "
      AND contact_civireport.id IN ( SELECT DISTINCT t.entity_id FROM civicrm_value_eventbrite_re_20 t WHERE t.languages_spoken_at_home_langues_71 LIKE '%French%' )
      GROUP BY contact_b_civireport.id ) as temp ");
    array_unshift($statistics['counts'], array(
      'title' => ts('Family Found'),
      'value' => "Total: $familiesCount, French: $frenchFamiliesCount",
    ));

    $childrenCount = CRM_Core_DAO::singleValueQuery(" SELECT COUNT(*) FROM ( SELECT contact_civireport.id " . $this->_from . " " . $this->_where . " GROUP BY contact_civireport.id ) as temp ");
    $frenchChildrenCount = CRM_Core_DAO::singleValueQuery(" SELECT COUNT(*)
      FROM ( SELECT contact_civireport.id " . $this->_from . " " . $this->_where . "
      AND contact_civireport.id IN ( SELECT DISTINCT t.entity_id FROM civicrm_value_eventbrite_re_20 t WHERE t.languages_spoken_at_home_langues_71 LIKE '%French%' )
      GROUP BY contact_b_civireport.id ) as temp ");
    $childrenCount += $familiesCount;
    $frenchChildrenCount += $frenchFamiliesCount;

      array_unshift($statistics['counts'], array(
        'title' => ts('Individuals Found'),
        'value' => "Total: $childrenCount, French: $frenchChildrenCount",
      ));
  }


}
