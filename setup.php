<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
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
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/**
 * Plugin install hook: registers this plugin's hooks (tab rendering,
 * navigation text, config arrays/settings, poller bottom, log regex)
 * and admin realms (viewing/creating/managing reports), then runs the
 * initial database schema setup. Called by Cacti's plugin architecture
 * when the plugin is installed.
 *
 * @return void
 */
function plugin_reportit_install() {
	api_plugin_register_hook('reportit', 'top_header_tabs',       'reportit_show_tab',             'setup.php');
	api_plugin_register_hook('reportit', 'top_graph_header_tabs', 'reportit_show_tab',             'setup.php');
	api_plugin_register_hook('reportit', 'draw_navigation_text',  'reportit_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('reportit', 'config_arrays',         'reportit_config_arrays',        'setup.php');
	api_plugin_register_hook('reportit', 'config_settings',       'reportit_config_settings',      'setup.php');
	api_plugin_register_hook('reportit', 'poller_bottom',         'reportit_poller_bottom',        'setup.php');
	api_plugin_register_hook('reportit', 'clog_regex_array',      'reportit_clog_regex_array',     'setup.php');

	api_plugin_register_realm('reportit', 'view.php,charts.php', 'ReportIt - Report Viewing', 1);
	api_plugin_register_realm('reportit', 'reportit.php,view.php', 'ReportIt - Create Reports', 1);
	api_plugin_register_realm('reportit', 'templates.php', 'ReportIt - Manage Reports', 1);

	$realm_array = [
		__('ReportIt - Report Viewing', 'reportit'),
		__('ReportIt - Create Reports', 'reportit'),
		__('ReportIt - Manage Reports', 'reportit')
	];

	reportit_system_setup();
}

/**
 * Plugin uninstall hook: drops all of this plugin's database tables.
 * Called by Cacti's plugin architecture when the plugin is
 * uninstalled.
 *
 * @return bool Always true.
 */
function plugin_reportit_uninstall() {
	db_execute('DROP TABLE IF EXISTS plugin_reportit_cache_measurands');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_cache_reports');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_cache_variables');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_data_items');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_data_source_items');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_measurands');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_presets');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_recipients');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_reports');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_rvars');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_templates');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_variables');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_data_template_groups');

	return true;
}

/**
 * Plugin config-check hook: ensures the plugin's schema/hooks are up to
 * date by delegating to reportit_check_upgrade(). Called by Cacti's
 * plugin architecture on relevant page loads.
 *
 * @return bool The result of reportit_check_upgrade() (always true).
 */
function plugin_reportit_check_config() {
	return reportit_check_upgrade();
}

/**
 * Plugin upgrade hook: brings the plugin's schema/hooks up to date by
 * delegating to reportit_check_upgrade(). Called by Cacti's plugin
 * architecture when the plugin is upgraded to a new version.
 *
 * @return bool Always true.
 */
function plugin_reportit_upgrade() {
	reportit_check_upgrade();

	return true;
}

/**
 * Reads and returns this plugin's version/author/metadata info from its
 * INFO file. Called wherever plugin metadata is needed (e.g.
 * reportit_check_upgrade()).
 *
 * @return array The plugin's info array, as parsed from the INFO
 *               file's '[info]' section.
 */
function plugin_reportit_version() {
	$info = parse_ini_file(CACTI_PATH_BASE . '/plugins/reportit/INFO', true);

	return $info['info'];
}

/**
 * Checks whether the plugin's recorded database version differs from
 * its actual (INFO file) version and, if so, runs the plugin's install/
 * upgrade system scripts to bring its schema up to date, re-registers
 * its hooks, and updates the plugin_config record. Only runs on
 * index.php/plugins.php/poller_reportit.php page loads. Called from
 * plugin_reportit_check_config() and plugin_reportit_upgrade().
 *
 * @return bool Always true.
 */
function reportit_check_upgrade() {
	$files = ['index.php', 'plugins.php', 'poller_reportit.php'];

	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files, true)) {
		return true;
	}

	$current = plugin_reportit_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='reportit'");
	$tables  = db_fetch_assoc("SHOW TABLE STATUS WHERE `Name` LIKE 'reportit%'");

	if (cacti_sizeof($old) && $current == $old['version']) {
		// ReportIt is up to date
		return true;
	}

	if (cacti_sizeof($old) && $current != $old['version']) {
		if ($old['status'] == 1 || $old['status'] == 4) {
			// re-register hooks
			// plugin_reportit_install();

			// perform data base upgrade
			require_once(CACTI_PATH_BASE . '/plugins/reportit/system/install.php');
			require_once(CACTI_PATH_BASE . '/plugins/reportit/system/upgrade.php');
			reportit_system_upgrade($old['version']);

			// re-register plugins hooks
			plugin_reportit_install();
		}

		$info = plugin_reportit_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='reportit'");

		db_execute_prepared('UPDATE plugin_config SET
			name = ?, author = ?, webpage = ?, version = ?
			WHERE id = ?',
			[
				$info['longname'],
				$info['author'],
				$info['homepage'],
				$info['version'],
				$id
			]
		);

		return true;
	}

	return true;
}

/**
 * Reports whether this plugin's dependencies/requirements are
 * satisfied. Called by Cacti's plugin architecture when checking
 * whether the plugin can be enabled/upgraded.
 *
 * @return bool Always true (this plugin declares no unmet
 *              requirements).
 */
function reportit_upgrade_requirements() {
	return true;
}

/**
 * Draw_navigation_text hook: registers the breadcrumb/navigation title
 * entries for this plugin's report list/edit/data-item pages and their
 * sub-views. Called by Cacti's navigation framework via the
 * 'draw_navigation_text' hook.
 *
 * @param array $nav The navigation entries array being built up.
 *
 * @return array The $nav array with this plugin's entries added.
 */
function reportit_draw_navigation_text($nav) {
	$nav['reportsit.php:'] = [
		'title'   => __('Reports', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'reportsit.php',
		'level'   => '1'];

	$nav['reportsit.php:save'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'templates.php',
		'level'   => '2'];

	$nav['reportsit.php:report_add'] = [
		'title'   => __('Add', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'templates.php',
		'level'   => '2'];

	$nav['reportsit.php:report_edit'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'templates.php',
		'level'   => '2'];

	$nav['reportsit.php:actions'] = [
		'title'   => __('Actions', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'templates.php',
		'level'   => '2'];

	$nav['rrdlist.php:'] = [
		'title'   => __('Data Items', 'reportit'),
		'mapping' => 'index.php:,reportsit.php:',
		'url'     => 'templates.php',
		'level'   => '2'];

	$nav['rrdlist.php:save'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,reportsit.php:,rrdlist.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['rrdlist.php:rrdlist_edit'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,reportsit.php:,rrdlist.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['rrdlist.php:actions'] = [
		'title'   => __('Actions', 'reportit'),
		'mapping' => 'index.php:,reportsit.php:,rrdlist.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['items.php:'] = [
		'title'   => __('Add', 'reportit'),
		'mapping' => 'index.php:,reportsit.php:,rrdlist.php:',
		'url'     => 'templates.php',
		'level'   => '3'];

	$nav['items.php:save'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,reportsit.php:,rrdlist.php:',
		'url'     => '',
		'level'   => '4'];

	$nav['templates.php:'] = [
		'title'   => __('Report Templates', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'templates.php',
		'level'   => '1'];

	$nav['templates.php:save'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:template_edit'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:template_new'] = [
		'title'   => __('Add', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:template_import_wizard'] = [
		'title'   => __('Import', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:template_upload_wizard'] = [
		'title'   => __('Import', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:template_import'] = [
		'title'   => __('Export', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:template_export'] = [
		'title'   => __('Export', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:template_export_wizard'] = [
		'title'   => __('Export', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['templates.php:actions'] = [
		'title'   => __('Actions', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['measurands.php:'] = [
		'title'   => __('Metrics', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => 'templates.php',
		'level'   => '2'];

	$nav['measurands.php:save'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:,measurands.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['measurands.php:measurand_edit'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:,measurands.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['measurands.php:actions'] = [
		'title'   => __('Actions', 'reportit'),
		'mapping' => 'index.php:,templates.php:,measurands.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['variables.php:'] = [
		'title'   => __('Variables', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url'     => 'templates.php',
		'level'   => '2'];

	$nav['variables.php:save'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:,variables.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['variables.php:variable_edit'] = [
		'title'   => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:,variables.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['variables.php:actions'] = [
		'title'   => __('Actions', 'reportit'),
		'mapping' => 'index.php:,templates.php:,variables.php:',
		'url'     => '',
		'level'   => '3'];

	$nav['run.php:calculation'] = [
		'title'   => __('Report Calculation', 'reportit'),
		'mapping' => 'index.php:,reportsit.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['view.php:'] = [
		'title'   => __('Public Reports', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'view.php',
		'level'   => '1'];

	$nav['view.php:show_report'] = [
		'title'   => __('Show Report', 'reportit'),
		'mapping' => 'index.php:,view.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['view.php:export'] = [
		'title'   => __('Export Report', 'reportit'),
		'mapping' => 'index.php:,view.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['view.php:show_graphs'] = [
		'title'   => __('Show Report', 'reportit'),
		'mapping' => 'index.php:,view.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['charts.php:'] = [
		'title'   => __('Public Report Charts', 'reportit'),
		'mapping' => 'index.php:',
		'url'     => 'graph.php',
		'level'   => '1'];

	$nav['charts.php:bar'] = [
		'title'   => __('Bar Chart', 'reportit'),
		'mapping' => 'index.php:,graph.php:',
		'url'     => '',
		'level'   => '2'];

	$nav['charts.php:pie'] = [
		'title'   => __('Pie Chart', 'reportit'),
		'mapping' => 'index.php:,graph.php:',
		'url'     => '',
		'level'   => '2'];

	return $nav;
}

/**
 * Config_arrays hook: defines this plugin's constants, augments Cacti's
 * role system to grant the ReportIt realms to the appropriate roles,
 * registers its Management/Templates menu entries when enabled, and
 * registers its template-editing error messages. Called by Cacti's
 * plugin framework via the 'config_arrays' hook on every page load.
 *
 * @return void
 *
 * @global array $user_auth_realms          Reserved/declared for parity
 *                                         with other functions in this
 *                                         file; not used directly here.
 * @global array $user_auth_realm_filenames Reserved/declared for parity
 *                                         with other functions in this
 *                                         file; not used directly here.
 * @global array $menu                      Cacti's admin menu registry;
 *                                         appended with this plugin's
 *                                         entries when enabled.
 * @global array $messages                  Cacti's message-code
 *                                         registry; appended with this
 *                                         plugin's template-editing
 *                                         error messages when enabled.
 */
function reportit_config_arrays() {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $messages;

	reportit_define_constants();

	if (function_exists('auth_augment_roles')) {
		auth_augment_roles(__('Normal User'), ['view.php,charts.php']);
		auth_augment_roles(__('General Administration'), ['reportsit.php']);
		auth_augment_roles(__('System Administration'), ['templates.php', 'measurands.php', 'variables.php']);
	}

	// show additional menu entries if plugin is enabled
	if (api_plugin_is_enabled('reportit')) {
		$menu[__('Management')]['plugins/reportit/reportit.php']  = __('ReportIt Reports', 'reportit');
		$menu[__('Templates')]['plugins/reportit/templates.php']  = __('ReportIt', 'reportit');

		$temp = [
			'reportit_templates__1' => [
				'message' => __('No data source item selected', 'reportit'),
				'type'    => 'error'
			],
			'reportit_templates__2' => [
				'message' => __('Unselected data source items are still in use', 'reportit'),
				'type'    => 'error'
			],
			'reportit_templates__3' => [
				'message' => __('Unable to unlock this template without defined measurands', 'reportit'),
				'type'    => 'error'
			],
		];

		$messages += $temp;
	}
}

/**
 * Config_settings hook: registers this plugin's 'ReportIt' settings tab
 * and all of its configuration fields (CSV export formatting, date/time
 * display mode, operator role requirements, export folders, execution
 * limits, and related report/scheduling defaults). Called by Cacti's
 * settings framework via the 'config_settings' hook.
 *
 * @return void
 *
 * @global array $tabs           Cacti's settings tabs registry;
 *                              appended with this plugin's tab.
 * @global array $tabs_graphs    Populated here with this plugin's
 *                              'Report General Settings' graph-tab
 *                              label.
 * @global array $settings       Cacti's settings fields registry;
 *                              appended with this plugin's fields.
 * @global array $settings_user  Populated here (merged with any
 *                              existing entries) with this plugin's
 *                              per-user setting fields.
 * @global array $item_rows      Cacti's standard row-count option list,
 *                              used for row-count settings.
 */
function reportit_config_settings() {
	global $tabs, $tabs_graphs, $settings, $settings_user, $item_rows;

	// presets
	$datetime              = [__('local', 'reportit'), __('global', 'reportit')];
	$csv_column_separator  = [',', ';', 'Tab', 'Blank'];
	$csv_decimal_separator = [',', '.'];

	$operator = [
		__('Power User (Report Owner)', 'reportit'),
		__('Super User (Report Admin)', 'reportit')
	];

	// setup ReportIt's global configuration area
	$tabs['reports'] = __('Reports', 'reportit');

	$temp = [
		'reportit_header1' => [
			'friendly_name' => __('General', 'reportit'),
			'method'        => 'spacer',
			'collapsible'   => 'true'
		],
		'reportit_met' => [
			'friendly_name' => __('Maximum Execution Time (in seconds)', 'reportit'),
			'description'   => __('Optional: Maximum execution time of one calculation.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '4',
			'default'       => '300',
		],
		'reportit_maxrrdchg' => [
			'friendly_name' => __('Maximum Record Count Change', 'reportit'),
			'description'   => __('Optional (Auto-Generate RRD List): Do not change RRD List of any Report if Record Count Change is greater than this Number This is to avoid unwanted and disastrous changes on RRD Lists', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '4',
			'default'       => '100',
		],
		'reportit_use_tmz' => [
			'friendly_name' => __('Time Zones', 'reportit'),
			'description'   => __('Enable/Disable the use of time zones for Data Item\'s configuration and report calculation.  In the former case server time has to be set up to GMT/UTC!', 'reportit'),
			'method'        => 'checkbox',
			'default'       => '',
		],
		'reportit_show_tmz' => [
			'friendly_name' => __('Show Local Time Zone', 'reportit'),
			'description'   => __('Enable/Disable to display server\'s timezone on the headlines.', 'reportit'),
			'method'        => 'checkbox',
			'default'       => 'on',
		],
		'reportit_operator' => [
			'friendly_name' => __('Allow Scheduling by Operators', 'reportit'),
			'description'   => __('Enable/Disable Operator\'s ability to Schedule Reports.  When Disabled, only Administrators may change scheduling.', 'reportit'),
			'method'        => 'checkbox',
			'default'       => '',
		],
		'reportit_use_IEC' => [
			'friendly_name' => __('SI-Prefixes', 'reportit'),
			'description'   => __('Enable/Disable the use of correct SI-Prefixes for binary multiples under the terms of <a href=\'http://www.ieee.org\'>IEEE 1541</a> and <a href=\'http://www.iec.ch/zone/si/si_bytes.htm\'>IEC 60027-2</a>.', 'reportit'),
			'method'        => 'checkbox',
			'default'       => 'on',
		],
		'reportit_header5' => [
			'friendly_name' => __('Auto E-Mailing', 'reportit'),
			'method'        => 'spacer',
			'collapsible'   => 'true'
		],
		'reportit_email' => [
			'friendly_name' => __('Enable', 'reportit'),
			'description'   => __('If enabled scheduled reports can be emailed automatically to a list of recipients.<br> This feature requires a configured version of the \'Settings Plugin\'.', 'reportit'),
			'method'        => 'checkbox',
			'default'       => '',
		]
	];

	if (isset($settings['reports']) && cacti_sizeof($settings['reports'])) {
		$settings['reports'] = array_merge($settings['reports'], $temp);
	} else {
		$settings['reports'] = $temp;
		unset($temp);
	}

	$user_temp = [
		'reportit_view_filter' => [
			'friendly_name' => __('Separate Report View Filter', 'reportit'),
			'description'   => __('Enable/disable the use of an individual filter per report.', 'reportit'),
			'method'        => 'checkbox',
			'default'       => 'on',
		],
		'reportit_max_rows' => [
			'friendly_name' => __('Rows Per Page', 'reportit'),
			'description'   => __('The number of rows to display on a single page.', 'reportit'),
			'method'        => 'drop_array',
			'array'         => $item_rows,
			'default'       => '25',
		],
		'reportit_csv_header' => [
			'friendly_name' => __('Report Export Settings', 'reportit'),
			'method'        => 'spacer',
			'collapsible'   => 'true'
		],
		'reportit_csv_column_s' => [
			'friendly_name' => __('CSV Column Separator', 'reportit'),
			'description'   => __('The column separator to be used for CSV exports.', 'reportit'),
			'method'        => 'drop_array',
			'array'         => $csv_column_separator,
			'default'       => 0,
		],
		'reportit_csv_decimal_s' => [
			'friendly_name' => __('CSV Decimal Separator', 'reportit'),
			'description'   => __('The symbol indicating the end of the integer part and the beginning of the fractional part.', 'reportit'),
			'method'        => 'drop_array',
			'array'         => $csv_decimal_separator,
			'default'       => 0,
		],
	];

	if (isset($settings_user['reportit']) && cacti_sizeof($settings_user['reportit'])) {
		$settings_user['reportit'] = array_merge($settings_user['reportit'], $user_temp);
	} else {
		$settings_user['reportit'] = $user_temp;
	}

	$tabs_graphs['reportit'] = __('Report General Settings', 'reportit');

	/**
	 * default user settings will be placed into settings table as system
	 * defaults.
	 */
	if (isset($settings['reports']) && cacti_sizeof($settings['reports'])) {
		$settings['reports'] = array_merge($settings['reports'], $user_temp);
	} else {
		$settings['reports'] = $user_temp;
	}

	unset($user_temp);

	foreach ($settings['reports'] as $key => $value) {
		if (array_key_exists('default', $value)) {
			if (!db_setting_exists($key)) {
//				set_config_option($key, $value['default']);
			}
		}
	}
}

/**
 * Checks whether a named row exists in Cacti's core 'settings' table.
 * Called from setting-migration/compatibility code before reading or
 * writing a setting that may not exist on older installations.
 *
 * @param string $setting The settings table 'name' column value to
 *                        check.
 *
 * @return bool True if a row with that name exists, false otherwise.
 */
function db_setting_exists($setting) {
	$results = db_fetch_row_prepared('SELECT * FROM settings WHERE name = ?', [$setting]);

	if (cacti_sizeof($results)) {
		return true;
	} else {
		return false;
	}
}

/**
 * Top_header_tabs/top_graph_header_tabs hook: triggers a schema upgrade
 * check, then prints the ReportIt tab icon/link in Cacti's page header
 * for users authorized to view reports, using the 'down' (active) icon
 * when currently viewing view.php. Called by Cacti's header rendering
 * via the 'top_header_tabs'/'top_graph_header_tabs' hooks.
 *
 * @return void
 */
function reportit_show_tab() {
	reportit_check_upgrade();

	if (api_user_realm_auth('view.php')) {
		print '<a href="' . CACTI_PATH_URL . 'plugins/reportit/view.php"><img src="' . CACTI_PATH_URL . 'plugins/reportit/images/tab_reportit_' . (get_current_page() == 'view.php' ? 'down' : 'up') . '.png" alt="' . __('ReportIt', 'reportit') . '"></a>';
	}
}

/**
 * Includes and runs this plugin's install system script to create its
 * database schema. Called from plugin_reportit_install().
 *
 * @return void
 */
function reportit_system_setup() {
	require_once(CACTI_PATH_BASE . '/plugins/reportit/system/install.php');

	reportit_system_install();
}

/**
 * Defines a PHP constant only if it isn't already defined, suppressing
 * any redefinition warning. Called from reportit_define_constants() to
 * safely (re-)establish this plugin's constants on every page load.
 *
 * @param string $constant The constant name to define.
 * @param mixed  $value    The value to assign if not already defined.
 *
 * @return void
 */
function reportit_define($constant, $value) {
	if (!defined($constant)) {
		@define($constant, $value);
	}
}

/**
 * Defines this plugin's runtime constants: dynamically resolved realm
 * ids (viewer/owner/admin) based on the plugin's registered realms,
 * and its base/temp/archive/export filesystem paths. Called from
 * reportit_config_arrays() on every page load.
 *
 * @return void
 */
function reportit_define_constants() {
	// realm IDs which have been defined dynamically by PIA 2.x
	$view = db_fetch_cell("SELECT id
		FROM plugin_realms
		WHERE plugin='reportit'
		AND file LIKE '%view.php%'");

	$create = db_fetch_cell("SELECT id
		FROM plugin_realms
		WHERE plugin='reportit'
		AND file LIKE '%reportit.php%'");

	$administrate = db_fetch_cell("SELECT id
		FROM plugin_realms
		WHERE plugin='reportit'
		AND file LIKE '%templates.php%'");

	reportit_define('REPORTIT_USER_VIEWER', 100 + $view);
	reportit_define('REPORTIT_USER_OWNER', 100 + $create);
	reportit_define('REPORTIT_USER_ADMIN', 100 + $administrate);

	// define ReportIt's base paths
	reportit_define('REPORTIT_BASE_PATH', CACTI_PATH_BASE . '/plugins/reportit');

	reportit_define('CACTI_BASE_PATH', CACTI_PATH_BASE);
	reportit_define('CACTI_INCLUDE_PATH', CACTI_BASE_PATH . '/include/');

	// path where PCLZIP will save temporary files
	reportit_define('REPORTIT_TMP_FD', REPORTIT_BASE_PATH . '/tmp/');
	// path where archives will be saved per default
	reportit_define('REPORTIT_ARC_FD', REPORTIT_BASE_PATH . '/archive/');
	// path where exports will be saved per default
	reportit_define('REPORTIT_EXP_FD', REPORTIT_BASE_PATH . '/exports/');
}

/**
 * Poller_bottom hook: purges expired report cache tables/rows past
 * their configured lifecycle, then checks all enabled reports for ones
 * due to run per their schedule, queuing them and launching a
 * background poller_reportit.php process to execute the queue. Called
 * by Cacti's poller via the 'poller_bottom' hook.
 *
 * @return void
 */
function reportit_poller_bottom() {
	require_once(CACTI_PATH_LIBRARY . '/api_scheduler.php');
	require_once(CACTI_PATH_LIBRARY . '/reports.php');

	$str   = '';
	$ids   = '';
	$cnt   = 0;
	$start = microtime(true);

	$lifecycle     = read_config_option('reportit_arc_lifecycle', true);
	$logging_level = read_config_option('log_verbosity', true);

	// user did not save plugin settings, variable $lifecycle doesn't exist
	if (intval($lifecycle) <= 0) {
		$lifecycle = 300;
	}

	// mark running reports which have run too long as failed
	$met = read_config_option('reportit_met');
	$met = intval($met);

	if ($met < 1) {
		$met = 300;
	}

	// fetch all tables whose life cycle has been expired
	$tables = db_fetch_assoc("SHOW TABLE STATUS
		WHERE `Name` LIKE 'plugin_reportit_tmp_%'
		AND (UNIX_TIMESTAMP(`Update_time`) + $lifecycle) <= UNIX_TIMESTAMP()");

	if (cacti_count($tables)) {
		foreach ($tables as $table) {
			// take care that we really do NOT delete others tables
			if (strpos($table['Name'], 'plugin_reportit_tmp_') !== false) {
				$str .= $table['Name'] . ', ';
				$ids .= ",'" . str_replace('plugin_reportit_tmp_', '', $table['Name']) . "'";
				$cnt++;
			}
		}

		if ($cnt > 0) {
			$ids = substr($ids, 1);
			$str = substr($str, 0, -2);

			if (db_execute("DROP TABLE IF EXISTS $str") == 1) {
				db_execute("DELETE FROM plugin_reportit_cache_reports WHERE `cache_id` IN ($ids)");
				db_execute("DELETE FROM plugin_reportit_cache_variables WHERE `cache_id` IN ($ids)");
				db_execute("DELETE FROM plugin_reportit_cache_measurands WHERE `cache_id` IN ($ids)");

				if ($cnt >= 5) {
					db_execute('OPTIMIZE TABLE `plugin_reportit_cache_reports`');
					db_execute('OPTIMIZE TABLE `plugin_reportit_cache_variables`');
					db_execute('OPTIMIZE TABLE `plugin_reportit_cache_measurands`');
				}
			} else {
				if ($logging_level != 'POLLER_VERBOSITY_LOW') {
					cacti_log('WARNING: Unable to clean up report cache', false, 'REPORTIT');
				}
			}
		}
	}

	$lastrun   = read_config_option('reportit_lastrun');
	$now       = time();
	$scheduled = 0;

	if (empty($lastrun)) {
		set_config_option('reportit_lastrun', $now);
		exit(0);
	}

	set_config_option('reportit_lastrun', $now);

	$php_binary = read_config_option('path_php_binary');

	$queued = [];

	$reports = db_fetch_assoc('SELECT * FROM plugin_reportit_reports WHERE enabled = "on"');

	reports_log('Cacti ReportIt Reports reports found: ' . cacti_sizeof($reports), true, 'REPORTS', POLLER_VERBOSITY_MEDIUM);

	if (cacti_sizeof($reports)) {
		foreach ($reports as $report) {
			if (api_scheduler_is_time_to_start($report, 'plugin_reportit_reports')) {
				reports_log('Reports processing report: ' . $report['name'], true, 'REPORTS', POLLER_VERBOSITY_MEDIUM);

				$queued[] = reportit_schedule_report($report);
			}
		}
	}

	if (cacti_sizeof($queued)) {
		exec_background($php_binary, CACTI_PATH_BASE . '/plugins/reportit/poller_reportit.php --scheduled');
	}

	$end = microtime(true);

	// only log when things happen
	if ($cnt > 0 || $scheduled > 0) {
		$stats = sprintf('REPORTIT STATS: Time:%0.2f CacheLifetime:%s CachePurged:%s DispatchedReports:%s', $end - $start, $lifecycle, $cnt, $scheduled);

		cacti_log($stats, false, 'SYSTEM');
	}
}

/**
 * Queues a single due report for execution by inserting a run-queue
 * entry, building the CLI command that will invoke poller_reportit.php
 * for it, and returning bookkeeping info for the batch dispatch step.
 * Called from reportit_poller_bottom() for each report found due to
 * run.
 *
 * @param array $report Reference, the plugin_reportit_reports row to
 *                      schedule.
 *
 * @return mixed The queued report's tracking info (used by the caller
 *               to know a report was queued).
 */
function reportit_schedule_report(&$report) {
	require_once(CACTI_PATH_BASE . '/plugins/reportit/lib/funct_runtime.php');

	$command  = read_config_option('path_php_binary');
	$command .= ' ' . CACTI_PATH_BASE . '/plugins/reportit/poller_reportit.php';

	$id     = $report['id'];
	$name   = $report['name'];
	$notify = $report['notify_list'];
	$from   = [];

	if (isset($report['from_email']) && $report['from_email'] != '') {
		$from_email = $report['from_email'];
	} else {
		$from_email = read_config_option('settings_from_email');
	}

	if (isset($report['from_name']) && $report['from_name'] != '') {
		$from_name = $report['from_name'];
	} else {
		$from_name = read_config_option('settings_from_name');
	}

	if ($from_email != '' && $from_name != '') {
		$from['email'] = $from_email;
		$from['name']  = $from_name;
	}

	$to_emails = db_fetch_assoc_prepared('SELECT email, name
		FROM plugin_reportit_recipients
		WHERE report_id = ?',
		[$report['id']]);

	if ($report['email'] != '') {
		$emails = explode(',', $report['email']);
		$emails = array_map('trim', $emails);

		$to_emails += $emails;
	}

	if ($report['bcc'] != '') {
		$bcc_emails = explode(',', $report['bcc']);
		$bcc_emails = array_map('trim', $bcc_emails);
	} else {
		$bcc_emails = [];
	}

	if (isset($report['reply_to'])) {
		$reply_to = $report['reply_to'];
	} else {
		$reply_to = '';
	}

	$notification = [];

	if (cacti_sizeof($to_emails) || cacti_sizeof($bcc_emails)) {
		$notification['email']['to_email']  = $to_emails;
		$notification['email']['bcc_email'] = $bcc_emails;
		$notification['email']['reply_to']  = $reply_to;
		$notification['email']['from']      = $from;
	}

	if ($notify > 0) {
		$notification['notification_list']['id']       = $notify;
		$notification['notification_list']['reply_to'] = $reply_to;
		$notification['notification_list']['from']     = $from;
	}

	return reports_queue($name, 1, 'reportit', $id, $command, $notification);
}

/**
 * Clog_regex_array hook: registers regex patterns that let Cacti's log
 * viewer (clog) turn 'RIReport[...]'/'RIDataItem[...]' tokens in log
 * lines into clickable links to the relevant report/data item edit
 * pages. Called by Cacti's log viewer via the 'clog_regex_array' hook.
 *
 * @param array $regex_array The list of regex/callback definitions
 *                           being built up.
 *
 * @return array The $regex_array with this plugin's patterns added.
 */
function reportit_clog_regex_array($regex_array) {
	$regex_array[] = ['name' => 'RIReport', 'regex' => '( RIReport\[)([, \d]+)(\])', 'func' => 'reportit_clog_regex_report'];
	$regex_array[] = ['name' => 'RIDataItem', 'regex' => '( RIDataItem\[)([, \d]+)(\])', 'func' => 'reportit_clog_regex_dataitem'];

	return $regex_array;
}

/**
 * Regex-match callback resolving one or more report ids embedded in a
 * clog 'RIReport[id,id,...]' token into HTML links (labeled with each
 * report's name where known) to its edit page. Called by Cacti's log
 * viewer for each 'RIReport[...]' match registered via
 * reportit_clog_regex_array().
 *
 * @param array $matches The regex match groups: [0] full match, [1]
 *                       leading token text, [2] comma-separated report
 *                       ids, [3] trailing token text.
 *
 * @return string The rendered HTML replacement with report links.
 */
function reportit_clog_regex_report($matches) {
	$result = $matches[0];

	$report_ids = explode(',', str_replace(' ', '', $matches[2]));

	if (cacti_sizeof($report_ids)) {
		$result  = '';
		$reports = db_fetch_assoc_prepared('SELECT id, name
			FROM plugin_reportit_reports
			WHERE id in (?)',
			[implode(',',$report_ids)]);

		$reportDescriptions = [];

		if (cacti_sizeof($reports)) {
			foreach ($reports as $report) {
				$reportDescriptions[$report['id']] = html_escape($report['name']);
			}
		}

		foreach ($report_ids as $report_id) {
			$result .= $matches[1] . '<a href=\'' . html_escape(CACTI_PATH_URL . 'plugins/reportit/reportit.php?action=report_edit&id=' . $report_id) . '\'>' . (isset($reportDescriptions[$report_id]) ? $reportDescriptions[$report_id] : $report_id) . '</a>' . $matches[3];
		}
	}

	return $result;
}

/**
 * Regex-match callback resolving one or more data item ids embedded in
 * a clog 'RIDataItem[id,id,...]' token into HTML links (labeled with
 * each item's cached name where known) to its edit page. Called by
 * Cacti's log viewer for each 'RIDataItem[...]' match registered via
 * reportit_clog_regex_array().
 *
 * @param array $matches The regex match groups: [0] full match, [1]
 *                       leading token text, [2] comma-separated data
 *                       item ids, [3] trailing token text.
 *
 * @return string The rendered HTML replacement with data item links.
 */
function reportit_clog_regex_dataitem($matches) {
	$result = $matches[0];

	$dataitem_ids = explode(',',str_replace(' ','',$matches[2]));

	if (cacti_sizeof($dataitem_ids)) {
		$result    = '';
		$dataitems = db_fetch_assoc_prepared('SELECT a.id, a.report_id, b.name_cache as description
			FROM plugin_reportit_data_items AS a
			LEFT JOIN data_template_data AS b
			ON b.local_data_id = a.id
			WHERE a.id in (?)',
			[implode(',',$dataitem_ids)]);

		$dataitemDescriptions = [];
		$dataitemReports      = [];

		if (cacti_sizeof($dataitems)) {
			foreach ($dataitems as $dataitem) {
				$dataitemReports[$dataitem['id']]      = $dataitem['report_id'];
				$dataitemDescriptions[$dataitem['id']] = html_escape($dataitem['description']);
			}
		}

		foreach ($dataitem_ids as $dataitem_id) {
			$result .= $matches[1] . '<a href=\'' . html_escape(CACTI_PATH_URL . 'plugins/reportit/rrdlist.php?action=rrdlist_edit&id=' . $dataitem_id) . '\'>' . (isset($dataitemDescriptions[$dataitem_id]) ? $dataitemDescriptions[$dataitem_id] : $dataitem_id) . '</a>' . $matches[3];
		}
	}

	return $result;
}
