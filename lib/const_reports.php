<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2025 The Cacti Group                                 |
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
	2 => __('Delete', 'reportit'),
	4 => __('Disable', 'reportit'),
	3 => __('Duplicate', 'reportit'),
	5 => __('Enable', 'reportit'),
	1 => __('Run Now', 'reportit'),
	6 => __('Take Ownership', 'reportit')
);

$report_states = array(
	'-2' => __('CRASHED', 'reportit'),
	'-1' => __('FAILED',  'reportit'),
	'0'  => __('Idle',    'reportit'),
	'1'  => __('Running', 'reportit')
);

/**
 * $templates - array, for dropdown menu
 * contains all names of available templates by taking into account user's realm
 */
$templates = db_fetch_assoc('SELECT * FROM plugin_reportit_templates WHERE locked = 0');

if (!$templates) {
	$templates['0'] = __('- No template available -', 'reportit');
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

/**
 * $timespans - array, for dropdown menu
 * contains preset values for selecting the report timespan
 */
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

// Timezones
foreach ($timezones as $tmz => $value) {
	$timezone[] = $tmz;
}

//Schedule frequency
$frequency = array(
	'daily'     => __('Daily', 'reportit'),
	'weekly'    => __('Weekly', 'reportit'),
	'monthly'   => __('Monthly', 'reportit'),
	'quarterly' => __('Quarterly', 'reportit'),
	'yearly'    => __('Yearly', 'reportit')
);

// Maximum number of files an archive can contain
$archive[0] = 'off';
for($i = 1; $i <= 1000; $i++) {
	$archive[$i]= $i;
}

// Tabs
$tabs = array(
	'general' => __('General', 'reportit'),
	'presets' => __('Data Source Presets', 'reportit'),
	'email'   => __('Email', 'reportit'),
	'items'   => __('Data Sources', 'reportit')
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
	'XML'  => __('Raw XML (.xml)', 'reportit'),
	'JSON' => __('JSON Data (.json)', 'reportit')
);

if (function_exists('yaml_emit')) {
	$format['YAML'] = __('YAML Data (.yaml)', 'reportit');
}

if (db_table_exists('plugin_notification_lists')) {
	$notify_lists = array_rekey(
		db_fetch_assoc('SELECT id, name
			FROM plugin_notification_lists
			WHERE enabled = "on"
			ORDER BY name'),
		'id', 'name'
	);
} else {
	$notify_lists = array();
}

$form_array_email = array(
	'header_1' => array(
		'friendly_name' => __('General', 'reportit'),
		'method'        => 'spacer',
	),
	'id' => array(
		'method' => 'hidden_zero',
		'value'  => '|arg1:id|',
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value'  => 'email',
	),
	'email_subject' => array(
		'friendly_name' => __('Subject', 'reportit'),
		'description'   => __('Enter the subject of your email.<br> Following variables will be supported (without quotes): \'|title|\' and \'|period|\'', 'reportit'),
		'size'          => '60',
		'max_length'    => '100',
		'method'        => 'textbox',
		'default'       => __('Scheduled report - |title| - |period|', 'reportit'),
		'value'         => '|arg1:email_subject|',
	),
	'email_body' => array(
		'friendly_name' => __('Body (optional)', 'reportit'),
		'description'   => __('Enter a message which will be displayed in the body of your email', 'reportit'),
		'method'        => 'textarea',
		'textarea_rows' => '3',
		'textarea_cols' => '45',
		'default'       => __('This is a scheduled report generated from Cacti.', 'reportit'),
		'value'         => '|arg1:email_body|',
	),
	'email_format' => array(
		'friendly_name' => __('Attachment', 'reportit'),
		'method'        => 'drop_array',
		'description'   => __('Only to receive an email as a notification that a new report is available choose \'None\'.<br> Otherwise select the format the report should be attached as.', 'reportit'),
		'value'         => '|arg1:email_format|',
		'array'         => $format,
		'default'       => '1',
	),
	'header_2' => array(
		'friendly_name' => __('Email Recipients', 'reportit'),
		'method'        => 'spacer',
	),
	'notify_list' => array(
		'friendly_name' => __('Notification List Recipients', 'reportit'),
		'description'   => __('To add a Recipients based upon an valid Notification List.', 'reportit'),
		'method'        => 'drop_array',
		'array'         => $notify_lists,
		'default'       => '',
		'none_value'    => __('None', 'reportit'),
		'value'         => '|arg1:notify_list|',
	),
	'email' => array(
		'friendly_name' => __('To Email Address(es)'),
		'method'        => 'textarea',
		'textarea_rows' => '5',
		'textarea_cols' => '60',
		'class'         => 'textAreaNotes',
		'default'       => '',
		'description'   => __('Please separate multiple addresses by comma (,)'),
		'max_length'    => 255,
		'value'         => '|arg1:email|'
	),
	'bcc' => array(
		'friendly_name' => __('BCC Address(es)'),
		'method'        => 'textarea',
		'textarea_rows' => '5',
		'textarea_cols' => '60',
		'class'         => 'textAreaNotes',
		'default'       => '',
		'description'   => __('Blind carbon copy. Please separate multiple addresses by comma (,)'),
		'max_length'    => 255,
		'value'         => '|arg1:bcc|'
	),
	'email_recipient' => array(
		'friendly_name' => __('New Email Recipients', 'reportit'),
		'description'   => __('To add a new recipient enter a valid email address (required) and a name (optional).<br> For a faster setup use a list of adresses/names where the names/addresses are separated with one of the following delemiters: \';\' or \',\'', 'reportit'),
		'method'        => 'custom',
		'default'       => 'false',
		'value'         => "<div style='line-height: 1.5em;'>
			<div>
				<input type='text' id='email_address' name='email_address' size='60' maxlength='2500' align='top'>
				<input type='submit' id='add_recipients_x' name='add_recipients_x' value='add' title='Add recipients'>
			</div>
			<div>
				<input type='text' id='email_recipient' name='email_recipient' size='60' maxlength='2500' align='top'>
			</div>
		</div>",
	)
);

if (!cacti_sizeof($notify_lists)) {
	unset($form_array_email['notify_list']);
}

$form_array_presets = array(
	'header_1' => array(
		'friendly_name' => __('General', 'reportit'),
		'method'        => 'spacer',
	),
	'subhead' => array(
		'friendly_name' => __('Optional Sub-heading', 'reportit'),
		'description'   => __('Define an additional subhead that should be on display under the interface description.<br> Following variables will be supported (without quotes): \'|t1|\' \'|t2|\' \'|tmz|\' \'|d1|\' \'|d2|\'', 'reportit'),
		'method'        => 'textarea',
		'textarea_rows' => '2',
		'textarea_cols' => '45',
		'value'         => '|arg1:subhead|',
		'default'       => '',
	),
);

if (read_config_option('reportit_use_tmz') == 'on') {
	$form_array_presets['timezone'] = array(
		'friendly_name' => __('Time Zone', 'reportit'),
		'description'   => __('Select the time zone your following shifttime informations will be based on.', 'reportit'),
		'method'        => 'drop_array',
		'value'         => '|arg1:timezone|',
		'default'       => '17',
		'array'         => array_keys($timezones)
	);
}

$form_array_presets_2 = array(
	'data_source_header' => array(
		'friendly_name' => __('Optional Data Source Pre-Filters', 'reportit'),
		'method'        => 'spacer',
	),
	'site_id' => array(
		'friendly_name' => __('Site Filter', 'reportit'),
		'description'   => __('Use this Sites matching Data Sources only.<br>Select \'None\' (default) to deactivate this filter setting.', 'reportit'),
		'method'        => 'drop_sql',
		'sql'           => 'SELECT id, name FROM sites ORDER BY name',
		'none_value'    => __('None', 'reportit'),
		'value'         => '|arg2:site_id|',
	),
	'host_template_id' => array(
		'friendly_name' => __('Device Template Filter', 'reportit'),
		'description'   => __('Use this Device Templates Data Sources only.<br>Select \'None\' (default) to deactivate this filter setting.', 'reportit'),
		'method'        => 'drop_sql',
		'sql'           => 'SELECT id, name FROM host_template ORDER BY name',
		'none_value'    => __('None', 'reportit'),
		'value'         => '|arg2:host_template_id|',
	),
	'data_source_filter' => array(
		'friendly_name' => __('Data Source Name Filter', 'reportit'),
		'description'   => __('Use Data Sources whose names match this filter.<br> Use SQL wildcards like % and/or _. No regular Expressions!', 'reportit'),
		'method'        => 'textbox',
		'size'          => 50,
		'max_length'    => '100',
		'value'         => '|arg2:data_source_filter|',
	),
	'autorrdlist' => array(
		'friendly_name' => __('Auto Generated Data Items', 'reportit'),
		'description'   => __('Enable/disable automatic creation of all data items based on given filters.This will be called before report execution.  Obsolete RRDs will be deleted and all RRDs matching the filter settings will be added.', 'reportit'),
		'method'        => 'checkbox',
		'value'         => '|arg1:autorrdlist|',
		'default'       => '',
	),
	'header_2' => array(
		'friendly_name' => __('Working Time', 'reportit'),
		'method'        => 'spacer',
	),
	'id' => array(
		'method' => 'hidden_zero',
		'value'  => '|arg1:id|',
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value'  => 'presets',
	),
	'start_time' => array(
		'friendly_name' => __('From', 'reportit'),
		'description'   => __('The startpoint of duration you want to analyse', 'reportit'),
		'method'        => 'drop_array',
		'default'       => '0',
		'value'         => '|arg1:start_time|',
		'array'         => $shifttime,
	),
	'end_time' => array(
		'friendly_name' => __('To', 'reportit'),
		'description'   => __('The end of analysing time.', 'reportit'),
		'method'        => 'drop_array',
		'default'       => '288',
		'value'         => '|arg1:end_time|',
		'array'         => $shifttime2,
	),
	'header_3' => array(
		'friendly_name' => __('Working Days', 'reportit'),
		'method'        => 'spacer',
	),
	'start_day' => array(
		'friendly_name' => __('From', 'reportit'),
		'description'   => __('Define the band of days where shift STARTS!', 'reportit'),
		'method'        => 'drop_array',
		'value'         => '|arg1:start_day|',
		'default'       => '0',
		'array'         => $weekday
	),
	'end_day' => array(
		'friendly_name' => __('To', 'reportit'),
		'method'        => 'drop_array',
		'description'   => __('Example: For a nightshift from Mo(22:30) till Sat(06:30) define Monday to Friday', 'reportit'),
		'value'         => '|arg1:end_day|',
		'default'       => '6',
		'array'         => $weekday
	),
);

$form_array_presets = array_merge($form_array_presets, $form_array_presets_2);

$owner_sql = 'SELECT user_auth.id, user_auth.username AS name
	FROM user_auth
	LEFT JOIN (
		SELECT user_id
		FROM user_auth_realm
		WHERE realm_id = ' . REPORTIT_USER_OWNER . '
	) AS user_realm
	ON user_auth.id = user_realm.user_id
	LEFT JOIN (
		SELECT gm.user_id
		FROM user_auth_group_members AS gm
		INNER JOIN user_auth_group_realm AS gr
		ON gm.group_id = gr.group_id
		WHERE gr.realm_id = ' . REPORTIT_USER_OWNER . '
	) AS group_member
	ON group_member.user_id = user_auth.id
	WHERE group_member.user_id IS NOT NULL OR user_realm.user_id IS NOT NULL';

$form_array_general = array(
	'id' => array(
		'method' => 'hidden_zero',
		'value'  => '|arg1:id|',
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value'  => 'general',
	),
	'template_id' => array(
		'method' => 'hidden_zero',
		'value'  => '|arg1:template_id|',
	),
	'header_1' => array(
		'friendly_name' => __('General', 'reportit'),
		'method'        => 'spacer',
	),
	'name' => array(
		'friendly_name' => __('Name', 'reportit'),
		'description'   => __('The name given to this report', 'reportit'),
		'method'        => 'textbox',
		'max_length'    => '100',
		'value'         => '|arg1:name|',
	),
	'template' => array(
		'friendly_name' => __('Template', 'reportit'),
		'description'   => __('The template your configuration depends on', 'reportit'),
		'method'        => 'custom',
		'max_length'    => '100',
		'value'         => '|arg1:template|',
		'default'       => '',
	),
	'owner' => array(
		'friendly_name' => __('Owner', 'reportit'),
		'description'   => __('Change the owner of this report. Only users with the permission "view" or above can be chosen.', 'reportit'),
		'method'        => (user_auth_realm(REPORTIT_USER_ADMIN, my_id()) ? 'drop_sql':'hidden_zero'),
		'sql'           => $owner_sql,
		'value'         => '|arg1:user_id|',
	),
	'enabled' => array(
		'friendly_name' => __('Enabled', 'reportit'),
		'description'   => __('Enable/disable scheduled reporting. Sliding time frame should be enabled.', 'reportit'),
		'method'        => 'checkbox',
		'value'         => '|arg1:enabled|',
		'default'       => '',
	),
	'public' => array(
		'friendly_name' => __('Public', 'reportit'),
		'description'   => __('If enabled everyone can see your report under tab \'reports\'', 'reportit'),
		'method'        => 'checkbox',
		'value'         => '|arg1:public|',
		'default'       => '',
	),
	'graph_permission' => array(
		'friendly_name' => __('Enable Use of Graph Permissions', 'reportit'),
		'description'   => __('If enabled (default) the list of available data items will be filtered automatically by owner\'s graph permission: \'by device\'.', 'reportit'),
		'method'        => 'checkbox',
		'value'         => '|arg1:graph_permission|',
		'default'       => 'on',
	),
	'auto_email' => array(
		'friendly_name' => __('Auto Generated Email', 'reportit'),
		'description'   => __('If enabled tab \'Email\' will be activated and all recipients defined under that section will receive automatically an email containing this scheduled report.', 'reportit'),
		'method'        => 'checkbox',
		'value'         => '|arg1:auto_email|',
		'default'       => ''
	),
	'header_2' => array(
		'friendly_name' => __('Reporting Period', 'reportit'),
		'method'        => 'spacer',
	),
	'sliding' => array(
		'friendly_name' => __('Sliding Time Frame', 'reportit'),
		'description'   => __('If checked the reporting period will be configured automatically in relation to the point of time the calculation starts.', 'reportit'),
		'method'        => 'checkbox',
		'value'         => '|arg1:sliding|',
		'default'       => 'off',
	),
	'preset_timespan' => array(
		'friendly_name' => __('Time Frames', 'reportit'),
		'description'   => __('The time frame you want to analyse in relation to the point of time the calculation starts.<br>This means calendar days, calendar months and calendar years.', 'reportit'),
		'method'        => 'drop_array',
		'value'         => '|arg1:preset_timespan|',
		'array'         => $timespans,
	),
	'present' => array(
		'friendly_name' => __('Up To The Day of Calculation', 'reportit'),
		'description'   => __('Extend the sliding time frame up to the day the calculation runs.', 'reportit'),
		'method'        => 'checkbox',
		'value'         => '|arg1:present|',
		'default'       => '',
	),
	'start_date' => array(
		'friendly_name' => __('Fixed Time Frame - Start Date (From)', 'reportit'),
		'description'   => __('To define the start date use the following format: <b>yyyy-mm-dd</b>', 'reportit'),
		'method'        => 'textbox',
		'max_length'    => '10',
		'value'         => '|arg1:start_date|',
	),
	'end_date' => array(
		'friendly_name' => __('Fixed Time Frame - End Date (To)', 'reportit'),
		'description'   => __('To define the end date use the following format: <b>yyyy-mm-dd</b>', 'reportit'),
		'method'        => 'textbox',
		'max_length'    => '10',
		'value'         => '|arg1:end_date|',
	)
);

$form_array_general += api_scheduler_form();

