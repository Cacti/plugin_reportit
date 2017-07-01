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

chdir('../../');

include_once('./include/auth.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/const_templates.php');

set_default_action();

switch (get_request_var('action')) {
	case 'actions':
		form_actions();
		break;
	case 'template_edit':
		top_header();
		template_edit();
		bottom_footer();
		break;
	case 'template_new':
		template_wizard('new');
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
	global $colors, $config, $list_of_data_templates;

	switch ($action) {
	case 'new':
		top_header();
		if (isset($_SESSION['reportit_tWizard'])) unset($_SESSION['reportit_tWizard']);

		html_start_box("<strong>New Template</strong>", "60%", $colors["header_panel"], "3", "center", "");

		print "<form action='cc_templates.php' autocomplete='off' method='post'>";

		if (sizeof($list_of_data_templates) == 0) {
			print "<tr class='odd'>
				<td>
					<span class='textError'>There are no data templates in use.</span>
				</td>
			</tr>";

			$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>";
		} else {
			$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue') . "' title='" . __('Create a new report template') . "'>";
			print "<tr class='odd'>
				<td>
					<p>" . __('Choose a data template this report template should depend on.<br>Unused data templates are hidden.') . "
				</td>";

			print "<td>" . form_dropdown('data_template', $list_of_data_templates, '', '', '', '', '') . "</td>
				</tr>";
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='template_edit'>
				$save_html
			</td>
		</tr>";

		html_end_box();
		bottom_footer();

		break;
	case 'export':
		top_header();

		$sql = "SELECT id, description FROM reportit_templates WHERE locked = 0";
		$templates = db_custom_fetch_assoc($sql, 'id', false);

		/* begin with the HTML output */
		html_start_box(__('Export Report Template'), '60%', '', '3', 'center', '');

		form_start('cc_templates.php');

		if ($templates === false) {
			print "	<tr bgcolor='#" . $colors['form_alternate1'] . "'>
				<td>
					<span class='textError'>No unlocked report templates available.</span>
				</td>
			</tr>";

			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>";
		} else {
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Export' title='Export report template'>";

			print "<table width='100%' cellpadding='2' cellspacing='2' bgcolor='#" . $colors['form_alternate1'] . "'";
			print "<tr><td valign='top'><strong>Report Template</strong><br>Choose one of your report templates to export to XML.</td><td>";
			print form_dropdown('template_id', $templates,'','','','','') . "</td></tr>";

			print "<tr><td><strong>Description</strong><br>Describe your report template.</td><td>";
			print form_text_area('template_description', '',4,37,'Your description') . "</td></tr>";

			print "<tr><td><strong>[Optional] Author</strong><br>You can fill a your name or your nick.</td><td>";
			form_text_box('template_author','','', 40, 50);
			print "</td></tr>";
			print "<tr><td><strong>[Optional] Version</strong><br>The version or revision of your template.</td><td>";
			form_text_box('template_version','','', 40, 50);
			print "</td></tr>";
			print "<tr><td><strong>[Optional] Contact</strong><br>Fill in your email address or something else if want to be reachable for other users of this template.</td><td>";
			form_text_box('template_contact','','', 40, 50);
			print "</td></tr>";
		}

		print "<tr>
			<td align='right' bgcolor='#eaeaea' colspan='2'>
				<input type='hidden' name='action' value='template_export'>
				$save_html
			</td>
		</tr>";

		html_end_box();
		bottom_footer();

		break;
	case 'upload':
		top_header();
		session_custom_error_display();

		html_start_box("<strong>Import Report Template</strong>", "60%", $colors["header_panel"], "3", "center", "");

		print "<form action='cc_templates.php' autocomplete='off' method='post' enctype='multipart/form-data'>";
		print "<tr>
			<td bgcolor='#" . $colors['form_alternate1'] . "'>
				Select the XML file that contains your report template.
			</td>
			<td bgcolor='#" . $colors['form_alternate1'] . "'>
				<input type='file' name='file' id='file' size='35' maxlength='50000' accept='xml'>
			</td>
		</tr>";

		print "<tr>
			<td align='right' bgcolor='#eaeaea' colspan='2'>
				<input type='hidden' name='action' value='template_import_wizard'>
				<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Upload' title='Upload Report Template'>
			</td>
		</tr>";

		html_end_box();
		bottom_footer();

		break;
	case 'import':
		/* clean up user session */
		if (isset($_SESSION['sess_reportit']['report_template'])) unset($_SESSION['sess_reportit']['report_template']);

		if (validate_uploaded_template() == true) {
			top_header();

			$save_html = ($_SESSION["sess_reportit"]["report_template"]["analyse"]["compatible"] == 'yes')
				? "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Import' title='Import Report Template'>"
				: "<input type='button' value='Cancel' onClick='window.history.back()'>";

			$info	   = $_SESSION["sess_reportit"]["report_template"]["general"];
			$templates  = $_SESSION["sess_reportit"]["report_template"]["analyse"]["templates"];

			clean_xml_waste($info, '<i>unknown</i>');

			html_start_box("<strong>Summary</strong>", "60%", $colors["header_panel"], "3", "center", "");
			print "<form action='cc_templates.php' autocomplete='off' method='post'>";

			print "<tr class='textArea' bgcolor='#" . $colors['form_alternate1'] . "'>
				<td colspan='4'></td>
			</tr>
			<tr class='textArea' bgcolor='#" . $colors['form_alternate1'] . "'>
				<td>Template Name:</td>
				<td>
					{$_SESSION["sess_reportit"]["report_template"]["settings"]["description"]}
				</td>
				<td align='right'>Version:</td>
				<td>
					{$info["version"]}
				</td>
			</tr>
			<tr class='textArea' bgcolor='#" . $colors['form_alternate1'] . "'>
				<td>Author:</td>
				<td>
					{$info["author"]}
				</td>
				<td align='right'>Contact:</td>
				<td>
					{$info["contact"]}
				</td>
			<tr>
			<tr class='textArea' bgcolor='#" . $colors['form_alternate1'] . "'>
				<td>Description:</td>
				<td colspan='4' width='85%'>
					" . nl2br($info['description']) ."
				</td>
			</tr>
			<tr class='textArea' bgcolor='#" . $colors['form_alternate1'] . "'>
				<td>Compatible:</td>
				<td colspan='4'>
					{$_SESSION["sess_reportit"]["report_template"]["analyse"]["compatible"]}
				</td>
			</tr>
			<tr class='textArea' bgcolor='#" . $colors['form_alternate1'] . "'>
				<td>Data Template:</td>
				<td colspan='4'>";
				($templates) ? form_dropdown('data_template', $templates, '', '', '', '', '') : print "no compatible template found";

			print "</tr>";

			print "<tr>
				<td align='right' bgcolor='#eaeaea' colspan='4'>
					<input type='hidden' name='action' value='template_import'>
					$save_html
				</td>
			</tr>";

			html_end_box();
			bottom_footer();
		} else {
			header('Location: cc_templates.php?action=template_upload_wizard');
		}

		bottom_footer();

		break;
	}
}

function template_export() {
	/* ================= input validation ================= */
	get_filter_request_var('template_id');
	/* ==================================================== */

	/* collect all additional information */
	$info = array();
	$info['description']	= get_request_var('template_description');
	$info['author']			= get_request_var('template_author');
	$info['version']		= get_request_var('template_version');
	$info['contact']		= get_request_var('template_contact');

	$output = export_report_template(get_request_var('template_id'), $info);
	if ($output == false) die_html_custom_error('Internal error.',true);

	header('Cache-Control: public');
	header('Content-Description: File Transfer');
	header('Cache-Control: max-age=1');
	header('Content-Type: application/xml');
	header('Content-Disposition: attachment; filename=\'template.xml\'');
	print '<?xml version=\'1.0\' encoding=\'UTF-8\'/>' . $output;
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
		header('Location: cc_templates.php?action=template_upload_wizard');
	}

	$template_data				= $_SESSION['sess_reportit']['report_template']['settings'];
	$template_variables			= $_SESSION['sess_reportit']['report_template']['variables'];
	$template_measurands		= $_SESSION['sess_reportit']['report_template']['measurands'];
	$template_data_source_items	= $_SESSION['sess_reportit']['report_template']['data_source_items'];

	$template_data['id'] = 0;
	$template_data['data_template_id'] = get_request_var('data_template');
	clean_xml_waste($template_data);

	$template_id = sql_save($template_data, 'reportit_templates');

	if (is_array($template_variables)) {
		if (!isset($template_variables['variable'][0])) {
			$variable = $template_variables['variable'];
			$variable['id'] = 0;
			$variable['template_id'] = $template_id;
			$new_id = sql_save($variable, 'reportit_variables');
			$old[] = $variable['abbreviation'];
			$abbr = 'c' . $new_id . 'v';
			$new[] = $abbr;
			db_execute("UPDATE reportit_variables SET abbreviation = '$abbr' WHERE id = $new_id");
		} else {
			$template_variables = $template_variables['variable'];
			foreach($template_variables as $variable) {
				$variable['id'] = 0;
				$variable['template_id']= $template_id;
				$new_id = sql_save($variable, 'reportit_variables');
				$old[] = $variable['abbreviation'];
				$abbr = 'c' . $new_id . 'v';
				$new[] = $abbr;
				db_execute("UPDATE reportit_variables SET abbreviation = '$abbr' WHERE id = $new_id");
			 }
		}
	}

	if (is_array($template_measurands)) {
		if (!isset($template_measurands['measurand'][0])) {
			$measurand = $template_measurands['measurand'];
			$measurand['id']			= 0;
			$measurand['template_id']   = $template_id;
			$measurand['calc_formula']  = str_replace($old,$new, $measurand['calc_formula']);
			sql_save($measurand, 'reportit_measurands');
		} else {
			$template_measurands = $template_measurands['measurand'];
			foreach($template_measurands as $measurand) {
				$measurand['id']			= 0;
				$measurand['template_id']   = $template_id;
				$measurand['calc_formula']  = str_replace($old,$new, $measurand['calc_formula']);
				sql_save($measurand, 'reportit_measurands');
			}
		}
	}

	if (is_array($template_data_source_items)) {
		if (!isset($template_data_source_items['data_source_item'][0])) {
			$ds_item = $template_data_source_items['data_source_item'];
			clean_xml_waste($ds_item);

			$sql = "SELECT id FROM `data_template_rrd`
					WHERE local_data_id = 0
					AND data_template_id = {get_request_var('data_template')}
					AND data_source_name = '{$ds_item['data_source_name']}'";
			$ds_item['id'] = db_fetch_cell($sql);
			$ds_item['template_id'] = $template_id;

			sql_save($ds_item, 'reportit_data_source_items', array('id', 'template_id'), false);

		} else {

			$template_ds_items = $template_data_source_items['data_source_item'];

			foreach($template_ds_items as $ds_item) {
				clean_xml_waste($ds_item);

				$sql = "SELECT id FROM `data_template_rrd`
						WHERE local_data_id = 0
						AND data_template_id = {get_request_var('data_template')}
						AND data_source_name = '{$ds_item['data_source_name']}'";
				$ds_item['id'] = db_fetch_cell($sql);
				$ds_item['template_id'] = $template_id;

				sql_save($ds_item, 'reportit_data_source_items', array('id', 'template_id'), false);
			}
		}
	}

	/* destroy the template data saved in current session */
	unset($_SESSION["sess_reportit"]["report_template"]);

	header('Location: cc_templates.php');
}

function template_filter() {
	global $item_rows;

	html_start_box( __('Report Templates'), '100%', '', '3', 'center', 'cc_templates.php?action=template_new');
	?>
	<tr class='even'>
		<td>
			<form id='form_templates' action='cc_templates.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Templates');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default');?></option>
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
						<input id='refresh' type='button' value='<?php print __esc_x('Button: use filter settings', 'Go');?>'>
					</td>
					<td>
						<input id='clear' type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear');?>'>
					</td>
					<td>
						<input id='import' type='button' value='<?php print __esc_x('Button: import button', 'Import');?>'>
					</td>
					<td>
						<input id='export' type='button' value='<?php print __esc_x('Button: export button', 'Export');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_filter_request_var('page');?>'>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL = 'cc_templates.php?filter='+escape($('#filter').val())+'&rows='+$('#rows').val()+'&header=false';
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = 'cc_templates.php?clear=1&header=false';
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
					document.location = 'cc_templates.php?action=template_upload_wizard';
				});

				$('#export').click(function() {
					document.location = 'cc_templates.php?action=template_export_wizard';
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
	global  $template_actions, $link_array, $desc_array, $consolidation_functions, $list_of_data_templates, $order_array;

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
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_cc_templates');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	if (get_request_var('filter') != '') {
		$sql_where = "WHERE (rs.name LIKE '%" . get_request_var('filter') . "%')";
	} else {
		$sql_where = '';
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$total_rows = db_fetch_cell('SELECT COUNT(reportit_templates.id) FROM reportit_templates ' . $sql_where);

	$template_list = db_fetch_assoc("SELECT a.*, b.measurands, c.variables
		FROM reportit_templates AS a
		LEFT JOIN (SELECT template_id, COUNT(*) AS measurands FROM `reportit_measurands` GROUP BY template_id) AS b
		ON a.id = b.template_id
		LEFT JOIN (SELECT template_id, COUNT(*) AS variables FROM `reportit_variables` GROUP BY template_id) AS c
		ON a.id = c.template_id
		$sql_where
		$sql_order
		$sql_limit");

	$display_text = array(
		'description' => array('display' => __('Name'),          'align' => 'left', 'sort' => 'ASC'),
		'nosort'      => array('display' => __('Internal Name'), 'align' => 'left'),
		'pre_filter'  => array('display' => __('Maximum'),       'align' => 'right'),
		'nosort1'     => array('display' => __('Minimum'),       'align' => 'right'),
		'nosort2'     => array('display' => __('Default'),       'align' => 'left', 'sort' => 'ASC'),
		'nosort3'     => array('display' => __('Input Type'),    'align' => 'left', 'sort' => 'ASC'),
	);

	$nav = html_nav_bar('cc_templates.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Templates'), 'page', 'main');

	template_filter();

	print $nav;

	html_start_box('', '100%', '', '2', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (sizeof($template_list)) {
		foreach($template_list as $template) {
			form_alternate_row('line' . $template['id'], true);

			form_selectable_cell('<a class="linkEditMain" href="cc_templates.php?action=template_edit&id=' . $template['id'] . '>' . $template['description'] . '</a>', $template['id']);

			if (isset($list_of_data_templates[$template['data_template_id']])) {
				form_selectable_cell($list_of_data_templates[$template['data_template_id']], $template['id']);
			} else {
				form_selectable_cell("<i class='deviceDown'>" . __('Data template not available') . '</i>', $template['id']);
			}

			form_selectable_cell($template['pre_filter'], $template['id']);
			form_selectable_cell(html_checked_with_icon($template['locked'], 'lock.gif', 'locked'), $template['id']);

			if ($template['measurands'] != NULL) {
				form_selectable_cell("<a class='linkEditMain' href='cc_measurands.php?id=" . $template['id'] . "'>edit (" . $template['measurands'] . ")</a>", $template['id']);
			} else {
				form_selectable_cell("<a class='linkEditMain' href='cc_measurands.php?action=measurand_edit&template_id=" . $template['id'] . "'>add</a>", $template['id']);
			}

			if ($template['variables'] != NULL) {
				form_selectable_cell("<a class='linkEditMain' href='cc_variables.php?id=" . $template['id'] . "'>edit (" . $template['variables'] . ")</a>", $template['id']);
			} else {
				form_selectable_cell("<a class='linkEditMain' href='cc_variables.php?action=variable_edit&template_id=" . $template['id'] . "'>add</a>", $template['id']);
			}

			form_checkbox_cell($template['id']);
			form_end_row();
		}
	} else {
		print "<tr><td colspan='7'><em>" . __('No templates') . "</em></td></tr>";
	}

	html_end_box(true);

	if (sizeof($template_list)) {
		print $nav;
	}

	draw_actions_dropdown($template_actions);
}

function form_save() {
	global $list_of_data_templates;

	$ds_items = array();
	$used_data_sources = '';
	$unused_data_sources = FALSE;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('template_data_template'));
	form_input_validate(get_request_var('template_description'), 'template_description', '', false, 3);
	form_input_validate(get_request_var('template_filter'), 'template_filter', '', true, 3);
	/* ==================================================== */

	$template_data = array();
	$template_data['id']               = get_request_var('id');
	$template_data['description']      = get_request_var('template_description');
	$template_data['pre_filter']       = get_request_var('template_filter');
	$template_data['data_template_id'] = get_request_var('data_template_id');
	$template_data['locked']           = isset_request_var('template_locked') ? 1 : 0;
	$template_data['export_folder']    = isset_request_var('template_export_folder') ? mysql_real_escape_string(get_request_var('template_export_folder')) : '';

	$sql = "SELECT id, data_source_name
		FROM data_template_rrd
		WHERE local_data_id = 0
		AND data_template_id = " . $template_data['data_template_id'];

	$defined_data_sources = db_custom_fetch_assoc($sql, 'id', false);
	$defined_data_sources[0] = 'overall';

	foreach($_POST as $key => $value){
		if (strpos($key, 'ds_enabled__') !== false) {
			$ds_id                                 = substr($key, 12);
			$used_data_sources                    .= ($ds_id != 0) ? "$ds_id," : '';
			$ds_name                               = $defined_data_sources[$ds_id];
			$ds_alias                              = 'ds_alias__' . $ds_id;
			$ds_items[$ds_id]['id']                = $ds_id;
			$ds_items[$ds_id]['template_id']       = $template_data['id'];
			$ds_items[$ds_id]['data_source_name']  = $ds_name;
			$ds_items[$ds_id]['data_source_alias'] = mysql_real_escape_string(trim(get_request_var($ds_alias)));
		}
	}

	if (!$used_data_sources) {
		session_custom_error_message('', 'No data source item selected.');
	} else {
		/* get the list of unused data sources */
		$sql = "SELECT id
			FROM data_template_rrd
			WHERE local_data_id = 0
			AND data_template_id = {$template_data['data_template_id']}
			AND id NOT IN (". substr($used_data_sources,0,-1) . ")";

		$unused_data_sources = db_custom_fetch_flat_string($sql);
	}

	/* check if there are data sources unselected although they are used in one of the defined measurands. */
	if ($template_data['id'] != 0 & $unused_data_sources !== FALSE) {
		/* get the list of unused data sources */
		$sql = "SELECT data_source_name
				FROM data_template_rrd
				WHERE local_data_id = 0
				AND data_template_id = {$template_data['data_template_id']}
				AND id NOT IN (". substr($used_data_sources,0,-1) . ")";
		$pattern = db_custom_fetch_flat_string($sql, '|');

		$sql = "SELECT `abbreviation`
				FROM reportit_measurands
				WHERE `template_id` = {$template_data['id']}
				AND `calc_formula` REGEXP '($pattern)'";
		$measurands = db_custom_fetch_flat_string($sql, ', ');

		if ($measurands !== FALSE) {
			session_custom_error_message('', "Unselected data source items are used in: $measurands");
		}
	}

	/* check if we can unlock this template. */
	if ($template_data['locked'] == 0) {
		if (stat_autolock_template($template_data['id'])) {
			session_custom_error_message('template_locked', 'Unable to unlock this template without defined measurands');
		}
	}

	if (is_error_message()) {
		header('Location: cc_templates.php?action=template_edit&id=' . get_request_var('id'));
	} else {
		/* save template data */
		$id = sql_save($template_data, 'reportit_templates');

		/* update template id for data source items if necessary */
		if (get_request_var('id') == 0) {
			foreach($ds_items as $key => $ds_item) {
				$ds_items[$key]['template_id']=$id;
			}
		}

		/* remove all data source items which are no longer in use */
		if ($unused_data_sources) {
			$sql = "DELETE FROM reportit_data_source_items WHERE template_id = $id AND id IN ($unused_data_sources)";
			db_execute($sql);
		}

		/* save the data source items */
		foreach($ds_items as $ds_item) sql_save($ds_item, 'reportit_data_source_items', array('id', 'template_id'), false);

		/* return to list view if it was an existing report template */
		if (get_request_var('id')!='0') {
			raise_message(1);
			header('Location: cc_templates.php');

		/* return to editor */
		} else {
			header('Location: cc_templates.php?action=template_edit&id=' . $id);
		}
	}
}

function template_edit() {
	global $colors, $consolidation_functions, $list_of_data_templates;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	session_custom_error_display();

	if (!isempty_request_var('id')) {
		$template_data = db_fetch_row_prepared('SELECT *
			FROM reportit_templates
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __('Template Configuration [edit: %s]',$template_data['description']);
	} else {
		$header_label = __('Template Configuration [new]');
	}

	$id = (isset_request_var('id') ? get_request_var('id') : '0');
	if (isset_request_var('data_template')) {
		if (!isset($_SESSION['reportit_tWizard']['data_template']))
		$_SESSION['reportit_tWizard']['data_template'] = get_request_var('data_template');
	}

	$data_template_id = (isset($template_data['data_template_id']) ? $template_data['data_template_id'] : $_SESSION['reportit_tWizard']['data_template']);
	$data_template = $list_of_data_templates[$data_template_id];

	$form_array = array(
		'id' => array(
			'method' => 'hidden_zero',
			'value' => $id
		),
		'data_template_id' => array(
			'method' => 'hidden_zero',
			'value' => $data_template_id
		),
		'ds_enabled__0' => array(
			'method' => 'hidden_zero',
			'value' => 'on'
		),
		'template_header' => array(
			'friendly_name' => __('General'),
			'method' => 'spacer',
		),
		'template_description' => array(
			'friendly_name' => __('Name'),
			'method' => 'textbox',
			'max_length' => '100',
			'description' => __('The unique name given to this report template.'),
			'value' => (isset($template_data['description']) ? $template_data['description'] : '')
		),
		'template_locked' => array(
			'friendly_name' => __('Locked'),
			'method' => 'checkbox',
			'default' => 'on',
			'description' => __('Define this template as locked (default), so that power users <br> can\'t use it until you have checked its functionality. During that time they are<br> also not allowed to modify or run reports based on this template.'),
			'value' => (!isset($template_data['locked']) || $template_data['locked'] == true) ? 'on' : 'off'
		),
		'template_filter' => array(
			'friendly_name' => __('Additional Pre-filter'),
			'method' => 'textbox',
			'max_length' => '100',
			'description' => __('Optional: The syntax to filter the available list of data items<br> by their description. Use SQL wildcards like % and/or _. No regular Expressions!'),
			'value' => (isset($template_data['pre_filter']) ? $template_data['pre_filter'] : '')
		)
	);

	if (read_config_option('reportit_auto_export')) {
		$form_array2 = array(
			'template_export_folder' => array(
				'friendly_name' => __('Export Path'),
				'description' => __('Optional: The path to an folder for saving the exports.  If it does not exist ReportIT automatically tries to create it during the first scheduled calculation, else it will try to create a new subfolder within the main export folder using the template id.'),
				'method' => 'dirpath',
				'max_length' => '255',
				'value' => isset($template_data['export_folder']) ? $template_data['export_folder'] : ''
			)
		);

		$form_array = array_merge($form_array, $form_array2);
	}

	$form_array3 = array(
		'template_header2' => array(
			'friendly_name' => __('Data Template'),
			'method' => 'spacer',
		),
		'template_data_template' => array(
			'friendly_name' => __('Data Template'),
			'method' => 'custom',
			'max_length' => '100',
			'description' => __('The name of the data template this report template depends on.'),
			'value' => $data_template
		)
	);

	$form_array = array_merge($form_array, $form_array3);

	/* draw input fields for data source alias */
	$data_source_items = html_template_ds_alias($id, $data_template_id);
	$form_array = array_merge($form_array, $data_source_items);

	if (!isempty_request_var('id')) {
		//Build 'Create links'
		$links		= array();
		$href		= 'cc_variables.php?action=variable_edit&template_id=' . get_request_var('id');
		$text		= __('Create a new variable');
		$links[]	= array('href' =>$href, 'text' =>$text);

		$href		= 'cc_measurands.php?action=measurand_edit&template_id=' . get_request_var('id');
		$text		= __('Create a new measurand');
		$links[]	= array('href' =>$href, 'text' =>$text);

		html_blue_link($links);
	}

	html_start_box($header_label, '100%', '', '2', 'center', '');

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		)
	);

	html_end_box();

	form_save_button('cc_templates.php');
}


function form_actions() {
	global $colors, $template_actions, $config;

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));

		if (get_request_var('drp_action') == '1') { // DELETE REPORT TEMPLATE
			$template_datas = db_fetch_assoc('SELECT id FROM reportit_templates WHERE ' . array_to_sql_or($selected_items, 'id'));

			if (sizeof($template_datas) > 0) {
				foreach ($template_datas as $template_data) {
					db_execute('DELETE FROM reportit_templates WHERE id=' . $template_data['id']);
					db_execute('DELETE FROM reportit_variables WHERE template_id=' . $template_data['id']);
					db_execute('DELETE FROM reportit_measurands WHERE template_id =' . $template_data['id']);
					db_execute('DELETE FROM reportit_data_source_items WHERE template_id =' . $template_data['id']);

					$template_reports = db_fetch_assoc('SELECT id FROM reportit_reports WHERE template_id =' .$template_data['id']);

					if (is_array($template_reports)) {
						foreach($template_reports as $template_report) {
							db_execute('DELETE FROM reportit_reports WHERE id=' . $template_report['id']);
							db_execute('DELETE FROM reportit_data_items WHERE report_id = ' . $template_report['id']);
							db_execute('DROP TABLE IF EXISTS reportit_results_' . $template_report['id']);
							db_execute('DELETE FROM reportit_rvars WHERE report_id =' . $template_report['id']);
							db_execute('DELETE FROM reportit_presets WHERE id=' . $template_report['id']);
							db_execute('DELETE FROM reportit_recipients WHERE report_id=' . $template_report['id']);
						}
					}
				}
			}
		} elseif (get_request_var('drp_action') == '2') { //DUPLICATE REPORT TEMPLATE
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				$template_data = db_fetch_row('SELECT * FROM reportit_templates WHERE id = ' . $selected_items[$i]);
				$template_data['id'] = 0;
				$template_data['description'] = str_replace("<template_title>", $template_data['description'], get_request_var('template_addition'));
				$template_id = sql_save($template_data, 'reportit_templates');

				$old = array();
				$new = array();

				/* duplicate all variable of the original template */
				$template_variables = db_fetch_assoc_prepared('SELECT *
					FROM reportit_variables
					WHERE template_id = ?
					ORDER BY id',
					array($selected_items[$i]));

				if (sizeof($template_variables)) {
					foreach($template_variables as $variable) {
						$variable['id']          = 0;
						$variable['template_id'] = $template_id;

						$new_id = sql_save($variable, 'reportit_variables');

						$old[]  = $variable['abbreviation'];
						$abbr   = 'c' . $new_id . 'v';
						$new[]  = $abbr;

						db_execute("UPDATE reportit_variables SET abbreviation = '$abbr' WHERE id = $new_id");
					}
				}

				/* duplicate all measurands of the original template */
				$template_measurands = db_fetch_assoc_prepared('SELECT *
					FROM reportit_measurands
					WHERE template_id = ?
					ORDER BY id',
					array($selected_items[$i]));

				if (sizeof($template_measurands)) {
					foreach($template_measurands as $measurand) {
						$measurand['id'] = 0;
						$measurand['template_id'] = $template_id;
						$measurand['calc_formula'] = str_replace($old,$new, $measurand['calc_formula']);
						sql_save($measurand, 'reportit_measurands');
					}
				}

				/* duplicate all data source items of the original */
				$template_ds_items = db_fetch_assoc_prepared('SELECT *
					FROM reportit_data_source_items
					WHERE template_id = ?
					ORDER BY id',
					array($selected_items[$i]));

				if (sizeof($template_ds_items)) {
					foreach($template_ds_items as $data_source_item) {
						$data_source_item['template_id'] = $template_id;
						sql_save($data_source_item, 'reportit_data_source_items', array('id', 'template_id'), false);
					}
				}
			}
		}

		header('Location: cc_templates.php');
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
			$template_description = db_fetch_cell('SELECT description FROM reportit_templates WHERE id=' . $id);
			$template_identifier = $template_description . " [<a href='./cc_templates.php?action=template_edit&id=$id'>$id</a>]";
			$ds_list[$template_identifier] = '';

			//Fetch all descriptions of reports attached to this template
			$template_reports = db_fetch_assoc('SELECT id, description FROM reportit_reports WHERE template_id=' . $id);

			foreach ($template_reports as $key => $value) {
				$ds_list[$template_identifier][] = "[<a href='./cc_reports.php?action=report_edit&id={$template_reports[$key]['id']}'>{$template_reports[$key]['id']}</a>] " . $template_reports[$key]['description'];
			}
		}
	}

	top_header();

	html_start_box($template_actions{get_request_var('drp_action')}, '60%', '', '3', 'center', '');

	form_start('cc_templates.php');

	if (get_request_var('drp_action') == '1') {
		/* delete report template(s) */
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete the following Report Templates') . '</p>';

		if (is_array($ds_list)) {
			print "<strong>" . __('WARNING:') . "</strong> " . __('Every report that belongs to these templates will be deleted too!');

			foreach($ds_list as $key => $value) {
				print "<p>Template : $key<br>";

				if (is_array($ds_list[$key])) {
					foreach($ds_list[$key] as $report_name => $value) {
						print __("&#160 |_Report: %s<br>", $value);
					}
				} else {
					print __("&#160 |_Report: <i>none</i><br>");
				}
			}
		}

		print "</p>
			</td>
		</tr>";
	} elseif (get_request_var('drp_action') == '2') { // DUPLICATE REPORT TEMPLATE
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Duplicate the following Report Templates.  You can optionally change the Title for the new Report Templates.') . '</p>';

		if (is_array($ds_list)) {
			print	'<p>' . __('List of selected templates:') . '<br>';

			foreach($ds_list as $key => $value) {
				print	__('&#160 |_Template : %s<br>', $key);
			}
		}

		print '<p><strong>' . __('Title:') . '</strong><br>';

		form_text_box('template_addition', __('<template_title> (1)'), '', '255', '30', 'text');

		print '</p>
			</td>
		</tr>';
	}

	if (!is_array($ds_list)) {
		print "<tr>
			<td class='textArea'>
				<span class='textError'>" . __('You must select at least one report template.') . '</span>
			</td>
		</tr>';

		$save_html = "<input type='button' value='" . __('Cancel') . " onClick='cactiReturnTo()'>";
	} elseif (get_request_var('drp_action') == '1') {
		$save_html = "<input type='button' value='" . __('Cancel') . " onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue') . " title='" . __('Delete Report Templates') . "'>";
	} else {
		$save_html = "<input type='button' value='" . __('Cancel') . " onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue') . " title='" . __('Duplicate Report Templates') . "'>";
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

	bottom_footer();
}

