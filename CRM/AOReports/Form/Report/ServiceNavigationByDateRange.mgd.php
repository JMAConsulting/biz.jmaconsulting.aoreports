<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_AOReports_Form_Report_ServiceNavigationByDateRange',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'ServiceNavigationByDateRange',
      'description' => 'ServiceNavigationByDateRange (biz.jmaconsulting.aoreports)',
      'class_name' => 'CRM_AOReports_Form_Report_ServiceNavigationByDateRange',
      'report_url' => 'biz.jmaconsulting.aoreports/servicenavigationbydaterange',
      'component' => '',
    ],
  ],
];
