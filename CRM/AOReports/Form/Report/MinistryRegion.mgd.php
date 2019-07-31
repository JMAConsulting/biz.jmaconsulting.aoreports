<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_AOReports_Form_Report_MinistryRegion',
    'entity' => 'ReportTemplate',
    'params' =>
    array (
      'version' => 3,
      'label' => 'Number of families served broken doen by Ministry Region',
      'description' => 'Number of families who have had an event registration, individual consultation or service navigation provision (biz.jmaconsulting.aoreports)',
      'class_name' => 'CRM_AOReports_Form_Report_MinistryRegion',
      'report_url' => 'biz.jmaconsulting.aoreports/ministryregion',
      'component' => '',
    ),
  ),
);
