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

function reportit_system_install() {
	/*
	* Table `plugin_reportit_reports`
	* - contains the general definition of reports
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'host_template_id', 'type' => 'mediumint(8)', 'unsigned' => true,	'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'data_source_filter', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'preset_timespan', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'last_run', 'type' => 'datetime', 'NULL' => true);
	$data['columns'][] = array('name' => 'last_state', 'type' => 'datetime', 'NULL' => false);
	$data['columns'][] = array('name' => 'runtime', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'public', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'start_date', 'type' => 'date', 'NULL' => true);
	$data['columns'][] = array('name' => 'end_date', 'type' => 'date', 'NULL' => true);
	$data['columns'][] = array('name' => 'ds_description', 'type' => 'varchar(5000)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'rs_def', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'sp_def', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'sliding', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'scheduled', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'autorrdlist', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'auto_email', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_subject', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_body', 'type' => 'varchar(1000)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_format', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'subhead', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'state', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'graph_permission', 'type' => 'varchar(2)', 'NULL' => false, 'default' => 'on');
	$data['columns'][] = array('name' => 'frequency', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'autoarchive', 'type' => 'mediumint(8)', 'unsigned' => true,	'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'autoexport', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'autoexport_max_records', 'type' => 'smallint', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'autoexport_no_formatting', 'type' => 'varchar(2)', 'NULL' => false, 'default' => 'on');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'general report definition parameters';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_reports', $data);

	db_execute('ALTER TABLE `plugin_reportit_reports`
		CHANGE `last_state` `last_state` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
	/*
	* Table `plugin_reportit_templates`
	* - list of all report templates
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'modified_by', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'author', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'version', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'pre_filter', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'data_template_id', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'locked', 'type' => 'varchar(2)', 'NULL' => false, 'default' => 'on');
	$data['columns'][] = array('name' => 'enabled', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'export_folder', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'general report template definitions';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_templates', $data);

	db_execute('ALTER TABLE `plugin_reportit_templates`
		ADD `last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');

	/*
	* Table `plugin_reportit_measurands`
	* - defined measurands as part of a report template
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'template_id', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'group_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'abbreviation', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'calc_formula', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'unit', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'visible', 'type' => 'varchar(2)', 'NULL' => false, 'default' => 'on');
	$data['columns'][] = array('name' => 'spanned', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'rounding', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cf', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'data_type', 'type' => 'smallint', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'data_precision', 'type' => 'smallint', 'NULL' => false, 'default' => '2');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'keeps definitions of all measurands';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_measurands', $data);

	/*
	* Table `plugin_reportit_variables`
	* - definition of variables assigned to a report template
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'abbreviation', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'max_value', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'min_value', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'default_value', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'input_type', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'stepping', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'keeps definitions of all variables';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_variables', $data);

	/*
	* Table `plugin_reportit_rvars`
	* - list of variables set for a report
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'report_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'variable_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'value', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'tracks defined values of variables per report';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_rvars', $data);

	/*
	* Table `plugin_reportit_presets`
	* - pre defined list of configuration parameters for new data source items
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'start_day', 'type' => 'varchar(255)', 'NULL' => false, 'default' => 'Monday');
	$data['columns'][] = array('name' => 'end_day', 'type' => 'varchar(255)', 'NULL' => false, 'default' => 'Sunday');
	$data['columns'][] = array('name' => 'start_time', 'type' => 'time', 'NULL' => false, 'default' => '00:00:00');
	$data['columns'][] = array('name' => 'end_time', 'type' => 'time', 'NULL' => false, 'default' => '24:00:00');
	$data['columns'][] = array('name' => 'timezone', 'type' => 'varchar(255)', 'NULL' => false, 'default' => 'GMT');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'olds a defined list of configuration parameters automatically being assigned to new data source items of a report';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_presets', $data);

	/*
	* Table `plugin_reportit_recipients`
	* - list of contacts the report should automatically be forwarded to by email
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'report_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'email', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'list of contacts the report should automatically be forwarded to by email';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_recipients', $data);

	/*
	* Table `plugin_reportit_data_items`
	* - pre defined list of configuration parameters for new data source items
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'report_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'start_day', 'type' => 'varchar(255)', 'NULL' => false, 'default' => 'Monday');
	$data['columns'][] = array('name' => 'end_day', 'type' => 'varchar(255)', 'NULL' => false, 'default' => 'Sunday');
	$data['columns'][] = array('name' => 'start_time', 'type' => 'time', 'NULL' => false, 'default' => '00:00:00');
	$data['columns'][] = array('name' => 'end_time', 'type' => 'time', 'NULL' => false, 'default' => '24:00:00');
	$data['columns'][] = array('name' => 'timezone', 'type' => 'varchar(255)', 'NULL' => false, 'default' => 'GMT');
	$data['unique_keys'][] = array('name' => 'primary_key', 'columns' => 'id`, `report_id');
	$data['keys'][] = array('name' => 'report_id', 'columns' => 'report_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'holds a defined list of configuration parameters automatically being assigned to new data source items of a report';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_data_items', $data);

	/*
	* Table `plugin_reportit_data_template_groups`
	* - holds the definition of data template groups to aggregate different data templates
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'template_id', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'generic_group_id', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'elements', 'type' => 'varchar(510)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'template_id', 'columns' => 'template_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'list of data template groups report template';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_data_template_groups', $data);


	/*
	* Table `plugin_reportit_data_source_items`
	* - keeps track of ds items per data source template that should be part of a report template
	*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint', 'unsigned' => true,	'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'group_id', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'data_template_id', 'type' => 'mediumint(8)', 'unsigned' => true,	'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'data_source_name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'data_source_alias', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'data_source_title', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'data_source_mp', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '1');
	$data['unique_keys'][] = array('name' => 'primary_key', 'columns' => 'id`, `template_id`, `data_template_id');
	$data['keys'][] = array('name' => 'data_template_id', 'columns' => 'data_template_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'list of selected ds items and settings per report template';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_data_source_items', $data);

	/*
	* Table `plugin_reportit_cache_reports`
	* - contains report definition parameters of archived reports read in temporarily
	*/
	$data = array();
	$data['columns'][] = array('name' => 'cache_id', 'type' => 'varchar(30)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'host_template_id', 'type' => 'mediumint(8)', 'unsigned' => true,	'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'data_source_filter', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'preset_timespan', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'last_run', 'type' => 'datetime', 'NULL' => true);
	$data['columns'][] = array('name' => 'runtime', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'public', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'start_date', 'type' => 'date', 'NULL' => true);
	$data['columns'][] = array('name' => 'end_date', 'type' => 'date', 'NULL' => true);
	$data['columns'][] = array('name' => 'ds_description', 'type' => 'varchar(5000)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'rs_def', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'sp_def', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'sliding', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'scheduled', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'autorrdlist', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'auto_email', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_subject', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_body', 'type' => 'varchar(1000)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_format', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'subhead', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'state', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'graph_permission', 'type' => 'varchar(2)', 'NULL' => false, 'default' => 'on');
	$data['columns'][] = array('name' => 'frequency', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'autoarchive', 'type' => 'mediumint(8)', 'unsigned' => true,	'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'autoexport', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'autoexport_max_records', 'type' => 'smallint', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'autoexport_no_formatting','type' => 'varchar(2)', 'NULL' => false, 'default' => 'on');
	$data['primary'] = 'cache_id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'report definition parameters of archived reports read in temporarily';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_cache_reports', $data);

	/*
	* Table `plugin_reportit_cache_measurands`
	* - defined measurands as part of an archived report
	*/
	$data = array();
	$data['columns'][] = array('name' => 'cache_id', 'type' => 'varchar(30)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'abbreviation', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'calc_formula', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'unit', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'visible', 'type' => 'varchar(2)', 'NULL' => false, 'default' => 'on');
	$data['columns'][] = array('name' => 'spanned', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'rounding', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cf', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'data_type', 'type' => 'smallint', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'data_precision', 'type' => 'smallint', 'NULL' => false, 'default' => '2');
	$data['keys'][] = array('name' => 'cache_id', 'columns' => 'cache_id');
	$data['unique_keys'][] = array('name' => 'unique_cache_key', 'columns' => 'cache_id`, `id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'holds measurands as part of an archived report';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_cache_measurands', $data);

	/*
	* Table `plugin_reportit_cache_variables`
	* - definition of variables as part of an archived report
	*/
	$data = array();
	$data['columns'][] = array('name' => 'cache_id', 'type' => 'varchar(30)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'max_value', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'min_value', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'value', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['keys'][] = array('name' => 'cache_id', 'columns' => 'cache_id');
	$data['unique_keys'][] = array('name' => 'unique_cache_key', 'columns' => 'cache_id`, `id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'holds variables as part of an archived report';

	api_plugin_db_table_create ('reportit', 'plugin_reportit_cache_variables', $data);
}
