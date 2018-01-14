<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2018 The Cacti Group                                 |
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

    reportit_system_setup();
}

function plugin_reportit_uninstall() {
	db_execute('DROP TABLE IF EXISTS plugin_reportit_cache_measurands');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_cache_reports');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_cache_variables');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_data_items');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_data_source_items');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_data_template_groups');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_measurands');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_presets');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_recipients');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_reports');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_rvars');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_templates');
	db_execute('DROP TABLE IF EXISTS plugin_reportit_variables');

	return true;
}

function reportit_system_setup() {
	global $config;
	require_once($config['base_path'] . '/plugins/reportit/system/install.php');
	reportit_system_install();
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

	if($current == $old['version']) {
		return true;
	}else if(sizeof($old) && $current != $old['version']) {
		if ($old['status'] == 1 || $old['status'] == 4) {
			/* re-register hooks */
			plugin_reportit_install();
			/* perform a database upgrade */
			require_once($config['base_path'] . '/plugins/reportit/system/upgrade.php');
			reportit_system_upgrade($old["version"]);
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

function reportit_draw_navigation_text ($nav) {
    $nav['reports.php:'] = array(
		'title' => __('Reports', 'reportit'),
		'mapping' => 'index.php:',
		'url' => 'reports.php',
		'level' => '1');

    $nav['reports.php:save'] = array(
		'title' => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,?',
		'url' => 'templates.php',
		'level' => '2');

    $nav['reports.php:report_add'] = array(
		'title' => __('Add', 'reportit'),
		'mapping' => 'index.php:,?',
		'url' => 'templates.php',
		'level' => '2');

    $nav['reports.php:report_edit'] = array(
		'title' => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,?',
		'url' => 'templates.php',
		'level' => '2');

    $nav['reports.php:actions'] = array(
		'title' => __('Actions', 'reportit'),
		'mapping' => 'index.php:,?',
		'url' => 'templates.php',
		'level' => '2');

    $nav['rrdlist.php:'] = array(
		'title' => __('Data Items', 'reportit'),
		'mapping' => 'index.php:,reports.php:',
		'url' => 'templates.php',
		'level' => '2');

    $nav['rrdlist.php:save'] = array(
		'title' => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,reports.php:,rrdlist.php:',
		'url' => '',
		'level' => '3');

    $nav['rrdlist.php:rrdlist_edit'] = array(
		'title' => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,reports.php:,rrdlist.php:',
		'url' => '',
		'level' => '3');

    $nav['rrdlist.php:actions'] = array(
		'title' => __('Actions', 'reportit'),
		'mapping' => 'index.php:,reports.php:,rrdlist.php:',
		'url' => '',
		'level' => '3');

    $nav['items.php:'] = array(
		'title' => __('Add', 'reportit'),
		'mapping' => 'index.php:,reports.php:,rrdlist.php:',
		'url'  => 'templates.php',
		'level' => '3');

    $nav['items.php:save'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,reports.php:,rrdlist.php:',
		'url' => '',
		'level' => '4');

    $nav['templates.php:'] = array(
		'title' => __('Report Templates', 'reportit'),
		'mapping' => 'index.php:',
		'url' => 'templates.php',
		'level' => '1');

    $nav['templates.php:save'] = array(
		'title' => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:edit'] = array(
		'title' => __('(Edit)', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:template_new'] = array(
		'title' => __('Add', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:template_import_wizard'] = array(
		'title' => __('Import', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:template_upload_wizard'] = array(
		'title' => __('Import', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:template_import'] = array(
		'title' => __('Export', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:template_export'] = array(
		'title' => __('Export', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:template_export_wizard'] = array(
		'title' => __('Export', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['templates.php:actions'] = array(
		'title' => __('Actions', 'reportit'),
		'mapping' => 'index.php:,templates.php:',
		'url' => '',
		'level' => '2');

    $nav['run.php:calculation'] = array(
		'title' => __('Report Calculation', 'reportit'),
		'mapping' => 'index.php:,reports.php:',
		'url' => '',
		'level' => '2');

    $nav['view.php:'] = array(
		'title' => __('Public Reports', 'reportit'),
		'mapping' => 'index.php:',
		'url' => 'view.php',
		'level' => '1');

    $nav['view.php:show_report'] = array(
		'title' => __('Show Report', 'reportit'),
		'mapping' => 'index.php:,view.php:',
		'url' => '',
		'level' => '2');

    $nav['view.php:export'] = array(
		'title' => __('Export Report', 'reportit'),
		'mapping' => 'index.php:,view.php:',
		'url' => '',
		'level' => '2');

    $nav['view.php:show_graphs'] = array(
		'title' => __('Show Report', 'reportit'),
		'mapping' => 'index.php:,view.php:',
		'url' => '',
		'level' => '2');

    $nav['charts.php:'] = array(
		'title' => __('Public Report Charts', 'reportit'),
		'mapping' => 'index.php:',
		'url' => 'graph.php',
		'level' => '1');

    $nav['charts.php:bar'] = array(
		'title' => __('Bar Chart', 'reportit'),
		'mapping' => 'index.php:,graph.php:',
		'url' => '',
		'level' => '2');

    $nav['charts.php:pie'] = array(
		'title' => __('Pie Chart', 'reportit'),
		'mapping' => 'index.php:,graph.php:',
		'url' => '',
		'level' => '2');

    return $nav;
}

function reportit_config_arrays() {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $messages;

	/* register all realms of ReportIT */
	api_plugin_register_realm('reportit', 'view.php,charts.php', __('View Reports', 'reportit'), 1);
	api_plugin_register_realm('reportit', 'reports.php,rrdlist.php,items.php,run.php', __('Create Reports', 'reportit'), 1);
	api_plugin_register_realm('reportit', 'templates.php', __('Administrate Reports', 'reportit'), 1);

	/* show additional menu entries if plugin is enabled */
	if (api_plugin_is_enabled('reportit')) {
		$menu[__('Management')]['plugins/reportit/reports.php']  = __('Reports', 'reportit');
		$menu[__('Templates')]['plugins/reportit/templates.php'] = __('Reports', 'reportit');

		$temp = array(
			'reportit_templates__1' => array(
				'message' => __('No data source item selected', 'reportit'),
				'type' => 'error'),
			'reportit_templates__2' => array(
				'message' => __('Unselected data source items are still in use', 'reportit'),
				'type' => 'error'),
			'reportit_templates__3' => array(
				'message' => __('Unable to unlock this template without defined measurands', 'reportit'),
				'type' => 'error'),
			'reportit_templates__4' => array(
				'message' => __('Sorry, but this template has been locked by its owner', 'reportit'),
				'type' => 'error'),
			'reportit_templates__5' => array(
				'message' => __('Measurands have to be assigned to a data template group', 'reportit'),
				'type' => 'error'),
			'reportit_templates__6' => array(
				'message' => __('Duplicate abbreviation - this has to be a unique string', 'reportit'),
				'type' => 'error'),
		);
		$messages += $temp;
	}
}

function reportit_config_settings() {
	global $config, $tabs, $settings;

	reportit_define_constants();

	include_once( REPORTIT_INCLUDE_PATH . '/global_arrays.php' );
	include_once( REPORTIT_INCLUDE_PATH . '/global_forms.php' );

  	/* setup ReportIT's global configuration area */
	$tabs['reports'] = __('Reports', 'reportit');
	$settings['reports'] = $fields_reportit_settings;
}

function reportit_show_tab() {
    global $config;

	if (api_user_realm_auth('view.php')) {
		print '<a href="' . $config['url_path'] . 'plugins/reportit/view.php"><img src="' . $config['url_path'] . 'plugins/reportit/images/tab_reportit_' . (get_current_page() == 'view.php' ? 'down' : 'up'). '.png" alt="' . __esc('ReportIT') . '"></a>';
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
	@define('REPORTIT_LIB_PATH', REPORTIT_BASE_PATH . '/lib');
	@define('REPORTIT_INCLUDE_PATH', REPORTIT_BASE_PATH . '/include');

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
            db_execute("DELETE FROM plugin_reportit_cache_reports WHERE `cache_id` IN ($ids)");
            db_execute("DELETE FROM plugin_reportit_cache_variables WHERE `cache_id` IN ($ids)");
            db_execute("DELETE FROM plugin_reportit_cache_measurands WHERE `cache_id` IN ($ids)");

            if ($cnt >= 5) {
                db_execute('OPTIMIZE TABLE `plugin_reportit_cache_reports`');
                db_execute('OPTIMIZE TABLE `plugin_reportit_cache_variables`');
                db_execute('OPTIMIZE TABLE `plugin_reportit_cache_measurands`');
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
