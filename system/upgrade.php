<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group                                 |
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

function reportit_system_upgrade($old_version) {
	global $config, $database_default;

	$default_engine = db_fetch_row("SHOW GLOBAL VARIABLES LIKE 'default_storage_engine'");

	if (!sizeof($default_engine)) {
		$default_engine = db_fetch_row("SHOW GLOBAL VARIABLES LIKE 'storage_engine'");
	}

	if (cacti_sizeof($default_engine)) {
		$engine = $default_engine['Value'];
	} else {
		$engine = 'InnoDB';
	}

	if (cacti_version_compare($old_version, '1.0.0', '<')) {
		/* we do not support older version any longer - users having something below 0.7.4
		 * should upgrade ReportIt to 0.7.4, 0.7.5 or 0.7.5a first.
		 */
		db_execute('RENAME TABLE `reportit_cache_reports` TO `plugin_reportit_cache_reports`');
		db_execute("ALTER TABLE `plugin_reportit_cache_reports`
			CHANGE COLUMN `cache_id` `cache_id` VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
			CHANGE COLUMN `last_run` `last_run` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:01',
			CHANGE COLUMN `runtime` `runtime` INT NOT NULL DEFAULT '0',
			CHANGE COLUMN `public` `public` VARCHAR(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `start_date` `start_date` DATE NOT NULL DEFAULT '1970-01-01',
			CHANGE COLUMN `end_date` `end_date` DATE NOT NULL DEFAULT '1970-01-01',
			CHANGE COLUMN `sliding` `sliding` VARCHAR(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `present` `present` VARCHAR(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `scheduled` `scheduled` VARCHAR(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `autorrdlist` `autorrdlist` VARCHAR(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `auto_email` `auto_email` VARCHAR(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `subhead` `subhead` VARCHAR(255) NOT NULL DEFAULT '',
			CHANGE COLUMN `in_process` `state` tinyint(1) NOT NULL DEFAULT '0',
			MODIFY COLUMN `graph_permission` varchar(2) NOT NULL DEFAULT 'on'");

		db_execute("UPDATE plugin_reportit_cache_reports SET `public` = 'on' where `public` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `public` = '' where `public` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `sliding` = 'on' where `sliding` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `sliding` = '' where `sliding` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `present` = 'on' where `present` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `present` = '' where `present` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `scheduled` = 'on' where `scheduled` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `scheduled` = '' where `scheduled` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `autorrdlist` = 'on' where `autorrdlist` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `autorrdlist` = '' where `autorrdlist` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `auto_email` = 'on' where `auto_email` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `auto_email` = '' where `auto_email` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `graph_permission` = 'on' where `graph_permission` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `graph_permission` = '' where `graph_permission` = '0'");

		db_execute('RENAME TABLE `reportit_cache_measurands` TO `plugin_reportit_cache_measurands`');

		db_execute("ALTER TABLE `plugin_reportit_cache_measurands`
			CHANGE COLUMN `cache_id` `cache_id` VARCHAR(30) NOT NULL DEFAULT '',
			CHANGE COLUMN `visible` `visible` VARCHAR(2) NOT NULL DEFAULT 'on',
			CHANGE COLUMN `spanned` `spanned` VARCHAR(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `cf` `cf` TINYINT(1) NOT NULL DEFAULT '1'");

		db_execute("UPDATE plugin_reportit_cache_measurands SET `visible` = 'on' where `visible` = '1'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `visible` = '' where `visible` = '0'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `spanned` = 'on' where `spanned` = '1'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `spanned` = '' where `spanned` = '0'");

		db_execute('RENAME TABLE `reportit_cache_variables` TO `plugin_reportit_cache_variables`');
		db_execute("ALTER TABLE `plugin_reportit_cache_variables`
			CHANGE COLUMN `cache_id` `cache_id` VARCHAR(30) NOT NULL DEFAULT '',
			CHANGE COLUMN `value` `value` float NOT NULL DEFAULT '0' AFTER `min_value`");

		db_execute('RENAME TABLE `reportit_data_items` TO `plugin_reportit_data_items`');

		db_execute('RENAME TABLE `reportit_data_source_items` TO `plugin_reportit_data_source_items`');
		db_execute("ALTER TABLE `plugin_reportit_data_source_items`
			ADD COLUMN `group_id` mediumint(8) NOT NULL DEFAULT '0' AFTER `template_id`,
			ADD COLUMN `data_template_id` mediumint(8) UNSIGNED NOT NULL DEFAULT '0' AFTER `group_id`,
			ADD COLUMN `data_source_title` varchar(255) NOT NULL DEFAULT '' AFTER `data_source_alias`,
			ADD COLUMN `data_source_mp` varchar(255) NOT NULL DEFAULT '1' AFTER `data_source_title`");

		db_execute('RENAME TABLE `reportit_measurands` TO `plugin_reportit_measurands`');
		db_execute("ALTER TABLE `plugin_reportit_measurands`
			ADD COLUMN `group_id` int(11) NOT NULL DEFAULT '0' AFTER `template_id`,
			MODIFY COLUMN `visible` varchar(2) NOT NULL DEFAULT 'on',
			MODIFY COLUMN `spanned` varchar(2) NOT NULL DEFAULT '',
			MODIFY COLUMN `cf` tinyint(1) NOT NULL DEFAULT '1'");

		db_execute("UPDATE plugin_reportit_measurands SET `visible` = 'on' where `visible` = '1'");
		db_execute("UPDATE plugin_reportit_measurands SET `visible` = '' where `visible` = '0'");
		db_execute("UPDATE plugin_reportit_measurands SET `spanned` = 'on' where `spanned` = '1'");
		db_execute("UPDATE plugin_reportit_measurands SET `spanned` = '' where `spanned` = '0'");

		db_execute('RENAME TABLE `reportit_presets` TO `plugin_reportit_presets`');
		db_execute('RENAME TABLE `reportit_recipients` TO `plugin_reportit_recipients`');

		db_execute('RENAME TABLE `reportit_reports` TO `plugin_reportit_reports`');
		db_execute("ALTER TABLE `plugin_reportit_reports`
			MODIFY COLUMN `last_run` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
			MODIFY COLUMN `start_date` date NOT NULL DEFAULT '1970-01-01',
			MODIFY COLUMN `end_date` date NOT NULL DEFAULT '1970-01-01',
			MODIFY COLUMN `public` varchar(2) NOT NULL DEFAULT '',
			MODIFY COLUMN `sliding` varchar(2) NOT NULL DEFAULT '',
			MODIFY COLUMN `present` varchar(2) NOT NULL DEFAULT '',
			MODIFY COLUMN `scheduled` varchar(2) NOT NULL DEFAULT '',
			MODIFY COLUMN `autorrdlist` varchar(2) NOT NULL DEFAULT '',
			MODIFY COLUMN `auto_email` varchar(2) NOT NULL DEFAULT '',
			CHANGE COLUMN `in_process` `state` tinyint(1) NOT NULL DEFAULT '0',
			MODIFY COLUMN `graph_permission` varchar(2) NOT NULL DEFAULT 'on',
			CHANGE COLUMN `subhead` `subhead` varchar(255) NOT NULL DEFAULT '',
			MODIFY COLUMN `autoexport_no_formatting` varchar(2) NOT NULL DEFAULT 'on'");

		db_execute("UPDATE plugin_reportit_reports SET `public` = 'on' where `public` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `public` = '' where `public` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `sliding` = 'on' where `sliding` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `sliding` = '' where `sliding` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `present` = 'on' where `present` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `present` = '' where `present` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `scheduled` = 'on' where `scheduled` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `scheduled` = '' where `scheduled` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `autorrdlist` = 'on' where `autorrdlist` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `autorrdlist` = '' where `autorrdlist` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `auto_email` = 'on' where `auto_email` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `auto_email` = '' where `auto_email` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `graph_permission` = 'on' where `graph_permission` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `graph_permission` = '' where `graph_permission` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `autoexport_no_formatting` = 'on' where `autoexport_no_formatting` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `autoexport_no_formatting` = '' where `autoexport_no_formatting` = '0'");

		db_execute('RENAME TABLE `reportit_rvars` TO `plugin_reportit_rvars`');
		db_execute('RENAME TABLE `reportit_templates` TO `plugin_reportit_templates`');
		db_execute("ALTER TABLE `plugin_reportit_templates`
			CHANGE COLUMN `description` `name` varchar(255) NOT NULL DEFAULT '',
			ADD COLUMN `user_id` int(11) NOT NULL DEFAULT '0' AFTER `name`,
			ADD COLUMN `modified_by` int(11) NOT NULL DEFAULT '0' AFTER `user_id`,
			ADD COLUMN `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `modified_by`,
			ADD COLUMN `description` varchar(255) NOT NULL DEFAULT '' AFTER `last_modified`,
			MODIFY COLUMN `locked` varchar(2) NOT NULL DEFAULT 'on',
			ADD COLUMN `enabled` varchar(2) NOT NULL DEFAULT '' AFTER `locked`
		");
		db_execute("UPDATE plugin_reportit_templates SET `locked` = 'on' WHERE `locked` = '1'");
		db_execute("UPDATE plugin_reportit_templates SET `locked` = '' WHERE `locked` = '0'");
		db_execute("UPDATE plugin_reportit_templates SET `enabled` = 'on' WHERE `locked` != 'on'");

		db_execute("CREATE TABLE IF NOT EXISTS `plugin_reportit_data_template_groups` (
			`id` int(11) NOT NULL auto_increment,
			`template_id` mediumint(8) NOT NULL DEFAULT '0',
			`name` varchar(255) NOT NULL DEFAULT '',
			`description` varchar(255) NOT NULL DEFAULT '',
			`generic_group_id` varchar(32) NOT NULL DEFAULT '',
			`elements` varchar(510) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`),
			KEY `template_id` (`template_id`))
			ENGINE=$engine
			COMMENT='Table that Contains Data Template Group Definitions'");

		db_execute('RENAME TABLE `reportit_variables` TO `plugin_reportit_variables`');
	}

	if (cacti_version_compare($old_version, '1.0.2', '<')) {
		/* migrate existing result tables */
		$result_tables = array(); //db_fetch_assoc("SHOW TABLES FROM `$database_default` LIKE 'reportit_result%'");

		foreach($result_tables as $index => $arr) {
			foreach($arr as $tbl) {
				db_execute("RENAME TABLE `$tbl` TO `plugin_$tbl`");
			}
		}
	}

	if (cacti_version_compare($old_version, '1.1.0', '<')) {
		db_execute("UPDATE `plugin_reportit_templates` SET
			last_modified = IF(CAST(last_modified as CHAR) = '0000-00-00 00:00:00', '1970-01-01 00:00:00', last_modified)");

		db_execute("ALTER TABLE `plugin_reportit_templates`
			CHANGE COLUMN `last_modified` `last_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			ADD COLUMN `author` varchar(100) NOT NULL DEFAULT '' AFTER `last_modified`,
			ADD COLUMN `version` varchar(10) NOT NULL DEFAULT '' AFTER `author`");

		db_execute("UPDATE `plugin_reportit_templates` SET
			last_modified = IF(CAST(last_modified as CHAR) = '1970-01-01 00:00:00', NULL, last_modified)");

		db_execute("UPDATE `plugin_reportit_reports` SET
			last_run = IF(CAST(last_run as CHAR) = '0000-00-00 00:00:00', '1970-01-01 00:00:00', last_run),
			start_date = IF(CAST(start_date AS CHAR) = '0000-00-00', '1970-01-01', start_date),
			end_date = IF(CAST(end_date AS CHAR)= '0000-00-00', '1970-01-01', end_date)");

		db_execute("ALTER TABLE `plugin_reportit_reports`
			CHANGE COLUMN `last_run` `last_run` DATETIME NULL DEFAULT NULL,
			CHANGE COLUMN `start_date` `start_date` DATE NULL DEFAULT NULL,
			CHANGE COLUMN `end_date` `end_date` DATE NULL DEFAULT NULL");

		db_execute("UPDATE `plugin_reportit_reports` SET
			last_run = IF(CAST(last_run as CHAR) = '1970-01-01 00:00:00', NULL, last_run),
			start_date = IF(CAST(start_date AS CHAR) = '1970-01-01', NULL, start_date),
			end_date = IF(CAST(end_date AS CHAR)= '1970-01-01', NULL, end_date)");

		db_execute("ALTER TABLE `plugin_reportit_reports`
			ADD COLUMN `last_state` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `last_run`");

		// Fix partial renaming that occurred in 1.0.x
		db_execute("UPDATE `plugin_reportit_templates`
			SET `name` = `description`
			WHERE `name` = ''");

		db_execute("UPDATE `plugin_reportit_templates`
			SET `description` = `name`
			WHERE `description` = ''");

		// Fix ON/OFF statuses that were corrupted in 1.0.x
		db_execute("UPDATE plugin_reportit_reports SET `public` = 'on' where `public` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `public` = '' where `public` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `sliding` = 'on' where `sliding` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `sliding` = '' where `sliding` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `present` = 'on' where `present` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `present` = '' where `present` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `scheduled` = 'on' where `scheduled` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `scheduled` = '' where `scheduled` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `autorrdlist` = 'on' where `autorrdlist` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `autorrdlist` = '' where `autorrdlist` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `auto_email` = 'on' where `auto_email` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `auto_email` = '' where `auto_email` = '0'");
		db_execute("UPDATE plugin_reportit_reports SET `graph_permission` = 'on' where `graph_permission` = '1'");
		db_execute("UPDATE plugin_reportit_reports SET `graph_permission` = '' where `graph_permission` = '0'");

		db_execute("UPDATE plugin_reportit_cache_reports SET `public` = 'on' where `public` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `public` = '' where `public` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `sliding` = 'on' where `sliding` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `sliding` = '' where `sliding` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `present` = 'on' where `present` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `present` = '' where `present` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `scheduled` = 'on' where `scheduled` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `scheduled` = '' where `scheduled` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `autorrdlist` = 'on' where `autorrdlist` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `autorrdlist` = '' where `autorrdlist` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `auto_email` = 'on' where `auto_email` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `auto_email` = '' where `auto_email` = '0'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `graph_permission` = 'on' where `graph_permission` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `graph_permission` = '' where `graph_permission` = '0'");

		db_execute("UPDATE plugin_reportit_measurands SET `visible` = 'on' where `visible` = '1'");
		db_execute("UPDATE plugin_reportit_measurands SET `visible` = '' where `visible` = '0'");
		db_execute("UPDATE plugin_reportit_measurands SET `spanned` = 'on' where `spanned` = '1'");
		db_execute("UPDATE plugin_reportit_measurands SET `spanned` = '' where `spanned` = '0'");

		db_execute("UPDATE plugin_reportit_cache_measurands SET `visible` = 'on' where `visible` = '1'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `visible` = '' where `visible` = '0'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `spanned` = 'on' where `spanned` = '1'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `spanned` = '' where `spanned` = '0'");

		db_execute("UPDATE plugin_reportit_templates SET `locked` = 'on' WHERE `locked` = '1'");
		db_execute("UPDATE plugin_reportit_templates SET `locked` = '' WHERE `locked` = '0'");
		db_execute("UPDATE plugin_reportit_templates SET `enabled` = 'on' WHERE `locked` != 'on'");
	}

	if (cacti_version_compare($old_version, '1.1.1', '<')) {
		db_execute("ALTER TABLE `plugin_reportit_cache_reports`
			ADD COLUMN `autoexport_max_records` smallint(6) NOT NULL DEFAULT '0' AFTER `autoexport`,
			ADD COLUMN `autoexport_no_formatting` varchar(2) NOT NULL DEFAULT 'on' AFTER `autoexport_max_records`,
			CHANGE COLUMN `last_run` `last_run` datetime NULL DEFAULT NULL,
			CHANGE COLUMN `start_date` `start_date` date NULL DEFAULT NULL,
			CHANGE COLUMN `end_date` `end_date` date NULL DEFAULT NULL,
			DROP COLUMN `template_name`,
			DROP COLUMN `data_template_alias`,
			DROP COLUMN `owner`");

		db_execute("ALTER TABLE `plugin_reportit_templates`
			MODIFY COLUMN `author` varchar(255) NOT NULL DEFAULT '',
			DROP COLUMN `data_template_alias`");
	}

	if (cacti_version_compare($old_version, '2.0', '<')) {
		db_execute('ALTER TABLE `plugin_reportit_reports`
			ADD COLUMN notify_list INT UNSIGNED NOT NULL DEFAULT 0 AFTER email_format,
			ADD COLUMN site_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER template_id');

		db_execute('UPDATE plugin_realms SET file="reports.php" WHERE plugin="reportit" AND file LIKE "%reports.php%"');
		db_execute('UPDATE plugin_realms SET file="templates.php" WHERE plugin="reportit" AND file LIKE "%templates.php%"');
	}
}
