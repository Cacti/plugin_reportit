<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

//----- CONSTANTS FOR: reports.php -----

$report_actions = array(
	1 => __('Run', 'reportit'),
	2 => __('Delete', 'reportit'),
	3 => __('Duplicate', 'reportit')
);

$report_states = array(
	'-2' => __('CRASHED', 'reportit'),
	'-1' => __('FAILED', 'reportit'),
	'0'  => __('Idle', 'reportit'),
	'1'  => __('Running', 'reportit')
);

//$templates		- array, for dropdown menu
//			- contains all names of available templates by taking into account user's realm
$templates = db_fetch_assoc('SELECT * FROM plugin_reportit_templates WHERE locked = 0');

if (!$templates) {
	$templates['0'] = '- No template available -';
} else {
	foreach($templates as $key => $value) {
	$tmp[$templates[$key]['id']] = $templates[$key]['description'];
	}
	$templates = $tmp;
	unset($tmp);
}

$weekday = array(
	__('Monday', 'reportit'),
	__('Tuesday', 'reportit'),
	__('Wednesday', 'reportit'),
	__('Thursday', 'reportit'),
	__('Friday', 'reportit'),
	__('Saturday', 'reportit'),
	__('Sunday', 'reportit')
);

// $timespans		- array, for dropdown menu
//			- contains preset values for selecting the report timespan
$timespans = array(
	__('Today', 'reportit'),
	__('Last 1 Day', 'reportit'),
	__('Last 2 Days', 'reportit'),
	__('Last 3 Days', 'reportit'),
	__('Last 4 Days', 'reportit'),
	__('Last 5 Days', 'reportit'),
	__('Last 6 Days', 'reportit'),
	__('Last 7 Days', 'reportit'),
	__('Last Week (Sun - Sat)', 'reportit'),
	__('Last Week (Mon - Sun)', 'reportit'),
	__('Last 14 Days', 'reportit'),
	__('Last 21 Days', 'reportit'),
	__('Last 28 Days', 'reportit'),
	__('Current Month', 'reportit'),
	__('Last Month', 'reportit'),
	__('Last 2 Months', 'reportit'),
	__('Last 3 Months', 'reportit'),
	__('Last 4 Months', 'reportit'),
	__('Last 5 Months', 'reportit'),
	__('Last 6 Months', 'reportit'),
	__('Current Year', 'reportit'),
	__('Last Year', 'reportit'),
	__('Last 2 Years', 'reportit')
);

//timezones
foreach ($timezones as $tmz => $value) $timezone[]=$tmz;

//Schedule frequency
$frequency = array(
	'daily',
	'weekly',
	'monthly',
	'quarterly',
	'yearly'
);

//Maximum number of files an archive can contain
$archive[0] = 'off';
for($i = 1; $i <= 1000; $i++) $archive[$i]= $i;

//Tabs
$tabs = array(
	'general' => __('General', 'reportit'),
	'presets' => __('Data Item Presets', 'reportit'),
	'email'   => __('Email', 'reportit'),
	'admin'   => __('Administration', 'reportit')
);

// $shifttime		- array, for dropdown menu
//			- contains all possible timestamps of a day by using steps of 5 minutes
$shifttime = array();

for($i=0; $i<24; $i++) {
	$hour=$i;

	if ($hour<10) {
		$hour = '0' . $hour;
	}

	for($j=0; $j<60; $j+=5) {
		$minutes = $j;

		if ($minutes<10) {
			$minutes = '0' . $minutes;
		}

		$shifttime[]= "$hour:$minutes:00";
	}
}

$shifttime2  = $shifttime;
$shifttime2[]= "24:00:00";

unset($i);
unset($j);

$format = array(
	'None' => __('None', 'reportit'),
	'CSV'  => __('Text CSV (.csv)', 'reportit'),
	'SML'  => __('MS Excel 2003 XML (.xml)', 'reportit'),
	'XML'  => __('Raw XML (.xml)', 'reportit')
);

$form_array_email = array(
	'report_header_1' => array(
		'friendly_name' => __('General', 'reportit'),
		'method' => 'spacer',
	),
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|',
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value' => 'email',
	),
	'report_email_subject' => array(
		'friendly_name' => __('Subject', 'reportit'),
		'description' => __('Enter the subject of your email.<br> Following variables will be supported (without quotes): \'|title|\' and \'|period|\'', 'reportit'),
		'size' => '60',
		'max_length' => '100',
		'method' => 'textbox',
		'default' => __('Scheduled report - |title| - |period|', 'reportit'),
		'value' => '|arg1:email_subject|',
	),
	'report_email_body' => array(
		'friendly_name' => __('Body (optional)', 'reportit'),
		'description' => __('Enter a message which will be displayed in the body of your email', 'reportit'),
		'method' => 'textarea',
		'textarea_rows' => '3',
		'textarea_cols' => '45',
		'default' => __('This is a scheduled report generated from Cacti.', 'reportit'),
		'value' => '|arg1:email_body|',
	),
	'report_email_format' => array(
		'friendly_name' => __('Attachment', 'reportit'),
		'method' => 'drop_array',
		'description' => __('Only to receive an email as a notification that a new report is available choose \'None\'.<br> Otherwise select the format the report should be attached as.', 'reportit'),
		'value' => '|arg1:email_format|',
		'array' => $format,
		'default' => '1',
	),
	'report_header_2' => array(
		'friendly_name' => __('Email Recipients', 'reportit'),
		'method' => 'spacer',
	),
	'report_email_recipient' => array(
		'friendly_name' => __('New Email Recipients', 'reportit'),
		'description' => __('To add a new recipient enter a valid email address (required) and a name (optional).<br> For a faster setup use a list of adresses/names where the names/addresses are separated with one of the following delemiters: \';\' or \',\'', 'reportit'),
		'method' => 'custom',
		'default' => 'false',
		'value' => "<div style='line-height: 1.5em;'>
				<div>
					<input type='text' id='report_email_address' name='report_email_address' size='60' maxlength='2500' align='top'>
					<input type='submit' id='add_recipients_x' name='add_recipients_x' value='add' title='Add recipients'>
				</div>
				<div>
					<input type='text' id='report_email_recipient' name='report_email_recipient' size='60' maxlength='2500' align='top'>
				</div>
		</div>",
	)
);

$form_array_scheduling = array(
	'report_header_3' => array(
		'friendly_name' => __('Scheduled Reporting', 'reportit'),
		'method' => 'spacer',
	),
	'report_schedule' => array(
		'friendly_name' => __('Enable', 'reportit'),
		'description' => __('Enable/disable scheduled reporting. Sliding time frame should be enabled.', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:scheduled|',
		'default' => '',
	),
	'report_schedule_frequency' => array(
		'friendly_name' => __('Frequency', 'reportit'),
		'description' => __('Select the frequency for processing this report. Be sure that there\'s a cronjob (or scheduled task) running for the choice you made. This won\'t be done automatically by ReportIT.', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:frequency|',
		'array' => $frequency
	),
	'report_autorrdlist' => array(
		'friendly_name' => __('Auto Generated Data Items', 'reportit'),
		'description' => __('Enable/disable automatic creation of all data items based on given filters.This will be called before report execution.  Obsolete RRDs will be deleted and all RRDs matching the filter settings will be added.', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:autorrdlist|',
		'default' => '',
	),
);

if (read_config_option('reportit_archive')) {
	$form_array_scheduling['report_autoarchive'] = array(
		'friendly_name' => __('Auto Generated Archive', 'reportit'),
		'description' => __('Define the maximum number of instances which should be archived before the first one will be overwritten.  Choose "off" if you want to deactivate that RoundRobbin principle (default, but not recommend).  If you define a lower value of instances than the current archive contains then it will get shrinked automatically within the next run.', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:autoarchive|',
		'default' => '0',
		'array' => $archive
	);
}

if (read_config_option('reportit_email')) {
	$form_array_scheduling['report_email'] = array(
		'friendly_name' => __('Auto Generated Email', 'reportit'),
		'description' => __('If enabled tab \'Email\' will be activated and all recipients defined under that section will receive automatically an email containing this scheduled report.', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:auto_email|',
		'default' => ''
	);
}

if (read_config_option('reportit_auto_export')) {
	$form_array_scheduling['report_autoexport'] = array(
		'friendly_name' => __('Auto Generated Export', 'reportit'),
		'description' => __('If enabled the report will be automatically exported to a separate subfolder.  This will be placed within the export folder defined in the report template.', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:autoexport|',
		'array' => $format,
		'default' => '0'
	);
	$form_array_scheduling['report_autoexport_max_records'] = array(
		'friendly_name' => __('Export Limitation', 'reportit'),
		'description' => __('Define the maximum number of instances which should be archived before the first one will be overwritten.  Choose \'off\' if you want to deactivate that RoundRobbin principle (default, but not recommend).  If you define a lower value of instances than the current export folder contains then it will get shrinked automatically within the next run.', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:autoexport_max_records|',
		'default' => '0',
		'array' => $archive
	);
	$form_array_scheduling['report_autoexport_no_formatting'] = array(
		'friendly_name' => __('Raw Data Export', 'reportit'),
		'description' => __('If enabled auto generated exports will contain raw data only. The formatting of measurands will be ignored.', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:autoexport_no_formatting|',
		'default' => ''
	);
}

$form_array_admin = array(
	'report_header_1' => array(
		'friendly_name' => __('General', 'reportit'),
		'method' => 'spacer',
	),
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|',
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value' => 'admin',
	),
	'report_owner' => array(
		'friendly_name' => __('Change Report Owner', 'reportit'),
		'description' => __('Change the owner of this report. Only users with a minimum of reporting rights (\'View\' or higher) can be selected.', 'reportit'),
		'method' => 'drop_sql',
		'sql' => "SELECT DISTINCT a.id, a.username as name FROM user_auth AS a INNER JOIN user_auth_realm AS b ON a.id = b.user_id WHERE (b.realm_id = " . REPORTIT_USER_OWNER . " OR b.realm_id = " . REPORTIT_USER_VIEWER . ") ORDER BY username",
		'value' => '|arg1:user_id|',
	),
	'report_graph_permission' => array(
		'friendly_name' => __('Enable Use of Graph Permissions', 'reportit'),
		'description' => __('If enabled (default) the list of available data items will be filtered automatically by owner\'s graph permission: \'by device\'.', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:graph_permission|',
		'default' => 'on',
	),
);

if (!read_config_option('reportit_operator')) {
	$form_array_admin = array_merge($form_array_admin, $form_array_scheduling);
}

$form_array_presets = array(
	'report_header_1' => array(
		'friendly_name' => __('General', 'reportit'),
		'method' => 'spacer',
	),
	'rrdlist_subhead' => array(
		'friendly_name' => __('Subhead (optional)', 'reportit'),
		'description' => __('Define an additional subhead that should be on display under the interface description.<br> Following variables will be supported (without quotes): \'|t1|\' \'|t2|\' \'|tmz|\' \'|d1|\' \'|d2|\'', 'reportit'),
		'method' => 'textarea',
		'textarea_rows' => '2',
		'textarea_cols' => '45',
		'value' => '|arg1:description|',
		'default' => '',
	)
);

if (read_config_option('reportit_use_tmz')) {
	$form_array_presets['rrdlist_timezone'] = array(
		'friendly_name' => __('Time Zone', 'reportit'),
		'description' => __('Select the time zone your following shifttime informations will be based on.', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:timezone|',
		'default' => '17',
		'array' => array_keys($timezones)
	);
}
$form_array_presets_2 = array(
	'host_template_id' => array(
		'friendly_name' => __('Host Template Filter (optional)', 'reportit'),
		'description' => __('Use those data items only, which belong to hosts of this host template.<br>Select \'None\' (default) to deactivate this filter setting.', 'reportit'),
		'method' => 'drop_sql',
		'sql' => 'SELECT id,name FROM host_template ORDER BY name',
		'none_value' => 'None',
		'value' => '|arg2:host_template_id|',
	),
	'data_source_filter' => array(
		'friendly_name' => __('Data Items Filter (optional)', 'reportit'),
		'description' => __('Allows additional filtering on the data items descriptions.<br> Use SQL wildcards like % and/or _. No regular Expressions!', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'value' => '|arg2:data_source_filter|',
	),
	'report_header_2' => array(
		'friendly_name' => __('Working Time', 'reportit'),
		'method' => 'spacer',
	),
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|',
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value' => 'presets',
	),
	'rrdlist_shifttime_start' => array(
		'friendly_name' => __('From', 'reportit'),
		'description' => __('The startpoint of duration you want to analyse', 'reportit'),
		'method' => 'drop_array',
		'default' => '0',
		'value' => '|arg1:start_time|',
		'array' => $shifttime,
	),
	'rrdlist_shifttime_end' => array(
		'friendly_name' => __('To', 'reportit'),
		'description' => __('The end of analysing time.', 'reportit'),
		'method' => 'drop_array',
		'default' => '288',
		'value' => '|arg1:end_time|',
		'array' => $shifttime2,
	),
	'rrdlist_header_3' => array(
		'friendly_name' => __('Working Days', 'reportit'),
		'method' => 'spacer',
	),
	'rrdlist_weekday_start' => array(
		'friendly_name' => __('From', 'reportit'),
		'description' => __('Define the band of days where shift STARTS!', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:start_day|',
		'default' => '0',
		'array' => $weekday
	),
	'rrdlist_weekday_end' => array(
		'friendly_name' => __('To', 'reportit'),
		'method' => 'drop_array',
		'description' => __('Example: For a nightshift from Mo(22:30) till Sat(06:30) define Monday to Friday', 'reportit'),
		'value' => '|arg1:end_day|',
		'default' => '6',
		'array' => $weekday
	),
);

$form_array_presets = array_merge($form_array_presets, $form_array_presets_2);

$form_array_general = array(
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|',
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value' => 'general',
	),
	'template_id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:template_id|',
	),
	'report_header_1' => array(
		'friendly_name' => __('General', 'reportit'),
		'method' => 'spacer',
	),
	'report_description' => array(
		'friendly_name' => __('Name', 'reportit'),
		'description' => __('The name given to this report', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'value' => '|arg1:description|',
	),
	'report_template' => array(
		'friendly_name' => __('Template', 'reportit'),
		'description' => __('The template your configuration depends on', 'reportit'),
		'method' => 'custom',
		'max_length' => '100',
		'value' => '|arg1:template|',
		'default' => '',
	),
	'report_owner' => array(
		'friendly_name' => __('Owner', 'reportit'),
		'description' => __('Change the owner of this report. Only users with the permission "view" or above can be chosen.', 'reportit'),
		'method' => ( user_auth_realm( REPORTIT_USER_ADMIN, my_id() ) ? 'drop_sql' : 'hidden_zero'),
		'sql' => 'SELECT user_auth.id, user_auth.username as name FROM user_auth LEFT JOIN ( SELECT user_id from user_auth_realm WHERE realm_id = ' . REPORTIT_USER_OWNER . ' ) AS user_realm ON user_auth.id = user_realm.user_id
					LEFT JOIN (
						SELECT user_auth_group_members.user_id FROM user_auth_group_members
							INNER JOIN user_auth_group_realm ON
						user_auth_group_members.group_id = user_auth_group_realm.group_id
						WHERE user_auth_group_realm.realm_id = ' . REPORTIT_USER_OWNER . '
					) as group_member
					ON group_member.user_id = user_auth.id
					WHERE group_member.user_id IS NOT NULL OR user_realm.user_id IS NOT NULL;',
		'value' => '|arg1:user_id|',
	),
	'report_public' => array(
		'friendly_name' => __('Public', 'reportit'),
		'description' => __('If enabled everyone can see your report under tab \'reports\'', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:public|',
		'default' => '',
	),
	'report_header_2' => array(
		'friendly_name' => __('Reporting Period', 'reportit'),
		'method' => 'spacer',
	),
	'report_dynamic' => array(
		'friendly_name' => __('Sliding Time Frame', 'reportit'),
		'description' => __('If checked the reporting period will be configured automatically in relation to the point of time the calculation starts.', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:sliding|',
		'default' => 'on',
	),
	'report_timespan' => array(
		'friendly_name' => __('Time Frames', 'reportit'),
		'description' => __('The time frame you want to analyse in relation to the point of time the calculation starts.<br>This means calendar days, calendar months and calendar years.', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:preset_timespan|',
		'array' => $timespans,
	),
	'report_present' => array(
		'friendly_name' => __('Up To The Day of Calculation', 'reportit'),
		'description' => __('Extend the sliding time frame up to the day the calculation runs.', 'reportit'),
		'method' => 'checkbox',
		'value' => '|arg1:present|',
		'default' => '',
	),
	'report_start_date' => array(
		'friendly_name' => __('Fixed Time Frame - Start Date (From)', 'reportit'),
		'description' => __('To define the start date use the following format: <b>yyyy-mm-dd</b>', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:start_date|',
	),
	'report_end_date' => array(
		'friendly_name' => __('Fixed Time Frame - End Date (To)', 'reportit'),
		'description' => __('To define the end date use the following format: <b>yyyy-mm-dd</b>', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:end_date|',
	)
);

if (read_config_option('reportit_operator')) {
	$form_array_general = array_merge($form_array_general, $form_array_scheduling);
}

