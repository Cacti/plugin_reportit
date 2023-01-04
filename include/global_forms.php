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

$fields_template_edit = array(
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|'
	),
	'data_template_id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:data_template_id|'
	),
	'ds_enabled__0' => array(
		'method' => 'hidden_zero',
		'value' => 'on'
	),
	'template_header' => array(
		'friendly_name' => __('General', 'reportit'),
		'method' => 'spacer',
		'collapsible' => 'true'
	),
	'template_name' => array(
		'friendly_name' => __('Name'),
		'method' => 'textbox',
		'max_length' => '100',
		'description' => __('The unique name given to this Report Template.', 'reportit'),
		'value' => '|arg1:name|'
	),
	'template_description' => array(
		'friendly_name' => __('Description', 'reportit'),
		'method' => 'textarea',
		'max_length' => '255',
		'textarea_cols' => '80',
		'textarea_rows' => '5',
		'description' => __('A longer description of this Report Template.', 'reportit'),
		'value' => '|arg1:description|'
	),
	'template_version' => array(
		'friendly_name' => __('Version', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'description' => __('A version number for this template', 'reportit'),
		'default' => '1.0',
		'value' => '|arg1:version|'
	),
	'template_author' => array(
		'friendly_name' => __('Author', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'description' => __('The author of this template', 'reportit'),
		'default' => get_username($_SESSION['sess_user_id']),
		'value' => '|arg1:author|'
	),
	'template_enabled' => array(
		'method' => 'checkbox',
		'friendly_name' => __('Publish', 'reportit'),
		'description' => __('Should this report template be published for users to access? For testing purposes of new templates or modifications you should uncheck this box.', 'reportit'),
		'value' => '|arg1:enabled|',
	),
	'template_locked' => array(
		'friendly_name' => __('Locked', 'reportit'),
		'method' => 'checkbox',
		'description' => __('The status "locked" avoids any kind of modification to your report template as well as assigned measurands and variable definitions', 'reportit'),
		'value' => '|arg1:locked|',
	),
	'template_filter' => array(
		'friendly_name' => __('Additional Pre-filter', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'description' => __('Optional: The syntax to filter the available list of data items by their description. Use SQL wildcards like % and/or _. No regular Expressions!', 'reportit'),
		'value' => '|arg1:pre_filter|',
		'default' => ''
	),
	'template_export_folder' => array(
		'friendly_name' => __('Export Path', 'reportit'),
		'description' => __('Optional: The path to an folder for saving the exports.  If it does not exist ReportIt automatically tries to create it during the first scheduled calculation, else it will try to create a new subfolder within the main export folder using the template id.', 'reportit'),
		'method' => 'dirpath',
		'max_length' => '255',
		'value' => '|arg1:export_folder|',
		'default' => ''
	),
	'template_header2' => array(
		'friendly_name' => __('Data Template', 'reportit'),
		'method' => 'spacer',
		'collapsible' => 'true'
	),
	'template_data_template_label' => array(
		'friendly_name' => __('Data Template', 'reportit'),
		'method' => 'label',
		'max_length' => '100',
		'description' => __('The name of the data template this Report Template depends on.', 'reportit'),
		'value' => '|arg1:data_template_name|'
	),
	'template_data_template' => array(
		'friendly_name' => __('Data Template', 'reportit'),
		'method' => 'hidden',
		'max_length' => '100',
		'description' => __('The name of the data template this Report Template depends on.', 'reportit'),
		'value' => '|arg1:data_template_id|'
	)
);

$fields_template_export = array(
	'action' => array(
		'method' => 'hidden_zero',
		'value' => 'template_export'
	),
	'template_id' => array(
		'friendly_name' => __('Report Template', 'reportit'),
		'description' => __('Choose one of your Report Templates to export to XML.', 'reportit'),
		'method' => 'drop_sql',
		'sql' => 'SELECT id, description as name FROM plugin_reportit_templates WHERE locked = 0 ORDER BY description',
		'default' => 0,
		'none_value' => 'None',
		'value' => '',
	),
	'template_description' => array(
		'method' => 'textarea',
		'friendly_name' => __('[Optional] Description', 'reportit'),
		'description' => __('Describe the characteristics of your report template', 'reportit'),
		'value' => '',
		'default' => '',
		'textarea_rows' => '10',
		'textarea_cols' => '50',
		'class' => 'textAreaNotes'
	),
	'template_author' => array(
		'method' => 'textbox',
		'friendly_name' => __('[Optional] Author', 'reportit'),
		'description' => __('Add your name or nick here.', 'reportit'),
		'value' => '',
		'max_length' => '250',
		'size' => 50
	),
	'template_verion' => array(
		'method' => 'textbox',
		'friendly_name' => __('[Optional] Version', 'reportit'),
		'description' => __('Add your name or nick here.', 'reportit'),
		'value' => '',
		'max_length' => '250',
		'size' => 50
	),
	'template_contact' => array(
		'method' => 'textbox',
		'friendly_name' => __('[Optional] Contact', 'reportit'),
		'description' => __('Add your name or nick here.', 'reportit'),
		'value' => '',
		'max_length' => '250',
		'size' => 50
	),
);

