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

function upgrade_reportit_0_4_0_to_0_7_0(){
	global $config, $database_default;

	include_once($config['base_path'] . '/plugins/reportit/setup.php');
	include_once($config['base_path'] . '/plugins/reportit/lib_int/funct_shared.php');


	/* install default tables */
	reportit_setup_table('upgrade');

	/* Update table reportit_tables */
	$result_col = db_fetch_assoc('show columns from reportit_reports');

	foreach ($result_col as $index => $arr) {
		foreach ($arr as $col) {
			$columns[] = $col;
		}
	}

	if (!in_array('host_template_id', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `host_template_id` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT 0 AFTER template_id");
	}

	if (!in_array('data_source_filter', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `data_source_filter` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER host_template_id");
	}

	if (!in_array('autorrdlist', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `autorrdlist` TINYINT(1) NOT NULL DEFAULT 0 AFTER scheduled");
	}

	if (!in_array('auto_email', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `auto_email` TINYINT(1) NOT NULL DEFAULT 0 AFTER autorrdlist");
	}

	if (!in_array('email_subject', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `email_subject` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER auto_email");
	}

	if (!in_array('email_body', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `email_body` VARCHAR( 1000 ) NOT NULL DEFAULT '' AFTER email_subject");
	}

	if (!in_array('email_format', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `email_format` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER email_body");
	}

	if (!in_array('autoarchive', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `autoarchive` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT 1 AFTER `frequency`");
	}

	if (!in_array('graph_permission', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `graph_permission` TINYINT(1) NOT NULL DEFAULT '1' AFTER `in_process`");
	}

	if (!in_array('autoexport', $columns)) {
		db_execute("ALTER TABLE reportit_reports
			ADD `autoexport` VARCHAR(255) NOT NULL DEFAULT ''");
	}

	/* Update table reportit_tables */
	$result_col = db_fetch_assoc("show columns from reportit_cache_reports");

	foreach ($result_col as $index => $arr) {
		foreach ($arr as $col) {
			$columns[] = $col;
		}
	}

	if (!in_array('autoexport', $columns)) {
		db_execute("ALTER TABLE reportit_cache_reports
			ADD `autoexport` VARCHAR(255) NOT NULL DEFAULT ''");
	}

	/* Update table reportit_cache_variables */
	$result_col     = db_fetch_assoc("show columns from reportit_cache_variables");
	$columns = array();
	foreach ($result_col as $index => $arr) {
		foreach ($arr as $col) {
			$columns[] = $col;
		}
	}

	if (!in_array('description', $columns)) {
		db_execute("ALTER TABLE reportit_cache_variables
			ADD `description` varchar(255) NOT NULL DEFAULT '' AFTER name");
	}

	if (!in_array('max_value', $columns)) {
		db_execute("ALTER TABLE reportit_cache_variables
			ADD `max_value` float NOT NULL DEFAULT '0' AFTER value");
	}

	if (!in_array('min_value', $columns)) {
		db_execute("ALTER TABLE reportit_cache_variables
			ADD `min_value` float NOT NULL DEFAULT '0' AFTER max_value");
	}

	/* Update table reportit_cache_measurands */
	$result_col = db_fetch_assoc("SHOW COLUMNS FROM reportit_cache_measurands");
	$columns = array();
	foreach ($result_col as $index => $arr) {
		foreach ($arr as $col) {
			$columns[] = $col;
		}
	}

	if (!in_array('cf', $columns)) {
		db_execute("ALTER TABLE reportit_cache_measurands ADD `cf` int(11) NOT NULL default '1'");
	}

	/* Update table reportit_measurands */
	$result_col = db_fetch_assoc("SHOW COLUMNS FROM reportit_measurands");
	$columns = array();
	foreach ($result_col as $index => $arr) {
		foreach ($arr as $col) {
			$columns[] = $col;
		}
	}

	if (!in_array('cf', $columns)) {
		db_execute("ALTER TABLE reportit_measurands ADD `cf` int(11) NOT NULL default '1'");
	}

	/* Old definitions within ReportIt 0.4.x where no strict enough and some fields were to small */
	db_execute("ALTER TABLE reportit_reports
		MODIFY `description` varchar(255) NOT NULL default '',
		MODIFY `user_id` int(11) NOT NULL default '0',
		MODIFY `template_id` int(11) NOT NULL default '0',
		MODIFY `preset_timespan` varchar(255) NOT NULL default '',
		MODIFY `last_run` datetime NOT NULL default '0000-00-00 00:00:00',
		MODIFY `runtime` float NOT NULL default '0',
		MODIFY `public` tinyint(1) NOT NULL default '0',
		MODIFY `start_date` date NOT NULL default '0000-00-00',
		MODIFY `end_date` date NOT NULL default '0000-00-00',
		MODIFY `ds_description` varchar(5000) NOT NULL default '',
		MODIFY `rs_def` varchar(255) NOT NULL default '',
		MODIFY `sp_def` varchar(255) NOT NULL default '',
		MODIFY `sliding` tinyint(1) NOT NULL default '0',
		MODIFY `present` tinyint(1) NOT NULL default '0',
		MODIFY `scheduled` tinyint(1) NOT NULL default '0',
		MODIFY `subhead` tinyint(1) NOT NULL default '0',
		MODIFY `in_process` tinyint(1) NOT NULL default '0',
		MODIFY `frequency` varchar(255) NOT NULL default ''");

	db_execute("ALTER TABLE reportit_templates
		MODIFY `description` varchar(255) NOT NULL default '',
		MODIFY `pre_filter` varchar(255) NOT NULL default '',
		MODIFY `data_template_id` int(11) NOT NULL default '0',
		MODIFY `locked` tinyint(1) NOT NULL default '0'");

	db_execute("ALTER TABLE reportit_measurands
		MODIFY `template_id` int(11) NOT NULL default '0',
		MODIFY `description` varchar(255) NOT NULL default '',
		MODIFY `abbreviation` varchar(255) NOT NULL default '',
		MODIFY `calc_formula` varchar(255) NOT NULL default '',
		MODIFY `unit` varchar(255) NOT NULL default '',
		MODIFY `visible` tinyint(1) NOT NULL default '1',
		MODIFY `spanned` tinyint(1) NOT NULL default '0',
		MODIFY `rounding` tinyint(1) NOT NULL default '0'");

	db_execute("ALTER TABLE reportit_variables
		MODIFY `template_id` int(11) NOT NULL default '0',
		MODIFY `abbreviation` varchar(255) NOT NULL default '',
		MODIFY `name` varchar(255) NOT NULL default '',
		MODIFY `description` varchar(255) NOT NULL default '',
		MODIFY `max_value` float NOT NULL default '0',
		MODIFY `min_value` float NOT NULL default '0',
		MODIFY `default_value` float NOT NULL default '0',
		MODIFY `input_type` tinyint(1) NOT NULL default '0',
		MODIFY `stepping` float NOT NULL default '0'");

	db_execute("ALTER TABLE reportit_rvars
		MODIFY `template_id` int(11) NOT NULL default '0',
		MODIFY `report_id` int(11) NOT NULL default '0',
		MODIFY `variable_id` int(11) NOT NULL default '0',
		MODIFY `value` float NOT NULL default '0'");

	db_execute("ALTER TABLE reportit_cache_reports
		MODIFY `ds_description` varchar(5000) NOT NULL default '',
		MODIFY `data_template_alias` varchar(10000) NOT NULL default ''");


	/* consolidation of reportit_rrdlist tables */
	$sql    = "SHOW tables FROM `" . $database_default . "`";
	$result = db_fetch_assoc($sql);
	$tables = array();

	foreach ($result as $index => $arr) {
		foreach ($arr as $tbl) {
			if (strpos( $tbl, 'reportit_rrdlist_') !== false) $tables[] = $tbl;
		}
	}

	if (cacti_sizeof($tables)) {
		foreach ($tables as $table) {
			$add  = false;
			$copy = false;

			list($a, $b, $report_id) = explode('_', $table);

			db_execute("ALTER TABLE $table ADD `report_id` int(11) NOT NULL default '$report_id' AFTER id");
			db_execute("INSERT INTO reportit_data_items (SELECT * FROM $table)");
			db_execute("DROP TABLE `$table`");
		}
	}

	/* transformation of table reportit_templates */
	$template_configs = db_fetch_assoc("SELECT * FROM reportit_templates");

	if (is_array($template_configs)) {
		foreach ($template_configs as $template) {
			if (!isset($template['cf'])) {
				continue;
			}

			$cf                  = $template['cf'];
			$template_id         = $template['id'];
			$data_template_id    = $template['data_template_id'];
			$data_template_alias = $template['data_template_alias'];

			$ds_items            = array();

			/* Consolidation function will be bundled with the measurands */
			db_execute("UPDATE reportit_measurands SET `cf` = $cf WHERE template_id = $template_id");

			/* Data template aliases has to be saved separately */
			/* load information about defined data sources of that data template */
			$names = db_fetch_assoc_prepared('SELECT id, data_source_name
				FROM data_template_rrd
				WHERE local_data_id=0
				AND data_template_id = ?',
				array($data_template_id));

			/* load defined aliases */
			$ds_names = db_fetch_cell_prepared('SELECT data_template_alias
				FROM reportit_templates
				WHERE id = ?',
				array($template_id));

			$ds_names = unserialize($ds_names);

			if (is_array($names)) {
				/* overall will be handled as data source too */
				$ds_items[0]['id']                = 0;
				$ds_items[0]['template_id']       = $template_id;
				$ds_items[0]['data_source_name']  = 'overall';
				$ds_items[0]['data_source_alias'] = '';

				foreach ($names as $name) {
					$ds_id                                = $name['id'];
					$ds_items[$ds_id]['id']               = $ds_id;
					$ds_items[$ds_id]['template_id']      = $template_id;
					$ds_items[$ds_id]['data_source_name'] = $name['data_source_name'];

					if (is_array($ds_names) && array_key_exists($name['data_source_name'], $ds_names)) {
						$ds_items[$ds_id]['data_source_alias'] = $ds_names[$name['data_source_name']];
					} else {
						$ds_items[$ds_id]['data_source_alias'] = '';
					}
				}

				foreach ($ds_items as $ds_item) {
					sql_save($ds_item, 'reportit_data_source_items', array('id', 'template_id'), false);
				}
			}
		}
	}

	db_execute("ALTER TABLE reportit_templates DROP `cf`");
	db_execute("ALTER TABLE reportit_templates DROP `data_template_alias`");
	db_execute("ALTER TABLE reportit_templates ADD `export_folder` VARCHAR(255) NOT NULL DEFAULT '' AFTER locked");
	db_execute("INSERT INTO settings (`name` , `value`) VALUES ('reportit_pia', '1.x')");
}

function upgrade_pia_1x_to_pia_2x(){
	/* upgrade user permissions */
	/* realm IDs which have been defined dynamically by PIA 2.x */
	$ids = db_fetch_assoc("SELECT id FROM plugin_realms WHERE plugin='reportit' ORDER BY id ASC");

	define("REPORTIT_USER_ADMINISTRATE", 100 + $ids[0]['id']);
	define("REPORTIT_USER_CREATE", 100 + $ids[1]['id']);
	define("REPORTIT_USER_VIEW", 100 + $ids[2]['id']);

	/* remove the admin account added by PIA before */
	db_execute("DELETE FROM user_auth_realm WHERE realm_id = " . REPORTIT_USER_ADMINISTRATE);
	db_execute("DELETE FROM user_auth_realm WHERE realm_id = " . REPORTIT_USER_CREATE);
	db_execute("DELETE FROM user_auth_realm WHERE realm_id = " . REPORTIT_USER_VIEW);

	db_execute("UPDATE user_auth_realm SET realm_id =" . REPORTIT_USER_ADMINISTRATE . " WHERE realm_id = 800");
	db_execute("UPDATE user_auth_realm SET realm_id =" . REPORTIT_USER_CREATE . " WHERE realm_id = 801");
	db_execute("UPDATE user_auth_realm SET realm_id =" . REPORTIT_USER_VIEW . " WHERE realm_id = 802");

	db_execute("UPDATE  settings SET value = '2.x' WHERE name = 'reportit_pia'");
}
