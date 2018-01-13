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

global $boost_max_runtime, $consolidation_functions, $graph_timespans;

$graph_timespans = array( GT_CUSTOM => __('Custom')) + $graph_timespans;


$fields_tab_marker = array(
	'id' => array(
		'method' =>'hidden_zero',
		'value'  => '|arg1:id|'
	),
	'tab' => array(
		'method' =>'hidden_zero',
		'value'  => '|arg1:tab|'
	)
);

$fields_report_edit = array(
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
		'collapsible' => 'true'
	),
	'report_name' => array(
		'friendly_name' => __('Name', 'reportit'),
		'description' => __('The name given to this report', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'value' => '|arg1:name|',
	),
	'report_description' => array(
		'friendly_name' => __('Description', 'reportit'),
		'description' => __('A short, pithy description that explains the sense of this report.', 'reportit'),
		'method' => 'textarea',
		'textarea_rows' => '5',
		'textarea_cols' => '50',
		'default' => __('Your description here ...', 'reportit'),
		'value' => '|arg1:description|',
		'default' => ''
	),
	'report_template'	=> array(
		'friendly_name'	=> __('Report Template', 'reportit'),
		'description'	=> __('The report template your configuration depends on', 'reportit'),
		'method'		=> 'drop_sql',
		'sql'			=> 'SELECT id, name FROM plugin_reportit_templates ORDER BY name',
		'value'			=> '|arg1:template_id|',
		'none_value'	=> __('None', 'reportit'),
	),
);

$fields_re1_edit = array(
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
	'report_timespan' => array(
		'friendly_name' => __('Time Frame', 'reportit'),
		'description' => __('The time frame you want to analyse in relation to the point of time the calculation starts. Choose "custom" if you would like to limit your report to a fixed reporting period where reoccurrence will not take into account.', 'reportit'),
		'method' => 'drop_array',
		'value' => '|arg1:preset_timespan|',
		'array' => $graph_timespans,
	),
	'report_start_date' => array(
		'friendly_name' => __('From', 'reportit'),
		'description' => __('Defines the start of a fixed reporting period', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:start_date|',
	),
	'report_end_date' => array(
		'friendly_name' => __('To', 'reportit'),
		'description' => __('Defines the end of a fixed reporting period', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:end_date|',
	),
	'report_header_3' => array(
		'friendly_name' => __('Scheduling'),
		'method' => 'spacer',
	),
	'report_schedule_frequency' => array(
		'friendly_name' => __('Recurrence Pattern'),
		'description' => __('Select a frequency this report should be re-calculated with'),
		'method' => 'drop_array',
		'value' => '|arg1:frequency|',
		'array' => $report_schedule_frequency,
		'none_value' => __('disabled', 'reportit'),
	),
	'report_autorrdlist' => array(
		'friendly_name' => __('Auto Generated Data Items'),
		'description' => __('Enable/disable automatic creation of all data items based on given filters.This will be called before report execution.  Obsolete RRDs will be deleted and all RRDs matching the filter settings will be added.'),
		'method' => 'checkbox',
		'value' => '|arg1:autorrdlist|',
		'default' => '',
	),

);




$fields_template_edit = array(
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|'
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
		'description' => __('A short, pithy description that explains the sense of this template.', 'reportit'),
		'method' => 'textarea',
		'textarea_rows' => '5',
		'textarea_cols' => '50',
		'default' => __('Your description here ...', 'reportit'),
		'value' => '|arg1:description|',
		'default' => ''
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
		'description' => __('The status "locked" avoids any kind of modification to your report template.', 'reportit'),
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
);

$fields_template_export = array(
	'template_action' => array(
		'method' => 'hidden_zero',
		'value' => 'template_export'
	),
	'template_id' => array(
		'friendly_name' => __('Report Template', 'reportit'),
		'description' => __('Choose one of your Report Templates to export to XML.', 'reportit'),
		'method' => 'drop_sql',
		'sql' => 'SELECT id, description as name FROM plugin_reportit_templates WHERE locked = 0 ORDER BY description',
		'default' => 0,
		'none_value' => false,
		'value' => '',
	),
	'template_description' => array(
		'method' => 'textarea',
		'friendly_name' => __('[Optional] Description', 'reportit'),
		'description' => __('Describe the characteristics of your report template', 'reportit'),
		'value' => '',
		'default' => '',
		'textarea_rows' => '10',
		'textarea_cols' => '52',
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
		'description' => __('A version number if you like. Internal revision number will be added automatically.', 'reportit'),
		'value' => '',
		'max_length' => '250',
		'size' => 50
	),
	'template_contact' => array(
		'method' => 'textbox',
		'friendly_name' => __('[Optional] Contact', 'reportit'),
		'description' => __('Your email address or something else if want to be reachable for other admins using this template.', 'reportit'),
		'value' => '',
		'max_length' => '250',
		'size' => 50
	),
);

$fields_template_upload = array(
	'template_action' => array(
		'method' => 'hidden_zero',
		'value' => 'template_import_wizard'
	),
	'import_file' => array(
		'friendly_name' => __('Import Template from Local File'),
		'description' => __('Select the local XML file that contains your Report Template.', 'reportit'),
		'method' => 'file'
	),
);

$fields_template_import = array(
	'template_action' => array(
		'method' => 'hidden_zero',
		'value' => 'template_import'
	),
	'template_name' => array(
		'friendly_name' => __('Template Name', 'reportit'),
		'method' => 'custom',
		'max_length' => '255',
		'description' => '',
		'value' => '|arg1:description|'
	),
	'template_version' => array(
		'friendly_name' => __('Version', 'reportit'),
		'method' => 'custom',
		'max_length' => '255',
		'description' => '',
		'value' => '|arg1:version|'
	),
	'template_author' => array(
		'friendly_name' => __('Author', 'reportit'),
		'method' => 'custom',
		'max_length' => '255',
		'description' => '',
		'value' => '|arg1:author|'
	),
	'template_contact' => array(
		'friendly_name' => __('Contact', 'reportit'),
		'method' => 'custom',
		'max_length' => '255',
		'description' => '',
		'value' => '|arg1:contact|'
	),
	'template_description' => array(
		'friendly_name' => __('Description', 'reportit'),
		'method' => 'custom',
		'max_length' => '255',
		'description' => '',
		'value' => '|arg1:description|'
	),
#	'template_compatible' => array(
#		'friendly_name' => __('Compatible', 'reportit'),
#		'method' => 'custom',
#		'max_length' => '255',
#		'description' => '',
#		'value' => '|arg2:templates|'
#	),
);

$fields_group_edit = array(
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|'
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value' => 'groups'
	),
	'template_id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:template_id|'
	),
	'group_header' => array(
		'friendly_name' => __('General', 'reportit'),
		'method' => 'spacer'
	),
	'group_generic_id'	=> array(
		'friendly_name' => __('Generic ID', 'reportit'),
		'description' => __('The generic group id will be automatically generated based on the list of selected data source items as well as defined aliases and titles', 'reportit'),
		'method' => 'custom',
		'max_length' => '100',
		'value' => '|arg1:generic_group_id|'
	),
	'group_name' => array(
		'friendly_name' => __('Name'),
		'description' => __('A name like "Interface Traffic" for example which should be used as a headline within the final report.', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'value' => '|arg1:name|',
		'default' => ''
	),
	'group_description' => array(
		'friendly_name' => __('Description', 'reportit'),
		'description' => __('A short, pithy description that explains the sense of this group and or data templates assigned to it.', 'reportit'),
		'method' => 'textarea',
		'textarea_rows' => '5',
		'textarea_cols' => '50',
		'default' => __('Your description here ...', 'reportit'),
		'value' => '|arg1:description|',
		'default' => ''
	),
);

$fields_variable_edit = array(
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|'
	),
	'tab' => array(
		'method' => 'hidden_zero',
		'value' => 'variables'
	),
	'template_id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:template_id|'
	),
	'variable_header' => array(
		'friendly_name' => __('General', 'reportit'),
		'method' => 'spacer'
	),
	'variable_abbreviation'	=> array(
		'friendly_name' => __('Internal name', 'reportit'),
		'description' => __('A unique identifier which will be created by ReportIt itself. Use this ID within the definition of your calculation formulas to include that value the report user has defined individually for it.', 'reportit'),
		'method' => 'custom',
		'max_length' => '100',
		'value' => '|arg1:abbreviation|'
	),
	'variable_name' => array(
		'friendly_name' => __('Name'),
		'description' => __('A name like "Threshold" for example which should be used as a headline within the report config.', 'reportit'),
		'method' => 'textbox',
		'max_length' => '100',
		'value' => '|arg1:name|',
		'default' => ''
	),
	'variable_description' => array(
		'friendly_name' => __('Description', 'reportit'),
		'description' => __('A short, pithy description that explains the sense of this variable.', 'reportit'),
		'method' => 'textarea',
		'textarea_rows' => '5',
		'textarea_cols' => '50',
		'default' => __('Your description here ...', 'reportit'),
		'value' => '|arg1:description|',
		'default' => ''
	),
	'variable_maximum' => array(
		'friendly_name' => __('Maximum Value', 'reportit'),
		'description' => __('Defines the upper limit of this variable.', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:max_value|',
		'default' => ''
	),
	'variable_minimum' => array(
		'friendly_name' => __('Minimum Value', 'reportit'),
		'description' => __('Defines the lower limit of this variable.', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:min_value|',
		'default' => ''
	),
	'variable_default' => array(
		'friendly_name' => __('Default Value', 'reportit'),
		'description' => __('Sets the default value.', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:default_value|',
		'default' => ''
	),
	'variable_type' => array(
		'friendly_name' => __('Type', 'reportit'),
		'description' => __('The method the report owner should use to define this variable.', 'reportit'),
		'method' => 'drop_array',
		'array' => $variable_input_types,
		'value' => '|arg1:input_type|',
		'default' => 1
	),
	'variable_stepping' => array(
		'friendly_name' => __('Stepping', 'reportit'),
		'description' => __('Defines the distance between two values if method "DropDown" has been chosen. Please ensure that this value is not set too low, because it defines indirectly the number of options the dropdown field will have. For example the following parameters: MAX:100, MIN:0, STEP:0.01  will result in a select box of 10.001 options. This can cause dramatical performance issues due to a high CPU load at the clients side. Try to keep it under 1000.', 'reportit'),
		'method' => 'textbox',
		'max_length' => '10',
		'value' => '|arg1:stepping|',
		'default' => ''
	),
);

$fields_measurands_edit = array(
	'id'				=> array(
		'method'			=> 'hidden_zero',
		'value'				=> '|arg1:id|'
	),
	'tab' 				=> array(
		'method' 			=> 'hidden_zero',
		'value' 			=> 'measurands'
	),
	'template_id'		=> array(
		'method'			=> 'hidden_zero',
		'value'				=> '|arg1:template_id|'
	),
	'measurand_header'	=> array(
		'friendly_name'		=> __('General', 'reportit'),
		'method'			=> 'spacer',
		'collapsible' 		=> 'true'
	),
	'measurand_abbreviation'=> array(
		'friendly_name'		=> __('Abbreviation', 'reportit'),
		'description'		=> __('Define a unique abbreviation for this measurand with max. 8 letters/numbers.', 'reportit'),
		'method'			=> 'textbox',
		'max_length'		=> '10',
		'value'				=> '|arg1:abbreviation|',
		'default'			=> ''
	),
	'measurand_group'		=> array(
		'friendly_name' 	=> __('Data Template Group', 'reportit'),
		'description' 		=> __('Assign this measurand to a data template group.', 'reportit'),
		'method' 			=> 'drop_sql',
		'sql' 				=> '|arg2:sql|',
		'value' 			=> '|arg1:group_id|',
		'none_value' 		=> __('None', 'reportit'),
	),
	'measurand_description'	=> array(
		'friendly_name'		=> __('Description', 'reportit'),
		'description'		=> __('The explanation given to this measurand. This will be shown as legend within exports as well as a tooltip within the presentation of a report itself.', 'reportit'),
		'method' 			=> 'textarea',
		'textarea_rows' 	=> '5',
		'textarea_cols' 	=> '50',
		'default' 			=> __('Your description here ...', 'reportit'),
		'value' 			=> '|arg1:description|',
		'default' 			=> ''
	),
	'measurand_unit'	=> array(
		'friendly_name'		=> __('Unit', 'reportit'),
		'description'		=> __('The unit given to this measurand. e.g. \'Bits/s\'', 'reportit'),
		'method'			=> 'textbox',
		'max_length'		=> '100',
		'value'				=> '|arg1:unit|',
		'default'			=> ''
	),
	'measurand_cf'		=> array(
		'friendly_name'		=> __('Consolidation function', 'reportit'),
		'description'		=> __('The name of the consolidation function to define which CDPs should be read out.', 'reportit'),
		'method'			=> 'drop_array',
		'array'				=> $consolidation_functions,
		'value'				=> '|arg1:cf|',
		'default'			=> ''
	),
	'measurand_visible'	=> array(
		'friendly_name'		=> __('Visible', 'reportit'),
		'description'		=> __('Choose \'enable\' if this measurand should be become part of the final report output. Leave it unflagged and this measurands will only be used as an auxiliary calculation.', 'reportit'),
		'method'			=> 'checkbox',
		'value'				=> '|arg1:visible|',
	),
	'measurand_spanned'	=> array(
		'friendly_name'		=> __('Separate', 'reportit'),
		'description'		=> __('Choose \'enable\' if this measurand will only have one result in total instead of one for every Data Source Item. In this case its result<br>will be shown separately. Use this option in combination with "Visible" = "off" if you are looking for a measurand holding a single interim result only that should be reused within the calculation of other measurands without being visible for end users.', 'reportit'),
		'method'			=> 'checkbox',
		'value'				=> '|arg1:spanned|',
	),
	'measurand_header2'	=> array(
		'friendly_name'		=> __('Formatting', 'reportit'),
		'method'			=> 'spacer',
		'collapsible' 		=> 'true'
	),
	'measurand_type'=> array(
		'friendly_name'		=> __('Type', 'reportit'),
		'method'			=> 'drop_array',
		'array'				=> $measurand_type_specifier,
		'description'		=> __('Defines as what type the data should be treated as.', 'reportit'),
		'value'				=> '|arg1:data_type|',
		'default'			=> '1'
	),
	'measurand_precision'=> array(
		'friendly_name'		=> __('Precision', 'reportit'),
		'description'		=> __('Defines how many decimal digits should be displayed for floating-point numbers.', 'reportit'),
		'method'			=> 'drop_array',
		'array'				=> $measurand_precision,
		'value'				=> '|arg1:data_precision|',
		'default'			=> '2'
	),
	'measurand_rounding'=> array(
		'friendly_name'		=> __('Prefixes', 'reportit'),
		'description'		=> __('Choose the type of prefix beeing used to format the result. With the use of decimal prefixes \'1024\' will be formatted to \'1.024k\' while the binary prefixes option returns \'1ki\'. Select \'off\' to display the raw data, here \'1024\'.', 'reportit'),
		'method'			=> 'drop_array',
		'array'				=> $measurand_rounding,
		'value'				=> '|arg1:rounding|',
		'default'			=> '2'
	),
	'measurand_header3'	=> array(
		'friendly_name'		=> __('Formula', 'reportit'),
		'method'			=> 'spacer',
		'collapsible' 		=> 'true',
	),
	'measurand_formula'	=> array(
		'friendly_name' 	=> __('Calculation Formula', 'reportit'),
		'description' 		=> __('The mathematical definion of this measurand. Allowed are all combinations of operators and operands listed below following the rules of mathematics. Use round and square brackets to signify complex terms and the order of operations. To increase readability it is also allowed to use spaces.', 'reportit'),
		'method' 			=> 'textbox',
		'max_length' 		=> '255',
		'value' 			=> '|arg1:calc_formula|',
		'default'			=> ''
	),
	'measurand_ops_and_opds'=> array(
		'friendly_name'		=> __('Operators & Operands', 'reportit'),
		'description'		=> __('Click on one of the listed operators or operand to append them to your calucalion formula. The tooltip will show you additional information like description, return value, arguments and usage.', 'reportit'),
		'method' 			=> 'custom',
		'value'				=> '|arg2:ops_and_opds|'
	),
);

$fields_reportit_settings = array(
	'reportit_header1'          => array(
		'friendly_name'         => __('General'),
		'method'                => 'spacer',
		'collapsible'           => 'true'
	),
	'reportit_met'              => array(
		'friendly_name'         => __('Maximum Script Run Time'),
		'description'           => __('Optional: Maximum execution time of a single reporting process.'),
		'method'                => 'drop_array',
		'default'               => '1200',
		'array'                 => $boost_max_runtime
	),
	'reportit_max_record_count' => array(
		'friendly_name'         => __('Maximum Record Count'),
		'description'           => __('Max number of data items that can be assigned to a single report. Please note, that the Report runtime extremely varies with the granularity definied for the RRD. Use the limitation to avoid that ReportIt overloads the I/O file system.'),
		'method'                => 'drop_array',
		'default'               => '2000',
		'array'                 => $settings_max_record_per_report,
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
		'method'                => 'drop_array',
		'default'               => '300',
		'array'                 => $settings_max_cache_life_time
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
);
