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

chdir('../../');

include_once('./include/auth.php');
include_once( REPORTIT_LIB_PATH . '/funct_validate.php' );
include_once( REPORTIT_LIB_PATH . '/funct_online.php' );
include_once( REPORTIT_LIB_PATH . '/funct_shared.php' );
include_once( REPORTIT_LIB_PATH . '/funct_html.php' );
include		( REPORTIT_INCLUDE_PATH . '/global_arrays.php' );
include		( REPORTIT_INCLUDE_PATH . '/global_forms.php' );

/* set default action */
set_default_action();

/* set default tab */
set_request_var('tab', (!isset_request_var('tab') ? 'general' : $_REQUEST['tab']) );

/* set default tab action */
set_request_var('tab_action', (!isset_request_var('tab_action') ? '' : $_REQUEST['tab_action']) );

/* overwrite default action if necessary to allow usage of default forms */
if(get_request_var('template_action')) {
	set_request_var('action', get_request_var('template_action'));
}

switch (get_request_var('action')) {
	case 'actions':
		form_actions();
		break;
	case 'dt_add':
		get_filter_request_var('template_id');
		template_add_dt();
		header('Location: templates.php?header=false&action=edit&id=' . get_request_var('template_id') . '&tab=data_templates');
		break;
	case 'query_operations_and_operants':
		$measurand['id'] = get_filter_request_var('id');
		$measurand['group_id'] = get_filter_request_var('group_id');
		$measurand['template_id'] = get_filter_request_var('template_id');
		print html_operations_and_operands($measurand);
		break;
	case 'edit':
		top_header();
		template_edit();
		bottom_footer();
		break;
	case 'template_export_wizard':
		template_wizard('export');
		break;
	case 'template_import_wizard' :
		template_wizard('import');
		break;
	case 'template_upload_wizard' :
		template_wizard('upload');
		break;
	case 'template_export':
		template_export();
		break;
	case 'template_import':
		template_import();
		break;
	case 'save':
		form_save();
		break;
	default:
		top_header();
		standard();
		bottom_footer();
		break;
}


function template_wizard($action) {
	global $config;

	switch ($action) {

		case 'export':

			global $fields_template_export;
			top_header();
			form_start('templates.php');
			html_start_box( __('Export Report Template', 'reportit'), '100%', '', '2', 'center', '');
			draw_edit_form(
				array(
					'config' => array('no_form_tag' => true),
					'fields' => $fields_template_export
				)
			);
			html_end_box();
			form_save_button('templates.php', $force_type = 'export', '', false);
			bottom_footer();

			break;

		case 'upload':

			global $fields_template_upload;

			top_header();
			session_custom_error_display();
			print "<form action='templates.php' autocomplete='off' method='post' enctype='multipart/form-data'>";
			html_start_box( __('Import Report Template', 'reportit'), '100%', '', '2', 'center', '');
			draw_edit_form(
				array(
					'config' => array('no_form_tag' => true),
					'fields' => $fields_template_upload
				)
			);
			html_end_box();
			form_save_button('templates.php', $force_type = 'import');
			bottom_footer();

			break;

		case 'import':

			global $fields_template_import;

			/* clean up user session */
			if (isset($_SESSION['sess_reportit']['report_template'])) unset($_SESSION['sess_reportit']['report_template']);

			if (validate_uploaded_template() == true) {


				$save_html = ($_SESSION['sess_reportit']['report_template']['analyse']['compatible'] == 'yes')
					? "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Import', 'reportit') . "' title='" . __esc('Import Report Template', 'reportit') . "'>"
					: "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";

				$template_data = $_SESSION['sess_reportit']['report_template']['general'];
				$templates = $_SESSION['sess_reportit']['report_template']['analyse'];

				clean_xml_waste($template_data, '<i>unknown</i>');

				top_header();
				html_start_box(__('Summary', 'reportit'), '100%', '', '2', 'center', '');
				form_start('templates.php');
				draw_edit_form(
					array(
						'config' => array('no_form_tag' => true),
						'fields' => inject_form_variables($fields_template_import, $template_data, $templates)
					)
				);
				html_end_box();
				form_save_button('templates.php', $force_type = 'import');
				bottom_footer();

			} else {
				header('Location: templates.php?header=false&action=template_upload_wizard');
			}

			break;
	}
}

function template_export() {
	/* ================= input validation ================= */
	get_filter_request_var('template_id');
	/* ==================================================== */

	/* collect all additional information */
	$info = array();
	$info['description'] = get_request_var('template_description');
	$info['author']      = get_request_var('template_author');
	$info['version']     = get_request_var('template_version');
	$info['contact']     = get_request_var('template_contact');

	$output = export_report_template(get_request_var('template_id'), $info);
	if ($output == false) {
		die_html_custom_error('Internal error.',true);
	}

	header('Cache-Control: public');
	header('Content-Description: File Transfer');
	header('Cache-Control: max-age=1');
	header('Content-Type: application/xml');
	header('Content-Disposition: attachment; filename=\'template.xml\'');

	print '<?xml version="1.0" encoding="UTF-8"?>' . $output;
}

function template_import() {
	$values		= '';
	$columns	= '';
	$old		= array();
	$new		= array();

	/* ================= input validation ================= */
	get_filter_request_var('data_template');
	/* ==================================================== */

	if (!isset($_SESSION['sess_reportit']['report_template'])) {
		header('Location: templates.php?action=template_upload_wizard');
	}

	$template_data              = $_SESSION['sess_reportit']['report_template']['settings'];
	$template_variables         = $_SESSION['sess_reportit']['report_template']['variables'];
	$template_measurands        = $_SESSION['sess_reportit']['report_template']['measurands'];
	$template_data_source_items = $_SESSION['sess_reportit']['report_template']['data_source_items'];

	$template_data['id'] = 0;
	$template_data['data_template_id'] = get_request_var('data_template');

	clean_xml_waste($template_data);

	$template_id = sql_save($template_data, 'plugin_reportit_templates');

	if (is_array($template_variables)) {
		if (!isset($template_variables['variable'][0])) {
			$variable = $template_variables['variable'];
			$variable['id'] = 0;
			$variable['template_id'] = $template_id;
			$new_id = sql_save($variable, 'plugin_reportit_variables');
			$old[] = $variable['abbreviation'];
			$abbr = 'c' . $new_id . 'v';
			$new[] = $abbr;
			db_execute("UPDATE plugin_reportit_variables SET abbreviation = '$abbr' WHERE id = $new_id");
		} else {
			$template_variables = $template_variables['variable'];
			foreach($template_variables as $variable) {
				$variable['id'] = 0;
				$variable['template_id']= $template_id;
				$new_id = sql_save($variable, 'plugin_reportit_variables');
				$old[] = $variable['abbreviation'];
				$abbr = 'c' . $new_id . 'v';
				$new[] = $abbr;
				db_execute("UPDATE plugin_reportit_variables SET abbreviation = '$abbr' WHERE id = $new_id");
			 }
		}
	}

	if (is_array($template_measurands)) {
		if (!isset($template_measurands['measurand'][0])) {
			$measurand                 = $template_measurands['measurand'];
			$measurand['id']           = 0;
			$measurand['template_id']  = $template_id;
			$measurand['calc_formula'] = str_replace($old,$new, $measurand['calc_formula']);

			sql_save($measurand, 'plugin_reportit_measurands');
		} else {
			$template_measurands = $template_measurands['measurand'];

			foreach($template_measurands as $measurand) {
				$measurand['id']           = 0;
				$measurand['template_id']  = $template_id;
				$measurand['calc_formula'] = str_replace($old,$new, $measurand['calc_formula']);

				sql_save($measurand, 'plugin_reportit_measurands');
			}
		}
	}

	if (is_array($template_data_source_items)) {
		if (!isset($template_data_source_items['data_source_item'][0])) {
			$ds_item = $template_data_source_items['data_source_item'];

			clean_xml_waste($ds_item);

			$ds_item['id'] = db_fetch_cell_prepared('SELECT id
				FROM `data_template_rrd`
				WHERE local_data_id = 0
				AND data_template_id = ?
				AND data_source_name = ?',
				array(get_request_var('data_template'), $ds_item['data_source_name']));

			$ds_item['template_id'] = $template_id;

			sql_save($ds_item, 'plugin_reportit_data_source_items', array('id', 'template_id'), false);
		} else {
			$template_ds_items = $template_data_source_items['data_source_item'];

			foreach($template_ds_items as $ds_item) {
				clean_xml_waste($ds_item);

				$ds_item['id'] = db_fetch_cell_prepared('SELECT id
						FROM `data_template_rrd`
						WHERE local_data_id = 0
						AND data_template_id = ?
						AND data_source_name = ?',
						array(get_request_var('data_template'), $ds_item['data_source_name']));

				$ds_item['template_id'] = $template_id;

				sql_save($ds_item, 'plugin_reportit_data_source_items', array('id', 'template_id'), false);
			}
		}
	}

	/* destroy the template data saved in current session */
	unset($_SESSION['sess_reportit']['report_template']);

	header('Location: templates.php');
}

function template_filter() {
	global $item_rows;

	html_start_box( __('Report Templates', 'reportit'), '100%', '', '3', 'center', 'templates.php?action=edit');
	?>
	<tr class='even'>
		<td>
			<form id='form_templates' action='templates.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'reportit');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Templates', 'reportit');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default', 'reportit');?></option>
							<?php
							if (sizeof($item_rows)) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td>
						<input id='refresh' type='button' value='<?php print __esc_x('Button: use filter settings', 'Go', 'reportit');?>'>
					</td>
					<td>
						<input id='clear' type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear', 'reportit');?>'>
					</td>
					<td>
						<input id='import' type='button' value='<?php print __esc_x('Button: import button', 'Import', 'reportit');?>'>
					</td>
					<td>
						<input id='export' type='button' value='<?php print __esc_x('Button: export button', 'Export', 'reportit');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_filter_request_var('page');?>'>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL = 'templates.php?filter='+escape($('#filter').val())+'&rows='+$('#rows').val()+'&header=false';
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = 'templates.php?clear=1&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#refresh').click(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});

				$('#import').click(function() {
					strURL = 'templates.php?header=false&action=template_upload_wizard';
					loadPageNoHeader(strURL);
				});

				$('#export').click(function() {
					strURL = 'templates.php?header=false&action=template_export_wizard';
					loadPageNoHeader(strURL);
				});

				$('#form_templates').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php

	html_end_box();
}

function standard() {
	global  $config, $report_template_actions, $consolidation_functions;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'description',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_templates');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	if (get_request_var('filter') != '') {
		$sql_where = "WHERE (description LIKE '%" . get_request_var('filter') . "%')";
	} else {
		$sql_where = '';
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$total_rows = db_fetch_cell('SELECT COUNT(plugin_reportit_templates.id) FROM plugin_reportit_templates ' . $sql_where);

	$template_list = db_fetch_assoc("SELECT a.*, d.data_template_groups, e.reports
		FROM plugin_reportit_templates AS a
		LEFT JOIN (SELECT template_id, COUNT(*) AS data_template_groups FROM plugin_reportit_data_template_groups GROUP BY template_id) as d
		ON a.id = d.template_id
		LEFT JOIN (SELECT template_id, COUNT(*) AS reports FROM plugin_reportit_reports GROUP BY template_id) as e
		ON a.id = e.template_id
		$sql_where
		$sql_order
		$sql_limit");

	$display_text = array(
		'name' 		  => array('display' => __('Name', 'reportit'),             'align' => 'left', 'sort' => 'ASC', 'tip' => __('The name of this Report Template.', 'reportit')),
		'enabled'     => array('display' => __('Published', 'reporit'),         'align' => 'left', 'tip' => __('Unpublished Templates can not be used for reporting.', 'reportit')),
		'locked'      => array('display' => __('Locked', 'reportit'),           'align' => 'left', 'tip' => __('A template must be locked in order to be edited', 'reportit')),
		'user_id'      => array('display' => __('Owner', 'reportit'),           'align' => 'left', 'tip' => __('A template must be locked in order to be edited', 'reportit')),
		'last_modified' => array('display' => __('Last Edited', 'reportit'),      'align' => 'right', 'tip' => __('The date this template was last edited.', 'reportit')),
		'modified_by' => array('display' => __('Edited By', 'reportit'),      	'align' => 'right', 'tip' => __('The last user to have modified this template.', 'reportit')),
		'data_template_groups' => array('display' => __('Data Template Groups', 'reportit'),   'align' => 'right', 'tip' => __('The total number of data template groups associated with this report template.', 'reportit')),
		'reports'     => array('display' => __('Reports', 'reportit'),          'align' => 'right', 'tip' => __('The total number of reports using this report template.', 'reportit'))
	);

	$nav = html_nav_bar('templates.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Templates', 'reportit'), 'page', 'main');

	template_filter();

	print $nav;

	form_start('templates.php');
	html_start_box('', '100%', '', '2', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (sizeof($template_list)) {
		foreach($template_list as $template) {
			form_alternate_row('line' . $template['id'], true);
			form_selectable_cell('<a class="linkEditMain"' . (($template['description']) ? ' title="' . htmlentities($template['description'],ENT_QUOTES, "UTF-8", true) . '"' : '') .  ' href="' . htmlentities('templates.php?action=edit&id=' . $template['id'], ENT_QUOTES, "UTF-8", true) . '">' . $template['name']  . '</a>', $template['id']);
			form_selectable_cell( ($template['enabled']) ? __('yes', 'reportit') : __('no', 'reportit'), $template['id']);
			form_selectable_cell( ($template['locked']) ? '<i class="fa fa-lock" aria-hidden="true"></i>' : '<i class="fa fa-unlock" ria-hidden="true"></i>', $template['id']);
			form_selectable_cell(get_username($template['user_id']), $template['id']);
			form_selectable_cell(substr($template['last_modified'],0,16), $template['id'], '', 'text-align:right');
			form_selectable_cell(get_username($template['modified_by']), $template['id'], '', 'text-align:right');
			form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('templates.php?action=edit&id=' . $template['id'] . '&tab=groups') . '">' . ($template['data_template_groups'] ? $template['data_template_groups'] : 0)  . '</a>', $template['id'], '', 'text-align:right');
			form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('templates.php?action=edit&id=' . $template['id'] . '&tab=reports') . '">' . ($template['reports'] ? $template['reports'] : 0)  . '</a>', $template['id'], '', 'text-align:right');
			form_checkbox_cell($template['description'], $template['id']);
			form_end_row();
		}
	} else {
		print "<tr><td colspan='7'><em>" . __('No templates', 'reportit') . "</em></td></tr>";
	}

	html_end_box(true);

	if (sizeof($template_list)) {
		print $nav;
	}

	draw_actions_dropdown($report_template_actions['templates']);
	form_end();
}


function template_add_dt() {
	/* ================= input validation ================= */
	$template_id = get_filter_request_var('template_id');
	$data_template_id = get_filter_request_var('data_template_id');
	/* ==================================================== */

	$data_source_items = db_fetch_assoc_prepared("SELECT id, data_source_name
		FROM data_template_rrd WHERE local_data_id = 0 AND data_template_id = ?",
		array($data_template_id));

	if (sizeof($data_source_items)) {

		foreach ($data_source_items as $data_source_item) {
			/* save the data source items */
			$ds_item['id']                		= $data_source_item['id'];
			$ds_item['template_id']  	  		= $template_id;
			$ds_item['group_id']				= 0;
			$ds_item['data_template_id']  		= $data_template_id;
			$ds_item['data_source_name']  		= $data_source_item['data_source_name'];
			$ds_item['data_source_alias'] 		= '';
			$ds_item['data_source_title'] 		= '';
			$ds_item['data_source_mp']    		= 1;

			sql_save($ds_item, 'plugin_reportit_data_source_items', array('id', 'template_id'), false);
			$grouping[] = $data_source_item['data_source_name'];
			$grouping[] = $data_source_item['data_source_name'];
		}

		$ds_item['id'] 						= 0;
		$ds_item['data_source_name'] 		= '';
		$ds_item['data_source_alias']		= '';
		$ds_item['data_source_title']		= '';
		$ds_item['data_source_mp']    		= 1;

		sql_save($ds_item, 'plugin_reportit_data_source_items', array('id', 'template_id'), false);

		$grouping[] = 'overall';
		$grouping[] = 'overall';

		sort($grouping);
		$generic_group_id = md5(implode(',',$grouping));

		$group_elements = '';
		foreach($grouping as $key => $element) {
			if($key % 2 == 0) {
				$group_elements .= $element . ':' . $grouping[$key+1] . ';';
			}
		}

		$group_id = db_fetch_cell_prepared('SELECT id FROM plugin_reportit_data_template_groups
			WHERE template_id = ? AND generic_group_id = ?', array($template_id, $generic_group_id));

		if(!is_numeric($group_id)) {
			$ds_group['id']					= 0;
			$ds_group['template_id']		= $template_id;
			$ds_group['name']				= __('Generic Group', 'reportit');
			$ds_group['description']		= __('This is a generic group created by ReportIt. Please replace this text by a meaningful description.', 'reportit');
			$ds_group['generic_group_id']	= $generic_group_id;
			$ds_group['elements']			= $group_elements;

			$group_id = sql_save($ds_group, 'plugin_reportit_data_template_groups', array('id'), false);
		}

		db_execute_prepared('UPDATE plugin_reportit_data_source_items SET group_id = ? WHERE template_id = ? AND data_template_id = ?', array($group_id, $template_id, $data_template_id));

	}
}

function form_save(){
	switch(get_request_var('tab')){
		case 'data_templates':
		case 'groups':
		case 'measurands':
		case 'variables':
			$function = 'form_save__' . get_request_var('tab');
			$function();
			break;
		default:
			form_save__templates();
			break;
	}
}

function form_save__data_templates() {

	$ds_items = array();
	$used_data_sources = '';
	$unused_data_sources = FALSE;

	/* ================= input validation ================= */
	$data_template_id = get_filter_request_var('id');
	$template_id = get_filter_request_var('template_id');
	/* ==================================================== */

	$sql = "SELECT id, data_source_name
			FROM data_template_rrd
			WHERE local_data_id = 0
			AND data_template_id = ?";

	$defined_data_sources = array_rekey(db_fetch_assoc_prepared($sql, array($data_template_id)), 'id', 'data_source_name');
	$defined_data_sources[0] = 'overall';

	foreach($_POST as $key => $value){
		if (strpos($key, 'ds_enabled__') !== false) {

			$ds_id                                 = substr($key, 12);
			$used_data_sources                    .= "$ds_id,";
			$ds_name                               = $defined_data_sources[$ds_id];
			$ds_alias                              = 'ds_alias__' . $ds_id;
			$ds_title                              = 'ds_title__' . $ds_id;

			/* ================= input validation ================= */
			input_validate_input_number(get_nfilter_request_var('ds_mp__' . $ds_id));
			/* ==================================================== */

			$ds_items[$ds_id]['id']                = $ds_id;
			$ds_items[$ds_id]['template_id']       = $template_id;
			$ds_items[$ds_id]['group_id']    	   = 0;
			$ds_items[$ds_id]['data_template_id']  = $data_template_id;
			$ds_items[$ds_id]['data_source_name']  = $ds_name;
			$ds_items[$ds_id]['data_source_alias'] = trim(get_nfilter_request_var($ds_alias));
			$ds_items[$ds_id]['data_source_title'] = trim(get_nfilter_request_var($ds_title));
			$ds_items[$ds_id]['data_source_mp']    = get_nfilter_request_var('ds_mp__' . $ds_id);

			$grouping[] = (!$ds_items[$ds_id]['data_source_alias']) ? $ds_items[$ds_id]['data_source_name'] : $ds_items[$ds_id]['data_source_alias'];
			$grouping[] = (!$ds_items[$ds_id]['data_source_title'])
							? (!$ds_items[$ds_id]['data_source_alias'])	? $ds_items[$ds_id]['data_source_name']	: $ds_items[$ds_id]['data_source_alias']
							: $ds_items[$ds_id]['data_source_title'];
		}
	}

	/* updates for rubric overall as follows */
	$ds_items[0]['id']                = 0;
	$ds_items[0]['template_id']       = $template_id;
	$ds_items[0]['group_id']    	  = 0;
	$ds_items[0]['data_template_id']  = $data_template_id;
	$ds_items[0]['data_source_name']  = '';
	$ds_items[0]['data_source_alias'] = '';
	$ds_items[0]['data_source_title'] = trim(get_nfilter_request_var('ds_title__0'));
	$ds_items[0]['data_source_mp']    = 1;

	$grouping[] = 'overall';
	$grouping[] = (!$ds_items[0]['data_source_title']) ? 'overall' : $ds_items[0]['data_source_title'];

	sort($grouping);
	$new_generic_group_id = md5(implode(',',$grouping));

	$group_elements = '';
	foreach($grouping as $key => $element) {
		if($key % 2 == 0) {
			$group_elements .= $element . ':' . $grouping[$key+1] . ';';
		}
	}

	if (!$used_data_sources) {
		raise_message('reportit_templates__1');
	} else {

		$used_data_sources = substr($used_data_sources,0,-1);

		// get the list of unused data sources
		$sql = "SELECT id
			FROM data_template_rrd
			WHERE local_data_id = 0
			AND data_template_id = $data_template_id
			AND id NOT IN (". $used_data_sources . ")";
		$unused_data_sources = db_custom_fetch_flat_string($sql);
	}

	// check if there are data sources unselected although they are used in one of the defined measurands.
	if ($unused_data_sources !== FALSE) {
		// get the list of unused data sources
		$sql = "SELECT data_source_name
				FROM data_template_rrd
				WHERE local_data_id = 0
				AND data_template_id = $data_template_id
				AND id NOT IN (". $used_data_sources . ")";
		$pattern = db_custom_fetch_flat_string($sql, '|');

		$sql = "SELECT `abbreviation`
				FROM plugin_reportit_measurands
				WHERE `template_id` = $template_id
				AND `calc_formula` REGEXP '($pattern)'";
		$measurands = db_custom_fetch_flat_string($sql, ', ');

		if ($measurands !== FALSE) {
			raise_message('reportit_templates__2');
		}
	}

	if (!is_error_message()) {
		//remove all data source items which are no longer in use
		if ($unused_data_sources) {
			db_execute_prepared("DELETE FROM plugin_reportit_data_source_items
				WHERE template_id = ?
				AND id IN ($unused_data_sources)",
				array($template_id));
		}

		$old_group_id = db_fetch_cell_prepared('SELECT group_id FROM plugin_reportit_data_source_items
			WHERE id = 0 AND template_id = ? AND data_template_id = ?', array($template_id, $data_template_id));

		$old_generic_group_id = db_fetch_cell_prepared('SELECT generic_group_id FROM plugin_reportit_data_template_groups
			WHERE id = ?', array($old_group_id));

		// save the data source items
		foreach($ds_items as $ds_item) {
			sql_save($ds_item, 'plugin_reportit_data_source_items', array('id', 'template_id', 'data_template_id'), false);
		}

		$remaining_grp_entries  = db_fetch_cell_prepared('SELECT count(*) FROM plugin_reportit_data_source_items
			WHERE template_id = ? AND group_id = ?', array($template_id, $old_group_id));

		/* check if the new generic group already exists */
		$new_group_id_existing = db_fetch_cell_prepared('SELECT id FROM plugin_reportit_data_template_groups
			WHERE template_id = ? AND generic_group_id = ?', array($template_id, $new_generic_group_id));

		if($old_generic_group_id != $new_generic_group_id) {

			if($remaining_grp_entries) {

				if(!$new_group_id_existing) {
					// create a new group record and update existing group items. Don't touch the old group record.
					$ds_group['id']					= 0;
					$ds_group['template_id']		= $template_id;
					$ds_group['name']				= 'Generic Group';
					$ds_group['description']		= '';
					$ds_group['generic_group_id']	= $new_generic_group_id;
					$ds_group['elements']			= $group_elements;
					$new_group_id_existing = sql_save($ds_group, 'plugin_reportit_data_template_groups', array('id'), false);
				}
				// assign updated entries to that new group
				db_execute_prepared('UPDATE plugin_reportit_data_source_items SET group_id = ? WHERE group_id = 0 AND template_id = ? AND data_template_id = ?', array($new_group_id_existing, $template_id, $data_template_id));

			}else {

				if($new_group_id_existing) {
					// assign updated entries to that existing, but new group
					db_execute_prepared('UPDATE plugin_reportit_data_source_items SET group_id = ? WHERE group_id = 0 AND template_id = ? AND data_template_id = ?', array($new_group_id_existing, $template_id, $data_template_id));
					// unbind measurands
					db_execute_prepared('UPDATE plugin_reportit_measurands SET group_id = 0 WHERE group_id = ? AND template_id = ?', array($old_group_id, $template_id));
					// destroy old group entry
					db_execute_prepared('DELETE FROM plugin_reportit_data_template_groups WHERE id = ?', array($old_group_id));

				}else {
					// update the existing, old group entry
					db_execute_prepared('UPDATE plugin_reportit_data_template_groups SET generic_group_id = ?, elements = ? WHERE id = ?', array($new_generic_group_id, $group_elements, $old_group_id));
					// re-assign the old group_id
					db_execute_prepared('UPDATE plugin_reportit_data_source_items SET group_id = ? WHERE template_id = ? AND data_template_id = ?', array($old_group_id, $template_id, $data_template_id));
				}
			}
		}else {
			// re-assign the old group_id to all d
			db_execute_prepared('UPDATE plugin_reportit_data_source_items SET group_id = ? WHERE template_id = ? AND data_template_id = ?', array($old_group_id, $template_id, $data_template_id));
		}

		raise_message(1);
	}
	header('Location: templates.php?header=false&action=edit&id=' . $template_id . '&tab=data_templates&tab_action=edit&data_template_id=' . $data_template_id);
}

function form_save__groups() {

	/* ================= input validation ================= */
	$group_id = get_filter_request_var('id');
	$template_id = get_filter_request_var('template_id');
	/* ==================================================== */

	$group_data = array();
	$group_data['id']           = $group_id;
	$group_data['name']         = form_input_validate(get_nfilter_request_var('group_name'), 'group_name', '', false, 3);
	$group_data['description']  = form_input_validate(get_nfilter_request_var('group_description'), 'group_description', '', false, 3);

	if (!is_error_message()) {
		/* update group data */
		$group_data['id'] = sql_save($group_data, 'plugin_reportit_data_template_groups');

		/* return to list view if it was an existing report template */
		if ($group_data['id'] != 0) {
			raise_message(1);
		}else {
			raise_message(2);
		}
		header('Location: templates.php?header=false&action=edit&id=' . $template_id . '&tab=groups');
	}else {
		raise_message(2);
		header('Location: templates.php?header=false&action=edit&tab=groups&tab_action=edit&id=' . $template_id . '&group_id=' . $group_id);
	}

}

function form_save__templates() {

	$ds_items = array();
	$used_data_sources = '';
	$unused_data_sources = FALSE;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	form_input_validate(get_nfilter_request_var('template_name'), 'template_description', '', false, 3);
	form_input_validate(get_request_var('template_description'), 'template_description', '', false, 3);
	form_input_validate(get_request_var('template_filter'), 'template_filter', '', true, 3);
	/* ==================================================== */

	$template_data = array();
	$template_data['id']               = get_request_var('id');
	$template_data['name']      	   = get_request_var('template_name');
	$template_data['description']      = get_request_var('template_description');
	$template_data['pre_filter']       = get_request_var('template_filter');
	$template_data['enabled']          = isset_request_var('template_enabled') ? 'on' : '';
	$template_data['locked'] 		   = isset_request_var('template_locked') ? 'on' : '';
	$template_data['export_folder']    = isset_request_var('template_export_folder') ? get_request_var('template_export_folder') : '';
	$template_data['last_modified'] = date('Y-m-d H:i:s', time());
	$template_data['modified_by']   = $_SESSION['sess_user_id'];
	if (empty($template_data['id'])) {
		$template_data['user_id'] = $_SESSION['sess_user_id'];
	}else {
		$template_old = db_fetch_row_prepared('SELECT * FROM plugin_reportit_templates WHERE id = ?', array($template_data['id']));
		if( $template_old['locked'] == 'on' && $template_data['locked'] == 'on' ) {
			raise_message('reportit_templates__4');
		}
	}

	/* check if we can unlock this template. */
	if ($template_data['enabled'] == 'on') {
		if (stat_autolock_template($template_data['id'])) {
			raise_message('reportit_templates__3');
		}
	}

	if (!is_error_message()) {
		/* save template data */
		$template_data['id'] = sql_save($template_data, 'plugin_reportit_templates');

		/* return to list view if it was an existing report template */
		if ($template_data['id'] != 0) {
			raise_message(1);
		}else {
			raise_message(2);
		}
	}
	header('Location: templates.php?header=false&action=edit&id=' . $template_data['id']);
}

function form_save__variables() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('template_id');

	form_input_validate(get_nfilter_request_var('variable_name'), 'variable_name', '^[a-zA-Z0-9[:space:]]+$', false, 3);
	form_input_validate(get_nfilter_request_var('variable_description'), 'variable_description', '^[a-zA-Z0-9\n\r]+$', false, 3);
	form_input_validate(get_nfilter_request_var('variable_maximum'), 'variable_maximum', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
	form_input_validate(get_nfilter_request_var('variable_minimum'), 'variable_minimum', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
	form_input_validate(get_nfilter_request_var('variable_default'), 'variable_default', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
	form_input_validate(get_filter_request_var('variable_type'), 'variable_type', '^[1-2]$', false, 3);

	if (get_request_var('variable_type') == 1) {
		form_input_validate(get_request_var('variable_stepping'), 'variable_stepping', '^[0-9]+[.]?[0-9]*$', false, 3);
	}
	/* ==================================================== */


	//Check defined variable
	if (!(get_request_var('variable_maximum') > get_request_var('variable_minimum'))) {
		session_custom_error_message('variable_maximum', __('Maximum has to be greater than minimum.', 'reportit'));
	}

	if (!(get_request_var('variable_minimum') <= get_request_var('variable_default') && get_request_var('variable_default') <= get_request_var('variable_maximum'))) {
		session_custom_error_message('variable_default', __('Default value is out of defined range.', 'reportit'));
	}

	if (get_request_var('variable_type') == 1) {
		if ( get_request_var('variable_stepping') <= 0 ) {
			session_custom_error_message('variable_stepping', __('Step has to be positive','reportit'));
		}elseif (!(get_request_var('variable_stepping') <= (get_request_var('variable_maximum') - get_request_var('variable_minimum') ) ) ) {
			session_custom_error_message('variable_stepping', __('Step does not fit to defined range', 'reportit'));
		}elseif ( (float) ( get_request_var('variable_maximum') - (( (int) (get_request_var('variable_maximum')/get_request_var('variable_stepping'))) * get_request_var('variable_stepping') )) ) {
			session_custom_error_message('variable_stepping', __('Maximum value is not an integer multiple of step.', 'reportit') );
		}elseif ( (float) ( get_request_var('variable_minimum') - (( (int) (get_request_var('variable_minimum')/get_request_var('variable_stepping'))) * get_request_var('variable_stepping') )) ) {
			session_custom_error_message('variable_stepping', __('Minimum value is not an integer multiple of step.', 'reportit') );
		}elseif ( (float) ( get_request_var('variable_default') - (( (int) (get_request_var('variable_default')/get_request_var('variable_stepping'))) * get_request_var('variable_stepping') )) ) {
			session_custom_error_message('variable_stepping', __('Default value is not an integer multiple of step.', 'reportit') );
		}
	}

	$variable_data = array();
	$variable_data['id']            = get_request_var('id');
	$variable_data['name']          = get_request_var('variable_name');
	$variable_data['template_id']   = get_request_var('template_id');
	$variable_data['description']   = get_request_var('variable_description');
	$variable_data['max_value']     = get_request_var('variable_maximum');
	$variable_data['min_value']     = get_request_var('variable_minimum');
	$variable_data['default_value'] = get_request_var('variable_default');
	$variable_data['input_type']	= get_request_var('variable_type');

	if (isset_request_var('variable_stepping')) {
		$variable_data['stepping']  = get_request_var('variable_stepping');
	}

	if (is_error_message()) {
		header('Location: templates.php?header=false&action=edit&tab=variables&tab_action=edit&id=' . get_request_var('template_id') . '&variable_id=' . get_request_var('id'));
	} else {
		//Save data
		$var_id = sql_save($variable_data, 'plugin_reportit_variables');

		if (get_request_var('id') == 0) {
			db_execute("UPDATE plugin_reportit_variables
				SET abbreviation = 'c". $var_id . "v'
				WHERE id = $var_id");

			//If its a new one we've to create the entries for all the reports
			//using this template.
			create_rvars_entries($var_id, $variable_data['template_id'], $variable_data['default_value']);
		}

		//Return to list view if it was an existing report

		header('Location: templates.php?header=false&action=edit&tab=variables&id=' . get_request_var('template_id'));
		raise_message(1);
	}
}

function form_save__measurands() {
	global $calc_var_names, $measurand_rounding, $measurand_precision, $measurand_type_specifier;

	/* ================= input validation ================= */
	input_validate_input_number(get_filter_request_var('id'));
	input_validate_input_number(get_filter_request_var('template_id'));
	input_validate_input_key(get_filter_request_var('measurand_type'), $measurand_type_specifier);
	input_validate_input_key(get_filter_request_var('measurand_precision'), $measurand_precision, true);
	input_validate_input_key(get_filter_request_var('measurand_rounding'), array(0,1,2), true);

	form_input_validate(get_nfilter_request_var('measurand_description'), 'measurand_description', '', false, 3);
	form_input_validate(get_nfilter_request_var('measurand_abbreviation'), 'measurand_abbreviation', '^[a-zA-Z0-9]+$', false, 3);
	form_input_validate(get_filter_request_var('measurand_group'), 'measurand_group', '^[1-9]\d*$', false, 'reportit_templates__5');

	form_input_validate(get_nfilter_request_var('measurand_unit'), 'measurand_unit', '^[\/\\\$a-zA-Z0-9%²³-]+$', false, 3);
	form_input_validate(get_nfilter_request_var('measurand_formula'), 'measurand_formula', '', false, 3);
	form_input_validate(get_filter_request_var('measurand_cf'), 'measurand_cf', '[1-4]', false, 3);
	/* ==================================================== */

	//Check if the abbreviation is already in use.
	$count = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM plugin_reportit_measurands
		WHERE abbreviation = ?
		AND id != ?
		AND template_id = ?
		AND group_id = ?',
		array(strtoupper(trim(get_nfilter_request_var('measurand_abbreviation'))), get_request_var('id'), get_request_var('template_id'), get_request_var('measurand_group')));

	if ($count != 0) {
		raise_message('reportit_templates__6');
	}

	//Check calculation formula
	if (strlen(get_nfilter_request_var('measurand_formula'))) {
		$interim_results      = get_interim_results(get_request_var('id'), get_request_var('template_id'));
		$calc_var_names       = array_keys(get_possible_variables(get_request_var('template_id')));
		$data_query_variables = get_possible_data_query_variables( get_filter_request_var('measurand_group') );
		$error                = validate_calc_formula( get_nfilter_request_var('measurand_formula'), $interim_results, $calc_var_names, $data_query_variables);

		if ($error != 'VALID') {
			session_custom_error_message('measurand_formula', $error);
		}
	}

	//Check possible dependences with other measurands
	if (!is_error_message_field('measurand_abbreviation') && get_request_var('id') != 0) {
		$dependences = array();

		$new = get_request_var('measurand_abbreviation');

		$old = db_fetch_cell_prepared("SELECT abbreviation
			FROM plugin_reportit_measurands
			WHERE id = ?",
			array(get_request_var('id')));

		if ($old != $new) {
			$dependencies = db_fetch_assoc_prepared("SELECT id, calc_formula
				FROM plugin_reportit_measurands
				WHERE template_id = ?
				AND id > ?
				AND calc_formula LIKE '%$old%'",
				array(get_request_var('template_id'), get_request_var('id')));

			if (sizeof($dependencies)) {
				foreach($dependences as $key => $value) {
					$value['calc_formula'] = str_replace($old, $new, $value['calc_formula']);
					$dependences[$key]     = $value;
				}
			}
		}

		//Check if interim results are used in other measurands
		if(isset_request_var('measurand_spanned')) {
			$count = db_fetch_cell_prepared("SELECT COUNT(*)
				FROM plugin_reportit_measurands
				WHERE template_id = ?
				AND id > ?
				AND calc_formula LIKE '%$old:%'",
				array(get_request_var('template_id'), get_request_var('id')))
;

			if ($count != 0) {
				session_custom_error_message('measurand_spanned', __('Interim results are used by other measurands.', 'reportit'));
			}
		}
	}

	$measurand_data = array();
	$measurand_data['id']             = get_request_var('id');
	$measurand_data['template_id']    = get_request_var('template_id');
	$measurand_data['group_id']    	  = get_request_var('measurand_group');
	$measurand_data['description']    = get_nfilter_request_var('measurand_description');
	$measurand_data['abbreviation']   = strtoupper(get_request_var('measurand_abbreviation'));
	$measurand_data['unit']           = get_nfilter_request_var('measurand_unit');
	$measurand_data['visible']        = isset_request_var('measurand_visible') ? 'on' : '';
	$measurand_data['spanned']        = isset_request_var('measurand_spanned') ? 'on' : '';
	$measurand_data['calc_formula']   = get_request_var('measurand_formula');
	$measurand_data['rounding']       = isset_request_var('measurand_rounding') ? get_request_var('measurand_rounding'): '';
	$measurand_data['cf']             = get_request_var('measurand_cf');
	$measurand_data['data_type']      = get_request_var('measurand_type');
	$measurand_data['data_precision'] = isset_request_var('measurand_precision') ? get_request_var('measurand_precision') : '';

	if (is_error_message()) {
		header('Location: templates.php?header=false&action=edit&tab=measurands&tab_action=edit&id=' . get_request_var('template_id') . '&measurand_id=' . get_request_var('id'));
	} else {
		//Save data
		sql_save($measurand_data, 'plugin_reportit_measurands');

		//Update dependences if it's necessary
		if (isset($dependencies) && sizeof($dependencies)) {
			update_formulas($dependencies);
		}

		//Return to list view if it was an existing measurand
		header('Location: templates.php?header=false&action=edit&tab=measurands&id=' . get_request_var('template_id'));
		raise_message(1);
	}
}

function template_edit() {
	global $config, $report_template_tabs;

	/* ================= input validation ================= */
	$id = get_filter_request_var('id', FILTER_VALIDATE_INT, array('default'=>0) );
	/* ==================================================== */

	session_custom_error_display();

	if ($id) {
		$template_data = db_fetch_row('SELECT *	FROM plugin_reportit_templates	WHERE id = ' . $id);
		$header_label = __('Template Configuration [edit: %s]', $template_data['name'], 'reportit');
	} else {
		$template_data['id'] = 0;
		$header_label = __('Template Configuration [new]', 'reportit');
	}

	if ($id) {
		/* draw the tabs */
		print "<div class='tabs'><nav><ul role='tablist'>\n";
		foreach (array_keys($report_template_tabs) as $tab_short_name) {
			print "<li class='subTab'><a class='tab" . (($tab_short_name == get_request_var('tab')) ? " selected'" : "'") .
				" href='" . htmlspecialchars('./templates.php?action=edit&id=' . $id .	'&tab=' . $tab_short_name) . "'>" . $report_template_tabs[$tab_short_name] . "</a></li>\n";
		}
		print "</ul></nav></div>\n";
	}

	switch(get_request_var('tab')){
		case 'data_templates':
			if(get_request_var('tab_action') == 'edit') {
				template_edit_data_templates($id, $header_label);
			}else {
				template_data_templates($id, $header_label);
			}
			break;
		case 'groups':
		case 'variables':
		case 'measurands':
			if(get_request_var('tab_action') == 'edit') {
				$function = 'template_edit_' . get_request_var('tab');
				$function($id, $header_label);
			}else {
				draw_tab_table($id, $header_label);
			}
			break;
		case 'reports':
			template_reports($id, $header_label);
			break;
		default:
			global $fields_template_edit;
			if (read_config_option('reportit_auto_export')) {
				$fields_template_edit['template_export_folder']['method'] = 'hidden';
			}
			form_start('templates.php');
			html_start_box($header_label, '100%', '', '2', 'center', '');
			draw_edit_form(
				array(
					'config' => array('no_form_tag' => true),
					'fields' => inject_form_variables($fields_template_edit, $template_data)
				)
			);
			html_end_box(true);
			form_save_button('templates.php', 'return');
			break;
	}
}

function template_edit_data_templates($template_id, $header_label) {

	/* ================= input validation ================= */
	$data_template_id = get_filter_request_var('data_template_id');
	/* ==================================================== */

	session_custom_error_display();

	$fields_data_template_edit = html_template_ds_alias($template_id, $data_template_id);

	form_start('templates.php');
	html_start_box($header_label, '100%', '', '2', 'center', '');
	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_data_template_edit)
		)
	);
	html_end_box();
	form_save_button('templates.php?action=edit&id=' . $template_id . '&tab=data_templates');
}

function template_edit_groups($template_id, $header_label) {
	global $fields_group_edit;

	/* ================= input validation ================= */
	$id = get_filter_request_var('group_id', FILTER_VALIDATE_INT);
	/* ==================================================== */

	session_custom_error_display();

	$group_data = db_fetch_row('SELECT * FROM plugin_reportit_data_template_groups WHERE id = ' . $id);
	$header = __('[group: %s]', $group_data['name'], 'reportit');

	form_start('templates.php');
	html_start_box($header_label . $header, '100%', '', '2', 'center', '');
	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_group_edit, $group_data)
		)
	);
	html_end_box();
	form_save_button('templates.php?action=edit&id=' . $template_id . '&tab=groups');
}

function template_edit_variables($template_id, $header_label) {
	global $fields_variable_edit;

	/* ================= input validation ================= */
	$id = get_filter_request_var('variable_id', FILTER_VALIDATE_INT, array('default'=>0) );
	/* ==================================================== */

	session_custom_error_display();

	if ($id) {
		$variable_data = db_fetch_row('SELECT * FROM plugin_reportit_variables WHERE id = ' . $id);
	} else {
		$variable_data['id'] = 0;
		$variable_data['template_id'] = $template_id;
		$variable_data['abbreviation'] = __('created automatically', 'reportit');
	}
	$header = __('[variable: %s]', (isset($variable_data['name']) ? $variable_data['name'] : 'new'), 'reportit');

	?>
	<script type='text/javascript'>
	$(function(){
		$('#variable_type').change( function(){
			$('#variable_stepping').prop('disabled', ($('#variable_type').val() == 2 ) ? true: false );
		}).trigger('change');
	});
	</script>
	<?php

	form_start('templates.php');
	html_start_box($header_label . $header, '100%', '', '2', 'center', '');
	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_variable_edit, $variable_data)
		)
	);
	html_end_box();
	form_save_button('templates.php?action=edit&id=' . $template_id . '&tab=variables');
}

function template_edit_measurands($template_id, $header_label) {
	global $fields_measurands_edit;

	/* ================= input validation ================= */
	$id = get_filter_request_var('measurand_id', FILTER_VALIDATE_INT, array('default'=>0) );
	/* ==================================================== */

	session_custom_error_display();

	if ($id) {
		$measurand_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_measurands
			WHERE id = ?',
			array($id));
	}else {
		$measurand_data['id'] = 0;
		$measurand_data['template_id'] = $template_id;
		$measurand_data['group_id'] = 0;
	}

	$header = __('[measurand: %s]', (isset($measurand_data['abbreviation']) ? $measurand_data['abbreviation'] : 'new'), 'reportit');

	$arg2['ops_and_opds'] = html_operations_and_operands($measurand_data);
	$arg2['sql'] = 'SELECT id, name FROM plugin_reportit_data_template_groups WHERE template_id = ' . $template_id . ' ORDER BY name';

	form_start('templates.php');
	html_start_box($header_label . $header, '100%', '', '2', 'center', '');
	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_measurands_edit, $measurand_data, $arg2)
		)
	);
	html_end_box();
	form_save_button('templates.php?action=edit&id=' . $template_id . '&tab=measurands');

	?>
	<script type='text/javascript'>

	$(function() {
		$('#measurand_group').change(function() {
			$.post('templates.php?action=query_operations_and_operants', {
				id: <?php print $measurand_data['id'];?>,
				template_id: <?php print $measurand_data['template_id'];?>,
				group_id: $(this).val(),
				__csrf_magic: csrfMagicToken
			}).done(function(data) {
				$('#row_measurand_ops_and_opds > .formColumnRight > .formData').html(data);
				template_form_actions();
			});
		});
	});

	function change_data_type(){

		if ($('#measurand_type').val() in {0:'',2:'',3:'',4:'',5:'',6:''}) {
	 		$('#measurand_precision').selectmenu({
  				disabled: true
			});
		} else {
			$('#measurand_precision').selectmenu({
  				disabled: false
			});
		};

		if ($('#measurand_type').val() in {0:'', 4:'', 5:'', 6:'', 7:''}) {
			$('#measurand_rounding').selectmenu({
  				disabled: true
			});
		}else {
			$('#measurand_rounding').selectmenu({
  				disabled: false
			});
		}
	}


	function template_form_actions(){
		var fs_start;
		var fs_end;

		/* initiate group selection */
		$('#measurand_group').trigger('change');


		$('#measurand_formula').blur( function() {
			fs_start = $(this)[0].selectionStart;
			fs_end = $(this)[0].selectionEnd;
		});

		$('.ops-and-opds').off().click( function() {
			element = $(this).attr('id');
			formula = $('#measurand_formula').val();
			new_formula = '';
			opening_bracket = '(';
			closing_bracket = ')';

			if (element == 'rb') {
				element = ''
			}else if (element == 'sb') {
				element = ''
				opening_bracket = '[';
				closing_bracket = ']';
			}

			if($(this).hasClass('parentheses')) {
				if(fs_start !== undefined && fs_end !== undefined && fs_start !== fs_end) {
					new_formula = formula.substring(0,fs_start) + element + opening_bracket + formula.substring(fs_start, fs_end) + closing_bracket + formula.substring(fs_end, formula.length);
					fs_start +=  element.length + 1;
					fs_end += element.length + 1;
				}else if(fs_start !== undefined && fs_start === fs_end) {
					new_formula = formula.substring(0,fs_start) + element + opening_bracket + closing_bracket + formula.substring(fs_end, formula.length);
					fs_start = fs_end = fs_start + element.length + 1;
				}else {
					new_formula = formula + element + opening_bracket + closing_bracket;
					fs_start = fs_end = new_formula.length - 1;
				}
			}else {
				if(fs_start !== undefined && fs_end !== undefined && fs_start !== fs_end) {
					new_formula = formula.substring(0,fs_start) + element + formula.substring(fs_end, formula.length);
					fs_end = fs_start +=  element.length;
				}else if(fs_start !== undefined && fs_start === fs_end) {
					new_formula = formula.substring(0,fs_start) + element + formula.substring(fs_end, formula.length);
					fs_start += element.length;
					fs_end += element.length;
				}else {
					new_formula = formula + element;
					fs_start = fs_end = new_formula.length;
				}
			}
			$('#measurand_formula').val(new_formula).focus();
			$('#measurand_formula')[0].selectionStart = fs_start;
			$('#measurand_formula')[0].selectionEnd = fs_end;
			return false;
		});

		$('#measurand_type').change(function() {
			change_data_type();
		});

		/* initiate settings */
		change_data_type();

	};

	template_form_actions();
	</script>
	<?php
}

function template_data_templates($template_id, $header_label) {
	global $report_template_actions, $fields_tab_marker;

	/* ================= input validation and session storage ================= */
	if (!$template_id| !is_numeric($template_id)) die_html_input_error();

	$filters = array(
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'description',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_template_data_templates');
	/* ================= input validation and session storage ================= */

	$tab =  get_request_var('tab');

	/* show assignable/assigned data templates for existing report templates */
	$data_templates = array_rekey(db_fetch_assoc('SELECT id, name FROM data_template ORDER BY name'), 'id', 'name');

	$sql = 'SELECT DISTINCT plugin_reportit_data_source_items.data_template_id as id, data_template.name
				FROM plugin_reportit_data_source_items
				LEFT JOIN data_template ON
					data_template.id = plugin_reportit_data_source_items.data_template_id
				WHERE plugin_reportit_data_source_items.template_id = ' . $template_id . '
					AND plugin_reportit_data_source_items.data_template_id != 0';

	$data_templates_used = array_rekey(db_fetch_assoc($sql), 'id', 'name');
	$data_templates_unused = array_diff($data_templates, $data_templates_used);

	form_start('templates.php');
	html_start_box($header_label, '100%', '', '2', 'center', false);

	?>
	<tr class='odd'>
		<td class='saveRow' colspan='7'>
			<table>
				<tr style='line-height:10px;'>
					<td class='nowrap templateAdd' style='padding-right:15px;'>
						<?php print __('Add Data Template');?>
					</td>
					<td class='noHide'>
						<?php form_dropdown('data_template_id', $data_templates_unused, '', '', '', '', '');?>
					</td>
					<td class='noHide'>
						<input type='button' value='<?php print __esc('Add', 'reportit');?>' id='add_dt' title='<?php print __esc('Add Data Template to Report Template', 'reportit');?>'>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php

	$display_text = array(
		__('Auto Assigned Groups', 'reportit'),
		__('ID', 'reportit'),
		__('Selected DS Items', 'reportit'),
		__('Defined DS Items', 'reportit'),
		__('Measurands', 'reportit'),
		__('Generic Group ID', 'reportit'),
	);

	$sql_order = get_order_string();
	$sql = 'SELECT DISTINCT plugin_reportit_data_source_items.data_template_id AS id,
					count(plugin_reportit_data_source_items.id) as ds_cnt,
					data_template.name AS dt_name,
					plugin_reportit_data_source_items.group_id,
					plugin_reportit_data_template_groups.name as group_name
				FROM plugin_reportit_data_source_items
				LEFT JOIN data_template ON
					data_template.id = plugin_reportit_data_source_items.data_template_id
				LEFT JOIN plugin_reportit_data_template_groups
					ON
						plugin_reportit_data_template_groups.id = plugin_reportit_data_source_items.group_id
				WHERE plugin_reportit_data_source_items.template_id = ?
					AND plugin_reportit_data_source_items.id != 0
				GROUP BY plugin_reportit_data_source_items.data_template_id
				ORDER BY group_name, group_id, dt_name';

	$data_templates_used_detailed = db_fetch_assoc_prepared($sql, array($template_id));

	$sql = 'SELECT data_template_id, count(data_template_id) as ds_cnt FROM data_template_rrd WHERE local_data_id = 0 GROUP BY data_template_id';
	$data_templates_ds_count = array_rekey( db_fetch_assoc($sql), 'data_template_id', array('ds_cnt') );

	$sql = 'SELECT * FROM plugin_reportit_data_template_groups
			LEFT JOIN
				(SELECT group_id, count(group_id) as mea_cnt FROM plugin_reportit_measurands WHERE template_id = ? GROUP BY group_id) as measurands
			ON measurands.group_id = id
			WHERE template_id = ?';

	$data_template_groups = array_rekey( db_fetch_assoc_prepared($sql, array($template_id, $template_id)), 'id', array('name', 'description', 'generic_group_id', 'mea_cnt'));

	$sql = 'SELECT DISTINCT ( CASE WHEN data_source_alias != "" THEN data_source_alias ELSE data_source_name END) as name, group_id FROM plugin_reportit_data_source_items WHERE  plugin_reportit_data_source_items.template_id = ? AND plugin_reportit_data_source_items.id != 0 ORDER BY id';
	$data_template_group_ds_items_tmp =  db_fetch_assoc_prepared($sql, array($template_id));

	if($data_template_group_ds_items_tmp && sizeof($data_template_group_ds_items_tmp)) {
		foreach($data_template_group_ds_items_tmp as $data_template_group_ds_item) {
			if(isset($data_template_group_ds_items[$data_template_group_ds_item['group_id']])) {
				$data_template_group_ds_items[$data_template_group_ds_item['group_id']] .= ', ' . $data_template_group_ds_item['name'];
			}else {
				$data_template_group_ds_items[$data_template_group_ds_item['group_id']] = $data_template_group_ds_item['name'];
			}
		}
	}

	html_header_checkbox($display_text);

	$current_group = 0;
	if (sizeof($data_templates_used_detailed) > 0) {
		foreach($data_templates_used_detailed as $key => $data_template_used_detailed) {

			if($data_template_used_detailed['group_id'] != $current_group) {
				$current_group = $data_template_used_detailed['group_id'];

				form_alternate_row('group' . $data_template_used_detailed['group_id'], true);
				print '<td>' . '<a class="linkEditMain" title="' . htmlspecialchars($data_template_groups[$current_group]['description']) . '"' . 'href="templates.php?action=edit&tab=groups&tab_action=edit&id=' . $template_id . '&group_id=' . $current_group . '">' . $data_template_groups[$current_group]['name'] . '</a>' . '</td>'
				 	. 	'<td></td>'
				  	. 	'<td></td>'
				 	. 	'<td>' . $data_template_group_ds_items[$current_group] . '</td>'
				 	. 	'<td>' . ($data_template_groups[$current_group]['mea_cnt'] ? $data_template_groups[$current_group]['mea_cnt'] : 0) . '</td>'
				 	. 	'<td>' . $data_template_groups[$current_group]['generic_group_id']. '</td>'
				 	. 	'<td></td>';
				form_end_row();
			}

			form_alternate_row('line' . $data_template_used_detailed['id'], true);
			form_selectable_cell("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . '<i class="fa fa-caret-right" aria-hidden="true"></i>' . "&nbsp;" . '<a class="linkEditMain" href="templates.php?action=edit&tab=data_templates&tab_action=edit&id=' . $template_id . '&data_template_id=' . $data_template_used_detailed['id'] . '">' . $data_template_used_detailed['dt_name'] . '</a>', $data_template_used_detailed['id']);
			form_selectable_cell($data_template_used_detailed['id'], $data_template_used_detailed['id']);
			form_selectable_cell($data_template_used_detailed['ds_cnt'] . '/' . $data_templates_ds_count[$data_template_used_detailed['id']]['ds_cnt'], $data_template_used_detailed['id']);
			form_selectable_cell('', $data_template_used_detailed['id']);
			form_selectable_cell('', $data_template_used_detailed['id']);
			form_selectable_cell('', $data_template_used_detailed['id']);
			form_checkbox_cell($data_template_used_detailed['dt_name'], $data_template_used_detailed['id']);
			form_end_row();
		}
	} else {
		print '<tr><td colspan="3"><em>' . __('No associated data templates found.', 'reportit') . '</em></td></tr>';
	}

	?>
	<script type='text/javascript'>
	$(function() {
		$('#add_dt').click(function() {
			$.post('templates.php?action=dt_add', {
				template_id: <?php print $template_id;?>,
				data_template_id: $('#data_template_id').val(),
				__csrf_magic: csrfMagicToken
			}).done(function(data) {
				$('div[class^="ui-"]').remove();
				$('#main').html(data);
				applySkin();
			});
		});
	});
	</script>
	<?php
	draw_edit_form(
		array(
			'config' => array(),
			'fields' => inject_form_variables($fields_tab_marker, array('id' =>  $template_id, 'tab' => $tab))
		)
	);
	html_end_box(true);
	draw_actions_dropdown($report_template_actions['data_templates']);
	form_end();
}

function draw_tab_table($template_id, $header_label) {
	global $report_template_actions, $report_template_display_text, $fields_tab_marker;

	/* ================= input validation ================= */
	if (!$template_id) die_html_input_error();
	/* ================= input validation ================= */

	$tab =  get_request_var('tab');

	$records = query_tab_table_records($template_id, $header_label, $tab);
	form_start('templates.php');
	html_start_box($header_label, '100%', '', '2', 'center', 'templates.php?action=edit&tab=' . $tab . '&tab_action=edit&id=' . $template_id);
	html_header_checkbox($report_template_display_text[$tab]);

	if (sizeof($records)) {
		foreach($records as $record) {
			draw_tab_table_record($template_id, $header_label, $tab, $record);
		}
	}else {
		print '<tr><td colspan="'. sizeof($report_template_display_text[$tab]) . '"><em>' . __('No records found.', 'reportit') . '</em></td></tr>';
	}

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => inject_form_variables($fields_tab_marker, array('id' =>  $template_id, 'tab' => $tab))
		)
	);
	html_end_box(true);
	draw_actions_dropdown($report_template_actions[$tab]);
	form_end();
}

function draw_tab_table_record($template_id, $header_label, $tab, $record) {

	switch($tab){
		case 'variables':
			global $variable_input_types;
			$select_options_count = ($record['input_type'] == 1) ? ( ceil(($record['max_value']-$record['min_value'])/$record['stepping']))+1 : false;
			$select_options_class = '';
			$icon = '';

			if($select_options_count !== false) {
				if($select_options_count <= 100) {
					$select_options_class = 'deviceUp';
					$icon = 'fa-thumbs-up';
				}else if($select_options_count <= 500) {
					$select_options_class = 'deviceDownMuted';
					$icon = 'fa-thumbs-down';
				}else {
					$select_options_class = 'deviceDown';
					$icon = 'fa-exclamation-triangle';
				}
			}

			form_alternate_row('line' . $record['id'], true);
			form_selectable_cell('<a class="linkEditMain"' . (($record['description']) ? ' title="' . htmlspecialchars($record['description']) . '"' : '') . ' href="templates.php?action=edit&tab=variables&tab_action=edit&id=' . $template_id . '&variable_id=' . $record['id'] . '">' . $record['name'] . '</a>', $record['id']);
			form_selectable_cell($record['abbreviation'], $record['id']);
			form_selectable_cell($record['max_value'], $record['id']);
			form_selectable_cell($record['min_value'], $record['id']);
			form_selectable_cell($record['default_value'], $record['id']);
			form_selectable_cell($record['stepping'], $record['id']);
			form_selectable_cell($variable_input_types[$record['input_type']], $record['id'], 'left');
			form_selectable_cell('<font class="' . $select_options_class . '"><i class="fa ' . $icon . '" aria-hidden="true"></i> ' . (($select_options_count !== false) ? "($select_options_count)" : 'n/a' ) . '</font>', $record['id']);
			form_checkbox_cell($record['name'], $record['id']);
			form_end_row();
		break;
		case 'measurands':
			global $consolidation_functions;
			form_alternate_row('line' . $record['id'], true);
			form_selectable_cell($record['id'], $record['id']);
			form_selectable_cell('<a class="linkEditMain"' . (($record['description']) ? ' title="' . htmlspecialchars($record['description']) . '"' : '') . ' href="templates.php?action=edit&tab=measurands&tab_action=edit&id=' . $template_id . '&measurand_id=' . $record['id'] . '">' . $record['abbreviation'] . '</a>', $record['id']);
			form_selectable_cell('<a class="linkEditMain" href="templates.php?action=edit&tab=groups&tab_action=edit&id=' . $template_id . '&group_id=' . $record['grp_id'] . '">' . $record['grp_name'] . '</a>', $record['id']);
			form_selectable_cell($record['unit'], $record['id']);
			form_selectable_cell($consolidation_functions[$record['cf']], $record['id']);
			form_selectable_cell( ($record['visible'] ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>'), $record['id']);
			form_selectable_cell( ($record['spanned'] ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>'), $record['id']);
			form_selectable_cell($record['calc_formula'], $record['id']);
			form_checkbox_cell($record['description'], $record['id']);
			form_end_row();
		break;
		case 'groups':
			form_alternate_row('line' . $record['id'], true);
			form_selectable_cell($record['id'], $record['id']);
			form_selectable_cell('<a class="linkEditMain"' . (($record['description']) ? ' title="' . htmlspecialchars($record['description']) . '"' : '') . ' href="templates.php?action=edit&tab=groups&tab_action=edit&id=' . $template_id . '&group_id=' . $record['id'] . '">' . $record['name'] . '</a>', $record['id']);
			form_selectable_cell($record['generic_group_id'], $record['id']);
			form_selectable_cell(str_replace(';', '<br>', $record['elements']), $record['id']);
			form_selectable_cell(($record['mea_cnt'] ? $record['mea_cnt'] : 0), $record['id']);
			form_selectable_cell($record['dt_cnt'], $record['id']);
			form_checkbox_cell($record['name'], $record['id']);
			form_end_row();
		break;
	}
}

function query_tab_table_records($template_id, $header_label, $tab) {
	$items = false;

	switch($tab){
		case 'variables':
			$items = db_fetch_assoc_prepared('SELECT *
						FROM plugin_reportit_variables
						WHERE template_id = ? ',
						array($template_id));
		break;
		case 'measurands':
			$items = db_fetch_assoc_prepared('SELECT plugin_reportit_measurands.*,
						plugin_reportit_data_template_groups.id as grp_id,
						plugin_reportit_data_template_groups.name as grp_name
						FROM plugin_reportit_measurands
						LEFT JOIN plugin_reportit_data_template_groups
						ON plugin_reportit_measurands.group_id = plugin_reportit_data_template_groups.id
						WHERE plugin_reportit_measurands.template_id = ?
						ORDER BY grp_name, id', array($template_id));
		break;
		case 'groups':
			$items = db_fetch_assoc_prepared('SELECT *
						FROM plugin_reportit_data_template_groups
						LEFT JOIN
							(SELECT group_id, count(data_template_id) as dt_cnt FROM plugin_reportit_data_source_items WHERE id != 0 AND template_id = ? GROUP BY group_id) as g_items
						ON g_items.group_id = id
						LEFT JOIN
							(SELECT group_id, count(group_id) as mea_cnt FROM plugin_reportit_measurands WHERE template_id = ? GROUP BY group_id) as measurands
						ON measurands.group_id = id
						WHERE template_id = ?
						ORDER BY id', array($template_id, $template_id, $template_id));
		break;
	}

	return $items;
}

function form_actions(){
	switch(get_request_var('tab')){
		case 'data_templates':
			form_actions__data_templates();
			break;
		case 'groups':
			form_actions__groups();
			break;
		case 'variables':
			form_actions__variables();
			break;
		case 'measurands':
			form_actions__measurands();
			break;
		default:
			form_actions__templates();
			break;
	}
}

function form_actions__templates() {
	global $report_template_actions, $config;

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));

		if (get_request_var('drp_action') == '1') { // DELETE REPORT TEMPLATE
			$template_datas = db_fetch_assoc('SELECT id FROM plugin_reportit_templates WHERE ' . array_to_sql_or($selected_items, 'id'));

			if (sizeof($template_datas) > 0) {
				foreach ($template_datas as $template_data) {
					db_execute('DELETE FROM plugin_reportit_templates WHERE id=' . $template_data['id']);
					db_execute('DELETE FROM plugin_reportit_variables WHERE template_id=' . $template_data['id']);
					db_execute('DELETE FROM plugin_reportit_measurands WHERE template_id =' . $template_data['id']);
					db_execute('DELETE FROM plugin_reportit_data_source_items WHERE template_id =' . $template_data['id']);

					$template_reports = db_fetch_assoc('SELECT id FROM plugin_reportit_reports WHERE template_id =' .$template_data['id']);

					if (is_array($template_reports)) {
						foreach($template_reports as $template_report) {
							db_execute('DELETE FROM plugin_reportit_reports WHERE id=' . $template_report['id']);
							db_execute('DELETE FROM plugin_reportit_data_items WHERE report_id = ' . $template_report['id']);
							db_execute('DROP TABLE IF EXISTS plugin_reportit_results_' . $template_report['id']);
							db_execute('DELETE FROM plugin_reportit_rvars WHERE report_id =' . $template_report['id']);
							db_execute('DELETE FROM plugin_reportit_presets WHERE id=' . $template_report['id']);
							db_execute('DELETE FROM plugin_reportit_recipients WHERE report_id=' . $template_report['id']);
						}
					}
				}
			}
		} elseif (get_request_var('drp_action') == '2') { //DUPLICATE REPORT TEMPLATE
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				$template_data = db_fetch_row('SELECT * FROM plugin_reportit_templates WHERE id = ' . $selected_items[$i]);
				$template_data['id'] = 0;
				$template_data['description'] = str_replace(__('<template_title>'), $template_data['description'], get_request_var('template_addition'));
				$template_id = sql_save($template_data, 'plugin_reportit_templates');

				$old = array();
				$new = array();

				/* duplicate all variable of the original template */
				$template_variables = db_fetch_assoc_prepared('SELECT *
					FROM plugin_reportit_variables
					WHERE template_id = ?
					ORDER BY id',
					array($selected_items[$i]));

				if (sizeof($template_variables)) {
					foreach($template_variables as $variable) {
						$variable['id']          = 0;
						$variable['template_id'] = $template_id;

						$new_id = sql_save($variable, 'plugin_reportit_variables');

						$old[]  = $variable['abbreviation'];
						$abbr   = 'c' . $new_id . 'v';
						$new[]  = $abbr;

						db_execute_prepared('UPDATE plugin_reportit_variables
							SET abbreviation = ?
							WHERE id = ?' ,
							array($abbr, $new_id));
					}
				}

				/* duplicate all measurands of the original template */
				$template_measurands = db_fetch_assoc_prepared('SELECT *
					FROM plugin_reportit_measurands
					WHERE template_id = ?
					ORDER BY id',
					array($selected_items[$i]));

				if (sizeof($template_measurands)) {
					foreach($template_measurands as $measurand) {
						$measurand['id']           = 0;
						$measurand['template_id']  = $template_id;
						$measurand['calc_formula'] = str_replace($old,$new, $measurand['calc_formula']);

						sql_save($measurand, 'plugin_reportit_measurands');
					}
				}

				/* duplicate all data source items of the original */
				$template_ds_items = db_fetch_assoc_prepared('SELECT *
					FROM plugin_reportit_data_source_items
					WHERE template_id = ?
					ORDER BY id',
					array($selected_items[$i]));

				if (sizeof($template_ds_items)) {
					foreach($template_ds_items as $data_source_item) {
						$data_source_item['template_id'] = $template_id;

						sql_save($data_source_item, 'plugin_reportit_data_source_items', array('id', 'template_id'), false);
					}
				}
			}
		}else if ( in_array(get_request_var('drp_action'), array(3,4)) ){
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
				db_execute("UPDATE plugin_reportit_templates SET locked='" . ((get_request_var('drp_action') == 3) ? "on'" : "'" ) . ' WHERE id=' . $selected_items[$i]);
			}
		}

		header('Location: templates.php?header=false');
		exit;
	}

	//Set preconditions
	$ds_list = ''; $i = 0;

	foreach ($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			//Fetch template id
			$id = substr($key, 4);
			$template_ids[] = $id;

			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			//Fetch Template description
			$template_description = db_fetch_cell_prepared('SELECT description
				FROM plugin_reportit_templates
				WHERE id = ?',
				array($id));

			$template_identifier = $template_description . " [<a href='./templates.php?action=template_edit&id=$id'>$id</a>]";
			$ds_list[$template_identifier] = '';

			//Fetch all descriptions of reports attached to this template
			$template_reports = db_fetch_assoc_prepared('SELECT id, description
				FROM plugin_reportit_reports
				WHERE template_id = ?',
				array($id));

			foreach ($template_reports as $key => $value) {
				$ds_list[$template_identifier][] = "[<a href='./reports.php?action=report_edit&id={$template_reports[$key]['id']}'>{$template_reports[$key]['id']}</a>] " . $template_reports[$key]['description'];
			}
		}
	}

	top_header();

	form_start('templates.php');

	html_start_box($report_template_actions['templates'][get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (get_request_var('drp_action') == '1') {
		/* delete report template(s) */
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete the following Report Templates', 'reportit') . '</p>';

		if (is_array($ds_list)) {
			print __('WARNING: Every Report that belongs to these Templates will also be deleted!', 'reportit');

			foreach($ds_list as $key => $value) {
				print '<p>' . __('Template : %s', $key) . '</p>';

				if (is_array($ds_list[$key])) {
					print '<ul>';
					foreach($ds_list[$key] as $report_name => $value) {
						print '<li>' . __('Report: %s', $value) . '</li>';
					}
					print '</ul>';
				} else {
					print '<ul>';
					print '<li>' . __('Report: <i>None</i>') . '</li>';
					print '</ul>';
				}
			}
		}

		print '</td>
		</tr>';
	} elseif (get_request_var('drp_action') == '2') { // DUPLICATE REPORT TEMPLATE
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to duplicate the following report templates. You can optionally change the title of those duplicates.', 'reportit') . '</p>';

		if (is_array($ds_list)) {
			print	'<p>' . __('List of selected report templates:', 'reportit') . '</p>';

			if (sizeof($ds_list)) {
				print '<ul>';
				foreach($ds_list as $key => $value) {
					print '<li>' . $key . '</li>';
				}
				print '</ul>';
			}
		}

		print '<p>' . __('Title:') . '<br>';

		form_text_box('template_addition', __('<template_title> (1)', 'reportit'), '', '255', '30', 'text');

		print '</p>
			</td>
		</tr>';
	} elseif ( in_array( get_request_var('drp_action'), array(3,4)) ) { // Lock
		print "<tr><td class='textArea'><p>";
		print (get_request_var('drp_action') == 3)
				? __('Click \'Continue\' to lock the following report templates. This will AVOID the modifcation of templates as well as underlying measurands and variables.', 'reportit')
				: __('Click \'Continue\' to unlock the following report templates. This will ALLOW the modifcation to templates as well as underlying measurands and variables.', 'reportit');

		if (is_array($ds_list)) {
			print	'<p>' . __('List of selected report templates:', 'reportit') . '</p>';
			if (sizeof($ds_list)) {
				print '<ul>';
				foreach($ds_list as $key => $value) {
					print '<li>' . $key . '</li>';
				}
				print '</ul>';
			}
		}
	}

	if (!is_array($ds_list)) {
		print "<tr>
			<td class='textArea'>
				<span class='textError'>" . __('You must select at least one Report Template.', 'reportit') . '</span>
			</td>
		</tr>';

		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";
	} elseif (get_request_var('drp_action') == '1') {
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Delete Report Templates', 'reportit') . "'>";
	} else {
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Duplicate Report Templates', 'reportit') . "'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($template_ids) ? serialize($template_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function form_actions__data_templates() {

	// ================= input validation =================
	$template_id = get_filter_request_var('id');
	$drp_action = get_filter_request_var('drp_action');
	// ====================================================

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_nfilter_request_var('selected_items')));

		if(sizeof($selected_items)>0) {
			foreach($selected_items as $selected_data_template ) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_data_template);
				/* ==================================================== */

				if($drp_action == 1) { // delete
					db_execute_prepared('DELETE FROM plugin_reportit_data_source_items WHERE template_id = ? AND data_template_id = ?', array($template_id, $selected_data_template));
				}
			}

			$abandoned_groups = db_fetch_assoc_prepared('SELECT count(*) as cnt, group_id FROM plugin_reportit_data_source_items WHERE template_id = ? GROUP BY group_id', array($template_id));

#TODO - LOGIKfehler!!!!

			if(sizeof($abandoned_groups)>0) {
				foreach($abandoned_groups as $abandoned_group ) {
					if($abandoned_group['cnt'] == '1') {
						db_execute_prepared('DELETE FROM plugin_reportit_data_source_items WHERE group_id = ?', array($abandoned_group['group_id']));
						db_execute_prepared('DELETE FROM plugin_reportit_data_template_groups WHERE id = ?', array($abandoned_group['group_id']));
						db_execute_prepared('UPDATE plugin_reportit_measurands SET group_id = 0 WHERE group_id = ?', array($abandoned_group['group_id']));
					}
				}
			}
		}
		header('Location: templates.php?header=false&action=edit&tab=data_templates&id=' . $template_id );
		exit;
	}

	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			$id = substr($key, 4);

			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			$dt_ids[] = $id;
			$dt_name 	= db_fetch_cell_prepared('SELECT data_template.name
				FROM plugin_reportit_data_source_items
				LEFT JOIN data_template ON
					data_template.id = plugin_reportit_data_source_items.data_template_id
				WHERE data_template_id = ?',
				array($id));

			$grp_list[] = $dt_name;
		}
	}

	top_header();
	form_start('templates.php');
	html_start_box( __('Delete', 'reportit'), '60%', '', '2', 'center', '');

	print '<tr>
				<td class="textArea"
				<p>'
				. __('Please note that assigned groups will be removed automatically once a group does not contain a data template anymore. Measurands related to this group will remain on your system, but stay inactive until you will have assigned a new group to them.', 'reportit') . '<br>'
				. __n('Click \'Continue\' to Delete the following data template:', 'Click \'Continue\' to Delete the following data templates:', sizeof($grp_list), 'reportit') . '</p>'
				. (is_array($grp_list) ? '<ul><li>' . implode('</li><li>', $grp_list) . '</li></ul>' : '' ).
				'</td>
			</tr>
	';

	if (!is_array($grp_list)) {
		print '<tr><td class="odd"><span class="textError">' . __('You must select at least one data template.', 'reportit') . '</span></td></tr>';
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=data_templates\")'>";
	}else {
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=data_templates\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc_n('Delete Data Template', 'Delete Data Templates', sizeof($grp_list), 'reportit') . "'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='id' value='" . $template_id . "'>
			<input type='hidden' name='tab' value='data_templates'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($dt_ids) ? serialize($dt_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	form_end();
	bottom_footer();
}



function form_actions__groups() {

	// ================= input validation =================
	$template_id = get_filter_request_var('id');
	$drp_action = get_filter_request_var('drp_action');
	// ====================================================

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_nfilter_request_var('selected_items')));

		if(sizeof($selected_items)>0) {
			foreach($selected_items as $selected_group ) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_group);
				/* ==================================================== */

				if($drp_action == 1) { // delete
					db_execute_prepared('DELETE FROM plugin_reportit_data_template_groups WHERE id = ?', array($selected_group));
					db_execute_prepared('DELETE FROM plugin_reportit_data_source_items WHERE group_id = ?', array($selected_group));
					db_execute_prepared('UPDATE plugin_reportit_measurands SET group_id = 0 WHERE group_id = ?', array($selected_group));
				}
			}
		}
		header('Location: templates.php?header=false&action=edit&tab=groups&id=' . $template_id );
		exit;
	}

	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			$id = substr($key, 4);

			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			$group_ids[] = $id;
			$group_name 	= db_fetch_cell_prepared('SELECT name
				FROM plugin_reportit_data_template_groups
				WHERE id = ?',
				array($id));

			$grp_list[] = $group_name;
		}
	}

	top_header();
	form_start('templates.php');
	html_start_box( __('Delete', 'reportit'), '60%', '', '2', 'center', '');

	print '<tr>
				<td class="textArea"
				<p>'
				. __('Please note that measurands related to this group will remain on your system, but stay inactive until you will have assigned a new group to them.', 'reportit') . '<br>'
				. __n('Click \'Continue\' to Delete the following groups:', 'Click \'Continue\' to Delete the following groups:', sizeof($grp_list), 'reportit') . '</p>'
				. (is_array($grp_list) ? '<ul><li>' . implode('</li><li>', $grp_list) . '</li></ul>' : '' ).
				'</td>
			</tr>
	';

	if (!is_array($grp_list)) {
		print '<tr><td class="odd"><span class="textError">' . __('You must select at least one group.', 'reportit') . '</span></td></tr>';
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=groups\")'>";
	}else {
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=groups\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc_n('Delete Template Group', 'Delete Template Groups', sizeof($grp_list), 'reportit') . "'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='id' value='" . $template_id . "'>
			<input type='hidden' name='tab' value='groups'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($group_ids) ? serialize($group_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	form_end();
	bottom_footer();
}




function form_actions__variables() {
	global $report_template_actions;
	$error = FALSE;
	$ds_list = FALSE;

	// ================= input validation =================
	$template_id = get_filter_request_var('id');
	$drp_action = get_filter_request_var('drp_action');
	// ====================================================

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_nfilter_request_var('selected_items')));

		if(sizeof($selected_items)>0) {
			foreach($selected_items as $selected_variable ) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_variable);
				/* ==================================================== */

				if($drp_action == 1) { // delete
					db_execute_prepared('DELETE FROM plugin_reportit_variables WHERE id = ?', array($selected_variable));
					db_execute_prepared('DELETE FROM plugin_reportit_rvars WHERE id = ?', array($selected_variable));
				}elseif($drp_action == 2) {	// duplicate
					$variable_data = db_fetch_row_prepared('SELECT * FROM plugin_reportit_variables WHERE id = ?' , array($selected_variable));

					if (sizeof($variable_data)) {
						$variable_data['id'] = 0;
						$new_id = sql_save($variable_data, 'plugin_reportit_variables');
						db_execute_prepared('UPDATE plugin_reportit_variables
							SET abbreviation = ?
							WHERE id = ?' ,
							array('c' . $new_id . 'v', $new_id));
					}
				}
			}
		}
		header('Location: templates.php?header=false&action=edit&tab=variables&id=' . $template_id );
		exit;
	}

	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			$id = substr($key, 4);

			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			$variable_ids[] = $id;
			$variable_name 	= db_fetch_cell_prepared('SELECT name
				FROM plugin_reportit_variables
				WHERE id = ?',
				array($id));

			$ds_list[] = $variable_name;
		}
	}

	top_header();
	form_start('templates.php');
	html_start_box( $report_template_actions['variables'][$drp_action], '60%', '', '2', 'center', '');

	print '<tr>
				<td class="textArea"
				<p>' .
				( ($drp_action == 1)
					? __n('Click \'Continue\' to Delete the following variable:', 'Click \'Continue\' to Delete the following variables:', sizeof($ds_list), 'reportit')
					: __n('Click \'Continue\' to Duplicate the following variable:', 'Click \'Continue\' to Duplicate the following variables:', sizeof($ds_list), 'reportit')
				)
			.	'</p>'
			.	(is_array($ds_list) ? '<ul><li>' . implode('</li><li>', $ds_list) . '</li></ul>' : '' ).
				'</td>
			</tr>
	';

	if ($drp_action == 1 && is_array($ds_list)) {
		//Check possible dependences for each variable
		foreach($variable_ids as $id) {
			$name = db_fetch_cell_prepared('SELECT abbreviation
				FROM plugin_reportit_variables
				WHERE id = ?',
				array($id));

			$count = db_fetch_cell_prepared("SELECT COUNT(*)
				FROM plugin_reportit_measurands
				WHERE template_id = ?
				AND calc_formula LIKE '%$name%'",
				array($template_id));

			if ($count != 0) {
				$error = true;
				break;
			}
		}
	}

	if (!is_array($ds_list)) {
		print '<tr><td class="odd"><span class="textError">' . __('You must select at least one variable.', 'reportit') . '</span></td></tr>';
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=variables\")'>";
	}elseif($error) {
		print '<tr><td class="odd"><span class="textError">' . __('There are one or more variables in use.', 'reportit') . '</span></td></tr>';
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=variables\")'>";
	}else {
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=variables\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . (($drp_action == 1) ? __esc_n('Delete Template Variable', 'Delete Template Variables', sizeof($ds_list), 'reportit') :  __esc_n('Duplicate Template Variable', 'Duplicate Template Variables', sizeof($ds_list), 'reportit') ) . "'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='id' value='" . $template_id . "'>
			<input type='hidden' name='tab' value='variables'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($variable_ids) ? serialize($variable_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	form_end();
	bottom_footer();
}

function form_actions__measurands() {
	global $report_template_actions;
	$ds_list = FALSE;

	// ================= input validation =================
	$template_id = get_filter_request_var('id');
	$drp_action = get_filter_request_var('drp_action');
	// ====================================================

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_nfilter_request_var('selected_items')));

		if(sizeof($selected_items)>0) {
			foreach($selected_items as $selected_measurand ) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_measurand);
				/* ==================================================== */

				if($drp_action == 1) { // delete
					db_execute_prepared('DELETE FROM plugin_reportit_measurands WHERE id = ?', array($selected_measurand));

					//Check if it is necessary to lock the report template
					//if (stat_autolock_template(get_request_var('id'))) {
					//	set_autolock_template(get_request_var('id'));
					//}

				}elseif($drp_action == 2) {	// duplicate
					$measurand_data = db_fetch_row_prepared('SELECT * FROM plugin_reportit_measurands WHERE id = ?' , array($selected_measurand));

					if (sizeof($measurand_data)) {
						$measurand_data['id'] = 0;
						$new_id = sql_save($measurand_data, 'plugin_reportit_measurands');
						db_execute_prepared('UPDATE plugin_reportit_measurands
							SET abbreviation = ?
							WHERE id = ?' ,
							array($measurand_data['abbreviation'] . $new_id, $new_id));
					}
				}
			}
		}
		header('Location: templates.php?header=false&action=edit&tab=measurands&id=' . get_request_var('id') );
		exit;
	}

	foreach($_POST as $key => $value) {
		if(strstr($key, 'chk_')) {
			$id = substr($key, 4);
			$measurand_ids[] = $id;

			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			$measurand_description 	= db_fetch_cell_prepared('SELECT description
				FROM plugin_reportit_measurands
				WHERE id = ?',
				array($id));

			$ds_list[] = $measurand_description;
		}
	}

	top_header();
	form_start('templates.php');
	html_start_box($report_template_actions['measurands'][$drp_action], '60%', '', '3', 'center', '');

	print '<tr>
				<td class="textArea"
				<p>' .
				( ($drp_action == 1)
					? __n('Click \'Continue\' to Delete the following measurand:', 'Click \'Continue\' to Delete the following measurands:', sizeof($ds_list), 'reportit')
					: __n('Click \'Continue\' to Duplicate the following measurand:', 'Click \'Continue\' to Duplicate the following measurands:', sizeof($ds_list), 'reportit')
				)
			.	'</p>'
			.	(is_array($ds_list) ? '<ul><li>' . implode('</li><li>', $ds_list) . '</li></ul>' : '' ).
				'</td>
			</tr>
	';

	if (!is_array($ds_list)) {
		print "<tr><td class='odd'><span class='textError'>" . __('You must select at least one variable.', 'reportit') . '</span></td></tr>';
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=measurands\")'>";
	}else {
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?action=edit&id=" . get_request_var('id') . "&tab=measurands\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . (($drp_action == 1) ? __esc_n('Delete Measurand', 'Delete Measurands', sizeof($ds_list), 'reportit') :  __esc_n('Duplicate Measurand', 'Duplicate Measurands', sizeof($ds_list), 'reportit') ) . "'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='id' value='" . get_request_var('id') . "'>
			<input type='hidden' name='tab' value='measurands'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($measurand_ids) ? serialize($measurand_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	form_end();
	bottom_footer();
}

