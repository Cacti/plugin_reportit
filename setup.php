<?php
/*
   +-------------------------------------------------------------------------+
   | Copyright (C) 2004-2017 The Cacti Group                                 |
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

function plugin_reportit_install() {
	api_plugin_register_hook('reportit', 'page_head',             'reportit_page_head',            'setup.php');
    api_plugin_register_hook('reportit', 'top_header_tabs',       'reportit_show_tab',             'setup.php');
    api_plugin_register_hook('reportit', 'top_graph_header_tabs', 'reportit_show_tab',             'setup.php');
    api_plugin_register_hook('reportit', 'draw_navigation_text',  'reportit_draw_navigation_text', 'setup.php');
    api_plugin_register_hook('reportit', 'config_arrays',         'reportit_config_arrays',        'setup.php');
    api_plugin_register_hook('reportit', 'config_settings',       'reportit_config_settings',      'setup.php');
    api_plugin_register_hook('reportit', 'poller_bottom',         'reportit_poller_bottom',        'setup.php');

	reportit_setup_table();
}

function plugin_reportit_uninstall() {
	db_execute('DROP TABLE IF EXISTS reportit_cache_measurands');
	db_execute('DROP TABLE IF EXISTS reportit_cache_reports');
	db_execute('DROP TABLE IF EXISTS reportit_cache_variables');
	db_execute('DROP TABLE IF EXISTS reportit_data_items');
	db_execute('DROP TABLE IF EXISTS reportit_data_source_items');
	db_execute('DROP TABLE IF EXISTS reportit_measurands');
	db_execute('DROP TABLE IF EXISTS reportit_presets');
	db_execute('DROP TABLE IF EXISTS reportit_recipients');
	db_execute('DROP TABLE IF EXISTS reportit_reports');
	db_execute('DROP TABLE IF EXISTS reportit_rvars');
	db_execute('DROP TABLE IF EXISTS reportit_templates');
	db_execute('DROP TABLE IF EXISTS reportit_variables');

	return true;
}

function plugin_reportit_check_config() {
	return reportit_check_upgrade();
}

function plugin_reportit_upgrade() {
    reportit_check_upgrade();
    return true;
}

function plugin_reportit_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/reportit/INFO', true);
	return $info['info'];
}

function reportit_check_upgrade() {
	global $config;

	$files = array('index.php', 'plugins.php', 'runtime.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = plugin_reportit_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='reportit'");
	$tables  = db_fetch_assoc("SHOW TABLE STATUS WHERE `Name` LIKE 'reportit%'");

	if (sizeof($old) && $current == $old['version']){
		/* ReportIT is up to date */
		return true;
	}elseif (sizeof($old) && $current != $old['version']) {
		if ($old['status'] == 1 || $old['status'] == 4) {
			/* install new tables */
			reportit_setup_table();

			/* perform data base upgrade */
			reportit_database_upgrade($old['version']);

			/* re-register plugins hooks */
			plugin_reportit_install();
		}

		$info = plugin_reportit_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='reportit'");

		db_execute_prepared('UPDATE plugin_config SET
			name = ?, author = ?, webpage = ?, version = ?
			WHERE id = ?',
			array(
				$info['longname'],
				$info['author'],
				$info['homepage'],
				$info['version'],
				$id
			)
		);

		return true;
	}
}

function reportit_upgrade_requirements() {
	return true;
}

function reportit_database_upgrade($action) {
    global $config, $database_default;

    if ($action == 'old_structure') {
        include_once($config['base_path'] . '/plugins/reportit/upgrade/0_4_0_to_0_7_0.php');
        upgrade_reportit_0_4_0_to_0_7_0();
    	include_once($config['base_path'] . '/plugins/reportit/upgrade/0_7_0_to_0_7_2.php');
    	upgrade_reportit_0_7_0_to_0_7_2();
    }elseif ($action == 'post-installation') {
		include_once($config['base_path'] . '/plugins/reportit/upgrade/0_4_0_to_0_7_0.php');
		upgrade_pia_1x_to_pia_2x();
    }elseif ($action == '0.7.0' || $action == '0.7.1') {
    	include_once($config['base_path'] . '/plugins/reportit/upgrade/0_7_0_to_0_7_2.php');
		upgrade_reportit_0_7_0_to_0_7_2();
    }elseif ($action == '0.7.2' || $action == '0.7.3') {
    	include_once($config['base_path'] . '/plugins/reportit/upgrade/0_7_2_to_0_7_4.php');
    	upgrade_reportit_0_7_2_to_0_7_4();
    }
}

function reportit_draw_navigation_text ($nav) {
    $nav['cc_reports.php:'] = array(
		'title' => __('Reports'),
		'mapping' => 'index.php:',
		'url' => 'cc_reports.php',
		'level' => '1');

    $nav['cc_reports.php:save'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,?',
		'url' => 'cc_templates.php',
		'level' => '2');

    $nav['cc_reports.php:report_add'] = array(
		'title' => __('Add'),
		'mapping' => 'index.php:,?',
		'url' => 'cc_templates.php',
		'level' => '2');

    $nav['cc_reports.php:report_edit'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,?',
		'url' => 'cc_templates.php',
		'level' => '2');

    $nav['cc_reports.php:actions'] = array(
		'title' => __('Actions'),
		'mapping' => 'index.php:,?',
		'url' => 'cc_templates.php',
		'level' => '2');

    $nav['cc_rrdlist.php:'] = array(
		'title' => __('Data Items'),
		'mapping' => 'index.php:,cc_reports.php:',
		'url' => 'cc_templates.php',
		'level' => '2');

    $nav['cc_rrdlist.php:save'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_reports.php:,cc_rrdlist.php:',
		'url' => '',
		'level' => '3');

    $nav['cc_rrdlist.php:rrdlist_edit'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_reports.php:,cc_rrdlist.php:',
		'url' => '',
		'level' => '3');

    $nav['cc_rrdlist.php:actions'] = array(
		'title' => __('Actions'),
		'mapping' => 'index.php:,cc_reports.php:,cc_rrdlist.php:',
		'url' => '',
		'level' => '3');

    $nav['cc_items.php:'] = array(
		'title' => __('Add'),
		'mapping' => 'index.php:,cc_reports.php:,cc_rrdlist.php:',
		'url'  => 'cc_templates.php',
		'level' => '3');

    $nav['cc_items.php:save'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_reports.php:,cc_rrdlist.php:',
		'url' => '',
		'level' => '4');

    $nav['cc_templates.php:'] = array(
		'title' => __('Report Templates'),
		'mapping' => 'index.php:',
		'url' => 'cc_templates.php',
		'level' => '1');

    $nav['cc_templates.php:save'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:template_edit'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:template_new'] = array(
		'title' => __('Add'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:template_import_wizard'] = array(
		'title' => __('Import'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:template_upload_wizard'] = array(
		'title' => __('Import'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:template_import'] = array(
		'title' => __('Export'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:template_export'] = array(
		'title' => __('Export'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:template_export_wizard'] = array(
		'title' => __('Export'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_templates.php:actions'] = array(
		'title' => __('Actions'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => '',
		'level' => '2');

    $nav['measurands.php:'] = array(
		'title' => __('Measurands'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => 'cc_templates.php',
		'level' => '2');

    $nav['measurands.php:save'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_templates.php:,measurands.php:',
		'url' => '',
		'level' => '3');

    $nav['measurands.php:measurand_edit'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_templates.php:,measurands.php:',
		'url' => '',
		'level' => '3');

    $nav['measurands.php:actions'] = array(
		'title' => __('Actions'),
		'mapping' => 'index.php:,cc_templates.php:,measurands.php:',
		'url' => '',
		'level' => '3');

    $nav['variables.php:'] = array(
		'title' => __('Variables'),
		'mapping' => 'index.php:,cc_templates.php:',
		'url' => 'cc_templates.php',
		'level' => '2');

    $nav['variables.php:save'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_templates.php:,variables.php:',
		'url' => '',
		'level' => '3');

    $nav['variables.php:variable_edit'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,cc_templates.php:,variables.php:',
		'url' => '',
		'level' => '3');

    $nav['variables.php:actions'] = array(
		'title' => __('Actions'),
		'mapping' => 'index.php:,cc_templates.php:,variables.php:',
		'url' => '',
		'level' => '3');

    $nav['cc_run.php:calculation'] = array(
		'title' => __('Report Calculation'),
		'mapping' => 'index.php:,cc_reports.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_view.php:'] = array(
		'title' => __('Public Reports'),
		'mapping' => 'index.php:',
		'url' => 'cc_view.php',
		'level' => '1');

    $nav['cc_view.php:show_report'] = array(
		'title' => __('Show Report'),
		'mapping' => 'index.php:,cc_view.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_view.php:export'] = array(
		'title' => __('Export Report'),
		'mapping' => 'index.php:,cc_view.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_view.php:show_graphs'] = array(
		'title' => __('Show Report'),
		'mapping' => 'index.php:,cc_view.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_charts.php:'] = array(
		'title' => __('Public Report Charts'),
		'mapping' => 'index.php:',
		'url' => 'cc_graph.php',
		'level' => '1');

    $nav['cc_charts.php:bar'] = array(
		'title' => __('Bar Chart'),
		'mapping' => 'index.php:,cc_graph.php:',
		'url' => '',
		'level' => '2');

    $nav['cc_charts.php:pie'] = array(
		'title' => __('Pie Chart'),
		'mapping' => 'index.php:,cc_graph.php:',
		'url' => '',
		'level' => '2');

    return $nav;
}

function reportit_config_arrays() {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $messages;

	/* register all realms of ReportIT */
	api_plugin_register_realm('reportit', 'cc_view.php,cc_charts.php', __('View Reports'), 1);
	api_plugin_register_realm('reportit', 'cc_reports.php,cc_rrdlist.php,cc_items.php,cc_run.php', __('Create Reports'), 1);
	api_plugin_register_realm('reportit', 'templates.php,measurands.php,variables.php', __('Administrate Reports'), 1);

	/* show additional menu entries if plugin is enabled */
	if (api_plugin_is_enabled('reportit')) {
		$menu[__('Management')]['plugins/reportit/cc_reports.php']  = __('Reports');
		$menu[__('Templates')]['plugins/reportit/templates.php'] = __('Report');

		$temp = array(
			'reportit_templates__1' => array(
				'message' => __('No data source item selected', 'reportit'),
				'type' => 'error'),
			'reportit_templates__2' => array(
				'message' => __('Unselected data source items are still in use', 'reportit'),
				'type' => 'error'),
			'reportit_templates__3' => array(
				'message' => __('Unable to unlock this template without defined measurands'),
				'type' => 'error'),
		);
		$messages += $temp;
	}
}

function reportit_config_settings() {
    reportit_define_constants();

    global $tabs, $tabs_graphs, $settings, $graph_dateformats, $graph_datechar, $settings_graphs, $config;

	/* presets */
	$datetime              = array(__('local'), __('global'));
	$csv_column_separator  = array(',', ';', 'Tab', 'Blank');
	$csv_decimal_separator = array(',', '.');

	$rrdtool_api = array(
		__('PHP BINDINGS (FAST)'),
		__('RRDTOOL CACTI (SLOW)'),
		__('RRDTOOL SERVER (SLOW)')
	);

	$rrdtool_quality = array(
		'2' => __('LOW'),
		'3' => __('MEDIUM'),
		'4' => __('HIGH'),
		'5' => __('ULTIMATE')
	);

	$operator = array(
		__('Power User (Report Owner)'),
		__('Super User (Report Admin)')
	);

    $graphs = array(
		'-10' => __('Bar (vertical)'),
		'10'  => __('Bar (horizontal)'),
		'20'  => __('Line'),
		'21'  => __('Area'),
		'30'  => __('Pie'),
		'40'  => __('Spider')
	);

	$font  = REPORTIT_BASE_PATH . '/lib_ext/fonts/DejaVuSansMono.ttf';
	$tfont = REPORTIT_BASE_PATH . '/lib_ext/fonts/DejaVuSansMono-Bold.ttf';

	/* setup ReportIT's global configuration area */
	$tabs['reports'] = __('Reports');

	$temp =  array(
		'reportit_header1'          => array(
			'friendly_name'         => __('General'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_met'              => array(
			'friendly_name'         => __('Maximum Execution Time (in seconds)'),
			'description'           => __('Optional: Maximum execution time of one calculation.'),
			'method'                => 'textbox',
			'max_length'            => '4',
			'default'               => '300',
		),
		'reportit_maxrrdchg'        => array(
			'friendly_name'         => __('Maximum Record Count Change'),
			'description'           => __('Optional (Auto-Generate RRD List): Do not change RRD List of any Report if Record Count Change is greater than this Number This is to avoid unwanted and disastrous changes on RRD Lists'),
			'method'                => 'textbox',
			'max_length'            => '4',
			'default'               => '100',
		),
		'reportit_use_tmz'          => array(
			'friendly_name'         => __('Time Zones'),
			'description'           => __('Enable/disable the use of time zones for data item\'s configuration and report calculation.  In the former case server time has to be set up to GMT/UTC!'),
			'method'                => 'checkbox',
			'default'               => '',
		),
		'reportit_show_tmz'         => array(
			'friendly_name'         => __('Show Local Time Zone'),
			'description'           => __('Enable/disable to display server\'s timezone on the headlines.'),
			'method'                => 'checkbox',
			'default'               => 'on',
		),
		'reportit_use_IEC'          => array(
			'friendly_name'         => __('SI-Prefixes'),
			'description'           => __('Enable/disable the use of correct SI-Prefixes for binary multiples under the terms of <a href=\'http://www.ieee.org\'>IEEE 1541</a> and <a href=\'http://www.iec.ch/zone/si/si_bytes.htm\'>IEC 60027-2</a>.'),
			'method'                => 'checkbox',
			'default'               => 'on',
		),
		'reportit_operator'         => array(
			'friendly_name'         => __('Operator for Scheduled Reporting'),
			'description'           => __('Choose the level which is necessary to configure all options of scheduled reporting in a report configuration.'),
			'method'                => 'drop_array',
			'array'                 => $operator,
			'default'               => '1',
		),
		'reportit_header2'          => array(
			'friendly_name'         => __('RRDtool'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_API'              => array(
			'friendly_name'         => __('RRDtool Connection'),
			'description'           => __('Choose the way to connect to RRDtool.'),
			'method'                => 'drop_array',
			'array'                 => $rrdtool_api,
			'default'               => '1',
		),
		'reportit_RRDID'            => array(
			'friendly_name'         => __('RRDtool Server IP'),
			'description'           => __('Optional: Configured IP address of the RRDtool server.'),
			'method'                => 'textbox',
			'max_length'            => '15',
			'default'               => '127.0.0.1',
		),
		'reportit_RRDPort'          => array(
			'friendly_name'         => __('RRDtool Server Port'),
			'description'           => __('Optional: Configured port setting of RRDtool server.'),
			'method'                => 'textbox',
			'max_length'            => '5',
			'default'               => '13900',
		),
		'reportit_header3'          => array(
			'friendly_name'         => __('Export Settings'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_exp_filename'     => array(
			'friendly_name'         => __('Filename Format'),
			'description'           => __('The name format for the export files created on demand.'),
			'max_length'            => '100',
			'method'                => 'textbox',
			'default'               => 'cacti_report_<report_id>',
		),
		'reportit_exp_header'       => array(
			'friendly_name'         => __('Export Header'),
			'description'           => __('The header description for export files'),
			'method'                => 'textarea',
			'textarea_rows'         => '3',
			'textarea_cols'         => '60',
			'default'               => __('# Your report header\\n# <cacti_version> <reportit_version>'),
		),
		'reportit_header4'          => array(
			'friendly_name'         => __('Auto Archiving'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_archive'          => array(
			'friendly_name'         => __('Enabled'),
			'description'           => __('If enabled the result of every scheduled report will be archived automatically'),
			'method'                => 'checkbox',
			'default'               => '',
		),
		'reportit_arc_lifecycle'    => array(
			'friendly_name'         => __('Cache Life Cyle (in seconds)'),
			'description'           => __('Number of seconds an archived report will be cached without any hit.'),
			'method'                => 'textbox',
			'max_length'            => '4',
			'default'               => '300',
		),
		'reportit_arc_folder'       => array(
			'friendly_name'         => __('Archive Path'),
			'description'           => __('Optional: The path to an archive folder for saving. If not defined subfolder \'archive\' will be used.'),
			'method'                => 'dirpath',
			'max_length'            => '255',
			'default'               => REPORTIT_ARC_FD,
		),
		'reportit_header5'          => array(
			'friendly_name'         => __('Auto E-Mailing'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_email'            => array(
			'friendly_name'         => __('Enable'),
			'description'           => __('If enabled scheduled reports can be emailed automatically to a list of recipients.<br> This feature requires a configured version of the \'Settings Plugin\'.'),
			'method'                => 'checkbox',
			'default'               => '',
		),
		'reportit_header6'          => array(
			'friendly_name'         => __('Auto Exporting'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_auto_export'      => array(
			'friendly_name'         => __('Enabled'),
			'description'           => __('If enabled scheduled reports can be exported automatically to a specified folder.<br> Therefore a full structured path architecture will be used:<br> Main Folder -> Template Folder (if defined) or Template ID -> Report ID -> Report'),
			'method'                => 'checkbox',
			'default'               => '',
		),
		'reportit_exp_folder'       => array(
			'friendly_name'         => __('Export Path'),
			'description'           => __('Optional: The main path to an export folder for saving the exports. If not defined subfolder \'exports\' will be used.'),
			'method'                => 'dirpath',
			'max_length'            => '255',
			'default'               => REPORTIT_EXP_FD,
		),
		'reportit_header7'          => array(
			'friendly_name'         => __('Graph Settings'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_graph'            => array(
			'friendly_name'         => __('Enabled'),
			'description'           => __('Enable/disable graph functionality'),
			'method'                => 'checkbox',
			'default'               => 'off',
		),
		'reportit_g_mheight'        => array(
			'friendly_name'         => __('Maximum Graph Height'),
			'description'           => __('The maximum height of ReportIT graphs in pixels.<br> Warning! GD functions are very memory intensive. Be sure to set \'memory_limit\' high enough.'),
			'method'                => 'textbox',
			'max_length'            => '4',
			'default'               => '320',
		),
		'reportit_g_mwidth'         => array(
			'friendly_name'         => __('Maximum Graph Width'),
			'description'           => __('The maximum width of ReportIT graphs in pixels.<br> Warning! GD functions are very memory intensive. Be sure to set \'memory_limit\' high enough.'),
			'method'                => 'textbox',
			'max_length'            => '4',
			'default'               => '480',
		),
		'reportit_g_quality'        => array(
			'friendly_name'         => __('Quality Level'),
			'description'           => __('Choose the level of quality.<br> Warning! A higher quality setting has a lower calculation speed and requires more memory.'),
			'method'                => 'drop_array',
			'array'                 => $rrdtool_quality,
			'default'               => '1',
		),
		'reportit_g_mono'           => array(
			'friendly_name'         => __('Monospace Fonts'),
			'description'           => __('It\'s recommend to use monospace fonts like Lucida, Courier, Vera or DejaVu instead of the other types.'),
			'method'                => 'checkbox',
			'default'               => 'on',
		),
		'reportit_g_tfont'          => array(
			'friendly_name'         => __('Title Font File'),
			'description'           => __('Define font file to use for graph titles'),
			'method'                => 'filepath',
			'max_length'            => '255',
			'default'               => $tfont,
		),
		'reportit_g_afont'          => array(
			'friendly_name'         => __('Axis Font File'),
			'description'           => __('Define font file to use for graph axis'),
			'method'                => 'filepath',
			'max_length'            => '255',
			'default'               => $font,
		),
	);

    if (isset($settings['reports'])) {
        $settings['reports'] = array_merge($settings_graphs, $temp);
    } else {
        $settings['reports'] = $temp;
        unset($temp);
    }

	//Extension of graph settings
	$tabs_graphs['reportit'] = __('ReportIT General Settings');
	$temp =  array(
		'reportit_view_filter'      => array(
			'friendly_name'         => __('Separate Report View Filter'),
			'description'           => __('Enable/disable the use of an individual filter per report.'),
			'method'                => 'checkbox',
			'default'               => 'on',
		),
		'reportit_max_rows'         => array(
			'friendly_name'         => __('Rows Per Page'),
			'description'           => __('The number of rows to display on a single page.'),
			'method'                => 'textbox',
			'max_length'            => '3',
			'default'               => '25',
		),
		'reportit_csv_header'       => array(
			'friendly_name'         => __('ReportIT Export Settings'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_csv_column_s'     => array(
			'friendly_name'         => __('CSV Column Separator'),
			'description'           => __('The column seperator to be used for CSV exports.'),
			'method'                => 'drop_array',
			'array'                 => $csv_column_separator,
			'default'               => '1',
		),
		'reportit_csv_decimal_s'    => array(
			'friendly_name'         => __('CSV Decimal Separator'),
			'description'           => __('The symbol indicating the end of the integer part and the beginning of the fractional part.'),
			'method'                => 'drop_array',
			'array'                 => $csv_decimal_separator,
			'default'               => '1',
		),
		'reportit_graph_header'     => array(
			'friendly_name'         => __('ReportIT Graph Settings'),
			'method'                => 'spacer',
			'collapsible'           => 'true'
		),
		'reportit_g_default'        => array(
			'friendly_name'         => __('Default Chart'),
			'description'           => __('Define your default chart that should be shown first'),
			'method'                => 'drop_array',
			'array'                 => $graphs,
			'default'               => '-10',
		),
		'reportit_g_height'         => array(
			'friendly_name'         => __('Graph Height'),
			'description'           => __('The height of ReportIT graphs in pixel.'),
			'method'                => 'textbox',
			'max_length'            => '4',
			'default'               => '320',
		),
		'reportit_g_width'          => array(
			'friendly_name'         => __('Graph Width'),
			'description'           => __('The width of ReportIT graphs in pixel.'),
			'method'                => 'textbox',
			'max_length'            => '4',
			'default'               => '480',
		),
		'reportit_g_showgrid'       => array(
			'friendly_name'         => __('Show Graph Grid'),
			'description'           => __('Enable/disable Graph Grid for ReportIT Graphs.'),
			'method'                => 'checkbox',
			'default'               => 'off',
		),
	);

	if (isset($settings_graphs['reports'])) {
		$settings_graphs['reportit'] = array_merge($settings_graphs['reportit'],$temp);
	} else {
		$settings_graphs['reportit'] = $temp;
	}

	unset($temp);
}

function reportit_show_tab() {
    global $config;

	if (api_user_realm_auth('cc_view.php')) {
		print '<a href="' . $config['url_path'] . 'plugins/reportit/cc_view.php"><img src="' . $config['url_path'] . 'plugins/reportit/images/tab_reportit_' . (get_current_page() == 'cc_view.php' ? 'down' : 'up'). '.png" alt="' . __('ReportIT') . '"></a>';
	}
}

function reportit_setup_table($upgrade = false) {
	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_reports (
		`id` int(11) NOT NULL auto_increment,
		`description` varchar(255) NOT NULL default '',
		`user_id` int(11) NOT NULL default '0',
		`template_id` int(11) NOT NULL default '0',
		`host_template_id` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
		`data_source_filter` varchar(255) NOT NULL DEFAULT '',
		`preset_timespan` varchar(255) NOT NULL default '',
		`last_run` datetime NOT NULL default '0000-00-00 00:00:00',
		`runtime` int(11) NOT NULL default '0',
		`public` tinyint(1) NOT NULL default '0',
		`start_date` date NOT NULL default '0000-00-00',
		`end_date` date NOT NULL default '0000-00-00',
		`ds_description` varchar(5000) NOT NULL default '',
		`rs_def` varchar(255) NOT NULL default '',
		`sp_def` varchar(255) NOT NULL default '',
		`sliding` tinyint(1) NOT NULL default '0',
		`present` tinyint(1) NOT NULL default '0',
		`scheduled` tinyint(1) NOT NULL default '0',
		`autorrdlist` tinyint(1) NOT NULL DEFAULT '0',
		`auto_email` tinyint(1) NOT NULL DEFAULT '0',
		`email_subject` varchar(255) NOT NULL default '',
		`email_body` varchar(1000) NOT NULL default '',
		`email_format` varchar(255) NOT NULL default '',
		`subhead` tinyint(1) NOT NULL default '0',
		`in_process` tinyint(1) NOT NULL default '0',
		`graph_permission` tinyint(1) NOT NULL DEFAULT '1',
		`frequency` varchar(255) NOT NULL default '',
		`autoarchive` mediumint(8) UNSIGNED NOT NULL DEFAULT 1,
		`autoexport` varchar(255) NOT NULL default '',
		`autoexport_max_records` smallint NOT NULL DEFAULT '0',
		`autoexport_no_formatting` tinyint(1) NOT NULL default '0',
		PRIMARY KEY (`id`))
		ENGINE=InnoDB;";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_templates (
		`id` int(11) NOT NULL auto_increment,
		`description` varchar(255) NOT NULL default '',
		`pre_filter` varchar(255) NOT NULL default '',
		`data_template_id` int(11) NOT NULL default '0',
		`locked` tinyint(1) NOT NULL default '0',
		`export_folder` varchar(255) NOT NULL default '',
		PRIMARY KEY (`id`))
		ENGINE=InnoDB;";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_measurands (
		`id` int(11) NOT NULL auto_increment,
		`template_id` int(11) NOT NULL default '0',
		`description` varchar(255) NOT NULL default '',
		`abbreviation` varchar(255) NOT NULL default '',
		`calc_formula` varchar(255) NOT NULL default '',
		`unit` varchar(255) NOT NULL default '',
		`visible` tinyint(1) NOT NULL default '1',
		`spanned` tinyint(1) NOT NULL default '0',
		`rounding` tinyint(1) NOT NULL default '0',
		`cf` int(11) NOT NULL default '1',
		`data_type` SMALLINT NOT NULL DEFAULT '1',
		`data_precision` SMALLINT NOT NULL DEFAULT '2',
		PRIMARY KEY  (`id`))
		ENGINE=InnoDB;";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_variables (
		`id` int(11) NOT NULL auto_increment,
		`template_id` int(11) NOT NULL default '0',
		`abbreviation` varchar(255) NOT NULL default '',
		`name` varchar(255) NOT NULL default '',
		`description` varchar(255) NOT NULL default '',
		`max_value` float NOT NULL default '0',
		`min_value` float NOT NULL default '0',
		`default_value` float NOT NULL default '0',
		`input_type` tinyint(1) NOT NULL default '0',
		`stepping` float NOT NULL default '0',
		PRIMARY KEY (`id`))
		ENGINE=InnoDB";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_rvars (
		`id` int(11) NOT NULL auto_increment,
		`template_id` int(11) NOT NULL default '0',
		`report_id` int(11) NOT NULL default '0',
		`variable_id` int(11) NOT NULL default '0',
		`value` float NOT NULL default '0',
		PRIMARY KEY (`id`))
		ENGINE=MyISAM;";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_presets (
		`id` int(11) NOT NULL DEFAULT 0,
		`description` varchar(255) NOT NULL default '',
		`start_day` varchar(255) NOT NULL default 'Monday',
		`end_day` varchar(255) NOT NULL default 'Sunday',
		`start_time` time NOT NULL default '00:00:00',
		`end_time` time NOT NULL default '24:00:00',
		`timezone` varchar(255) NOT NULL default 'GMT',
		PRIMARY KEY (`id`))
		ENGINE=InnoDB";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_recipients (
		`id` int(11) NOT NULL auto_increment,
		`report_id` int(11) NOT NULL DEFAULT '0',
		`email` varchar(255) NOT NULL default '',
		`name` varchar(255) NOT NULL default '',
		PRIMARY KEY (`id`))
		ENGINE=InnoDB";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_data_items (
		`id` int(11) NOT NULL default '0',
		`report_id` int(11) NOT NULL default '0',
		`description` varchar(255) NOT NULL default '',
		`start_day` varchar(255) NOT NULL default 'Monday',
		`end_day` varchar(255) NOT NULL default 'Sunday',
		`start_time` time NOT NULL default '00:00:00',
		`end_time` time NOT NULL default '24:00:00',
		`timezone` varchar(255) NOT NULL default 'GMT',
		PRIMARY KEY (`id`, `report_id`), INDEX (`report_id`))
		ENGINE = InnoDB";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_data_source_items (
		`id` int(11) NOT NULL default '0',
		`template_id` int(11) NOT NULL default '0',
		`data_source_name` varchar(255) NOT NULL default '',
		`data_source_alias` varchar(255) NOT NULL default '',
		PRIMARY KEY (`id`, `template_id`), INDEX (`template_id`))
		ENGINE = InnoDB";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_cache_reports (
		`cache_id` varchar(255) NOT NULL default '',
		`id` int(11) NOT NULL default '0',
		`description` varchar(255) NOT NULL default '',
		`user_id` int(11) NOT NULL default '0',
		`template_id` int(11) NOT NULL default '0',
		`host_template_id` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
		`data_source_filter` varchar(255) NOT NULL DEFAULT '',
		`preset_timespan` varchar(255) NOT NULL default '',
		`last_run` datetime NOT NULL default '0000-00-00 00:00:00',
		`runtime` float NOT NULL default '0',
		`public` tinyint(1) NOT NULL default '0',
		`start_date` date NOT NULL default '0000-00-00',
		`end_date` date NOT NULL default '0000-00-00',
		`ds_description` varchar(5000) NOT NULL default '',
		`rs_def` varchar(255) NOT NULL default '',
		`sp_def` varchar(255) NOT NULL default '',
		`sliding` tinyint(1) NOT NULL default '0',
		`present` tinyint(1) NOT NULL default '0',
		`scheduled` tinyint(1) NOT NULL default '0',
		`autorrdlist` tinyint(1) NOT NULL DEFAULT '0',
		`auto_email` tinyint(1) NOT NULL DEFAULT '0',
		`email_subject` varchar(255) NOT NULL default '',
		`email_body` varchar(1000) NOT NULL default '',
		`email_format` varchar(255) NOT NULL default '',
		`subhead` tinyint(1) NOT NULL default '0',
		`in_process` tinyint(1) NOT NULL default '0',
		`graph_permission` tinyint(1) NOT NULL DEFAULT '1',
		`frequency` varchar(255) NOT NULL default '',
		`autoarchive` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
		`template_name` varchar(255) NOT NULL default '',
		`data_template_alias` varchar(10000) NOT NULL default '',
		`owner` varchar(255) NOT NULL default '',
		`autoexport` varchar(255) NOT NULL default '',
		`autoexport_max_records` smallint NOT NULL DEFAULT '0',
		`autoexport_no_formatting` tinyint(1) NOT NULL default '0',
		PRIMARY KEY (`cache_id`))
		ENGINE=InnoDB";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_cache_measurands (
		`cache_id` varchar(255) NOT NULL default '',
		`id` int(11) NOT NULL default '0',
		`template_id` int(11) NOT NULL default '0',
		`description` varchar(255) NOT NULL default '',
		`abbreviation` varchar(255) NOT NULL default '',
		`calc_formula` varchar(255) NOT NULL default '',
		`unit` varchar(255) NOT NULL default '',
		`visible` tinyint(1) NOT NULL default '1',
		`spanned` tinyint(1) NOT NULL default '0',
		`rounding` tinyint(1) NOT NULL default '0',
		`cf` int(11) NOT NULL default '1',
		`data_type` SMALLINT NOT NULL DEFAULT '1',
		`data_precision` SMALLINT NOT NULL DEFAULT '2',
		INDEX (`cache_id`),
		UNIQUE(`cache_id`, `id`))
		ENGINE=InnoDB";

	$sql[] = "CREATE TABLE IF NOT EXISTS reportit_cache_variables (
		`cache_id` varchar(255) NOT NULL default '',
		`id` int(11) NOT NULL default '0',
		`name` varchar(255) NOT NULL default '',
		`description` varchar(255) NOT NULL default '',
		`value` float NOT NULL default '0',
		`max_value` float NOT NULL default '0',
		`min_value` float NOT NULL default '0',
		INDEX (`cache_id`),
		UNIQUE(`cache_id`, `id`))
		ENGINE=InnoDB";

    if (sizeof($sql)) {
		foreach($sql as $query) {
			db_execute($query);
        }
    }
}

function reportit_define_constants(){
    global $config;

    /* realm IDs which have been defined dynamically by PIA 2.x */
    $ids = db_fetch_assoc("SELECT id FROM plugin_realms WHERE plugin='reportit' ORDER BY id ASC");

    @define('REPORTIT_USER_ADMIN', 100 + $ids[0]['id']);
    @define('REPORTIT_USER_OWNER', 100 + $ids[1]['id']);
    @define('REPORTIT_USER_VIEWER', 100 + $ids[2]['id']);

    /* define ReportIT's base paths */
    @define('REPORTIT_BASE_PATH', $config['base_path'] . '/plugins/reportit');

    /* with regard to Cacti 0.8.8 it becomes necessarily to replace the old path settings */
    @define('CACTI_BASE_PATH', $config['base_path']);
    @define('CACTI_INCLUDE_PATH', CACTI_BASE_PATH . '/include/');

    /* path where PCLZIP will save temporary files */
    @define('REPORTIT_TMP_FD', REPORTIT_BASE_PATH . '/tmp/');
    /* path where archives will be saved per default */
    @define('REPORTIT_ARC_FD', REPORTIT_BASE_PATH . '/archive/');
    /* path where exports will be saved per default */
    @define('REPORTIT_EXP_FD', REPORTIT_BASE_PATH . '/exports/');
}

function reportit_poller_bottom() {
    $str = '';
    $ids = '';
    $cnt = 0;

    $lifecycle     = read_config_option('reportit_arc_lifecycle', TRUE);
    $logging_level = read_config_option('log_verbosity', TRUE);

    /* fetch all tables whose life cycle has been expired */
    $sql =  "SHOW TABLE STATUS WHERE `Name` LIKE 'reportit_tmp_%'
		AND (UNIX_TIMESTAMP(`Update_time`) + $lifecycle) <= UNIX_TIMESTAMP()";

    $tables = db_fetch_assoc($sql);

    if (count($tables)) {
        foreach($tables as $table) {
            /* take care that we really do NOT delete others tables */
            if (strpos($table['Name'], 'reportit_tmp_') !== FALSE) {
                $str .= $table['Name'] . ', ';
            $ids .= ",'" . substr($table['Name'], 13) . "'";
                $cnt++;
            }
        }
        if ($cnt == 0) exit;

        $ids = substr($ids, 1);
        $str = substr($str, 0, -2);
        if (db_execute("DROP TABLE IF EXISTS $str") == 1) {
            db_execute("DELETE FROM reportit_cache_reports WHERE `cache_id` IN ($ids)");
            db_execute("DELETE FROM reportit_cache_variables WHERE `cache_id` IN ($ids)");
            db_execute("DELETE FROM reportit_cache_measurands WHERE `cache_id` IN ($ids)");

            if ($cnt >= 5) {
                db_execute('OPTIMIZE TABLE `reportit_cache_reports`');
                db_execute('OPTIMIZE TABLE `reportit_cache_variables`');
                db_execute('OPTIMIZE TABLE `reportit_cache_measurands`');
            }

            if ($logging_level != 'POLLER_VERBOSITY_NONE' && $logging_level != 'POLLER_VERBOSITY_LOW') {
                cacti_log("REPORTIT STATS: Cache Life Cycle:$lifecycle"."s &nbsp;&nbsp;Number of drops:$cnt", false, 'PLUGIN');
            }
        } else {
            if ($logging_level != 'POLLER_VERBOSITY_LOW') {
                cacti_log('REPORTIT WARNING: Unable to clean up report cache', false, 'PLUGIN');
            }
        }
    }
}

function reportit_page_head(){
}
