<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_AOReports_Form_Report_ParentSupport',
    'entity' => 'ReportTemplate',
    'params' =>
    array (
      'version' => 3,
      'label' => 'Parent Support Issues Report',
      'description' => 'Parent Support Report (biz.jmaconsulting.aoreports)',
      'class_name' => 'CRM_AOReports_Form_Report_ParentSupport',
      'report_url' => 'biz.jmaconsulting.aoreports/parentsupport',
      'component' => '',
    ),
  ),
);
