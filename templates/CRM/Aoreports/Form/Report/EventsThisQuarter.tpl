{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* this div is being used to apply special css *}
    {if !$section }
    <div class="crm-block crm-form-block crm-report-field-form-block">
        {include file="CRM/Report/Form/Fields.tpl"}
    </div>
    {/if}

<div class="crm-block crm-content-block crm-report-form-block">
{include file="CRM/Report/Form/Actions.tpl"}
{if !$section }
{include file="CRM/Report/Form/Statistics.tpl" top=true}
{/if}
    {if $rows}

    {foreach from=$rows item=row key=title}
      <h3><center>{$title}</center></h3>
      <table class="report-layout crm-report_contact_civireport">

          <tr>
              {foreach from=$columnHeaders item=header key=field}
                  {if !$skip}
                      {if $header.colspan}
                          <th colspan={$header.colspan}>{$header.title}</th>
                          {assign var=skip value=true}
                          {assign var=skipCount value=`$header.colspan`}
                          {assign var=skipMade  value=1}
                      {else}
                          <th>{$header.title}</th>
                          {assign var=skip value=false}
                      {/if}
                  {else} {* for skip case *}
                      {assign var=skipMade value=`$skipMade+1`}
                      {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                  {/if}
              {/foreach}
          </tr>
            {foreach from=$row item=record key=k}
              <tr class="group-row crm-report">
                {foreach from=$columnHeaders item=header key=field}
                    <td>
                      {if $header.type eq 12 || $header.type eq 4}
                          {if $header.group_by eq 'MONTH' or $header.group_by eq 'QUARTER'}
                              {$record.$field|crmDate:$config->dateformatPartial}
                          {elseif $header.group_by eq 'YEAR'}
                              {$record.$field|crmDate:$config->dateformatYear}
                          {else}
                              {if $field eq 'civicrm_event_event_start_date'}
                                {$record.$field}
                              {else}
                                {$record.$field|truncate:10:''|crmDate}
                              {/if}
                          {/if}
                        {else}
                          {$record.$field}
                        {/if}
                      </td>
                {/foreach}
              </tr>
            {/foreach}
      </table>
    {/foreach}
    {/if}
    {include file="CRM/Report/Form/ErrorMessage.tpl"}
</div>
