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

function reportit_system_upgrade($old_version) {
	global $config, $database_default;

	$default_engine = db_fetch_row("SHOW GLOBAL VARIABLES LIKE 'default_storage_engine'");
	if (!sizeof($default_engine)) {
		$default_engine = db_fetch_row("SHOW GLOBAL VARIABLES LIKE 'storage_engine'");
	}

	if (sizeof($default_engine)) {
		$engine = $default_engine['Value'];
	} else {
		$engine = 'InnoDB';
	}

	if (version_compare($old_version, '1.0.0', '<')) {

		/* we do not support older version any longer - users having something below 0.7.4
		 * should upgrade ReportIt to 0.7.4, 0.7.5 or 0.7.5a first.
		 */
		db_execute('RENAME TABLE `reportit_reports` TO `plugin_reportit_reports`');
		db_execute("ALTER TABLE `plugin_reportit_reports`
			MODIFY `public` varchar(2) NOT NULL DEFAULT '',
			MODIFY `sliding` varchar(2) NOT NULL DEFAULT '',
			MODIFY `present` varchar(2) NOT NULL DEFAULT '',
			MODIFY `scheduled` varchar(2) NOT NULL DEFAULT '',
			MODIFY `autorrdlist` varchar(2) NOT NULL DEFAULT '',
			MODIFY `auto_email` varchar(2) NOT NULL DEFAULT '',
			CHANGE `in_process` `state` tinyint(1) NOT NULL DEFAULT '0',
			MODIFY `graph_permission` varchar(2) NOT NULL DEFAULT 'on',
			MODIFY `autoexport_no_formatting` varchar(2) NOT NULL DEFAULT 'on'
		");
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

		db_execute('RENAME TABLE `reportit_cache_reports` TO `plugin_reportit_cache_reports`');
		db_execute("ALTER TABLE `plugin_reportit_cache_reports`
			MODIFY `public` varchar(2) NOT NULL DEFAULT '',
			MODIFY `sliding` varchar(2) NOT NULL DEFAULT '',
			MODIFY `present` varchar(2) NOT NULL DEFAULT '',
			MODIFY `scheduled` varchar(2) NOT NULL DEFAULT '',
			MODIFY `autorrdlist` varchar(2) NOT NULL DEFAULT '',
			MODIFY `auto_email` varchar(2) NOT NULL DEFAULT '',
			CHANGE `in_process` `state` tinyint(1) NOT NULL DEFAULT '0',
			MODIFY `graph_permission` varchar(2) NOT NULL DEFAULT 'on',
			MODIFY `autoexport_no_formatting` varchar(2) NOT NULL DEFAULT 'on'
		");
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
		db_execute("UPDATE plugin_reportit_cache_reports SET `autoexport_no_formatting` = 'on' where `autoexport_no_formatting` = '1'");
		db_execute("UPDATE plugin_reportit_cache_reports SET `autoexport_no_formatting` = '' where `autoexport_no_formatting` = '0'");

		db_execute('RENAME TABLE `reportit_measurands` TO `plugin_reportit_measurands`');
		db_execute("ALTER TABLE `plugin_reportit_measurands`
			MODIFY `visible` varchar(2) NOT NULL DEFAULT 'on',
			MODIFY `spanned` varchar(2) NOT NULL DEFAULT default '',
			MODIFY `cf` tinyint(1) NOT NULL DEFAULT default '1',
		");
		db_execute("UPDATE plugin_reportit_measurands SET `visible` = 'on' where `visible` = '1'");
		db_execute("UPDATE plugin_reportit_measurands SET `visible` = '' where `visible` = '0'");
		db_execute("UPDATE plugin_reportit_measurands SET `spanned` = 'on' where `spanned` = '1'");
		db_execute("UPDATE plugin_reportit_measurands SET `spanned` = '' where `spanned` = '0'");

		db_execute('RENAME TABLE `reportit_cache_measurands` TO `plugin_reportit_cache_measurands`');
		db_execute("ALTER TABLE `plugin_reportit_cache_measurands`
			MODIFY `visible` varchar(2) NOT NULL DEFAULT 'on',
			MODIFY `spanned` varchar(2) NOT NULL DEFAULT default '',
			MODIFY `cf` tinyint(1) NOT NULL DEFAULT default '1',
		");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `visible` = 'on' where `visible` = '1'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `visible` = '' where `visible` = '0'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `spanned` = 'on' where `spanned` = '1'");
		db_execute("UPDATE plugin_reportit_cache_measurands SET `spanned` = '' where `spanned` = '0'");


		db_execute('RENAME TABLE `reportit_variables` TO `plugin_reportit_variables`');
		db_execute('RENAME TABLE `reportit_cache_variables` TO `plugin_reportit_cache_variables`');
		db_execute('RENAME TABLE `reportit_rvars` TO `plugin_reportit_rvars`');
		db_execute('RENAME TABLE `reportit_data_items` TO `plugin_reportit_data_items`');
		db_execute('RENAME TABLE `reportit_data_source_items` TO `plugin_reportit_data_source_items`');
		db_execute('RENAME TABLE `reportit_presets` TO `plugin_reportit_presets`');
		db_execute('RENAME TABLE `reportit_recipients` TO `plugin_reportit_recipients`');
		db_execute('RENAME TABLE `reportit_templates` TO `plugin_reportit_templates`');
		db_execute("ALTER TABLE `plugin_reportit_templates`
			CHANGE `description` `name` varchar(255) NOT NULL DEFAULT '',
			ADD `user_id` int(11) NOT NULL DEFAULT '0' AFTER `name`,
			ADD `modified_by` int(11) NOT NULL DEFAULT '0' AFTER `user_id`,
			ADD `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `modified_by`,
			ADD `description` varchar(255) NOT NULL DEFAULT '' AFTER `last_modified`,
			MODIFY `locked` varchar(2) NOT NULL DEFAULT 'on',
			ADD `enabled` varchar(2) NOT NULL DEFAULT '' AFTER `locked`
		");
		db_execute("UPDATE plugin_reportit_templates SET `locked` = 'on' WHERE `locked` = '1'");
		db_execute("UPDATE plugin_reportit_templates SET `locked` = '' WHERE `locked` = '0'");
		db_execute("UPDATE plugin_reportit_templates SET `enabled` = 'on' WHERE `locked` != 'on'");

		db_execute("CREATE TABLE IF NOT EXISTS `plugin_reportit_data_template_groups` (
			`id` int(11) NOT NULL auto_increment,
			`template_id` mediumint(8) NOT NULL DEFAULT '0',
			`name` varchar(255) NOT NULL DEFAULT '',
			`descriptions` varchar(255) NOT NULL DEFAULT '',
			`generic_group_id` varchar(32) NOT NULL DEFAULT '',
			`elements` varchar(510) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`),
			KEY `template_id` (`template_id`)
			)
			ENGINE=$engine
			COMMENT='Table that Contains Data Template Group Definitions';
		");

#TODO change columns type frequency in reportit reports from var to numeric.
#TODO drop column "scheduled", "sliding", "present"
#TODO report description become report name

	}

	if (version_compare($old_version, '1.0.2', '<')) {
		/* migrate existing result tables */
		$result_tables = db_fetch_assoc("SHOW TABLES FROM `$database_default` LIKE 'reportit_result%'");

		foreach($result_tables as $index => $arr) {
			foreach($arr as $tbl) {
				db_execute("RENAME TABLE `$tbl` TO `plugin_$tbl`");
			}
		}
	}

	if (version_compare($old_version, '1.1.0', '<')) {
		db_execute("ALTER TABLE `plugin_reportit_templates`
			CHANGE `last_modified` `last_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			ADD `version` varchar(10) NOT NULL DEFAULT '' AFTER `description`,
			ADD `author` varchar(100) NOT NULL DEFAULT '' AFTER `last_modified`
		");

		// Fix partial renaming that occurred in 1.0.x
		db_execute("UPDATE `plugin_reportit_templates`
			SET `name` = `description`
			WHERE `name` = ''");

		db_execute("UPDATE `plugin_reportit_templates`
			SET `description` = `name`
			WHERE `description` = ''");
	}
}
