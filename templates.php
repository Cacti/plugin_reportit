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
include_once(REPORTIT_BASE_PATH . '/include/global_forms.php');
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
	global $config, $list_of_data_templates, $fields_template_export;

	switch ($action) {
	case 'new':
		top_header();
		if (isset($_SESSION['reportit_tWizard'])) unset($_SESSION['reportit_tWizard']);

		form_start('templates.php');

		html_start_box(__('New Report Template', 'reportit'), '60%', '', '3', 'left', '');

		if (sizeof($list_of_data_templates) == 0) {
			print "<tr class='textArea'>
				<td>
					<span class='textError'>" . __('There are no Data Templates in use.', 'reportit') . "</span>
				</td>
			</tr>";
			$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php\")'>";
		} else {
			$save_html = '<input type="button" value="' . __esc('Cancel', 'reportit') . '" onClick="cactiReturnTo(\'templates.php\')">&nbsp;<input type="submit" value="' . __esc('Continue', 'reportit') . '" title="' . __esc('Create a new Report Template', 'reportit') . '">';
			print "<tr class='textArea'>
				<td>
					<p>" . __('Choose a Data Template this Report Template should depend on.  Unused Data Templates are hidden.', 'reportit') . "
				</td>
			</tr>";

			print "<tr class='textArea'>
				<td>" . form_dropdown('data_template', $list_of_data_templates, '', '', '', '', '') . "</td>
			</tr>";
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='template_edit'>
				$save_html
			</td>
		</tr>";

		html_end_box();

		form_end();

		bottom_footer();

		break;
	case 'export':
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
		top_header();

		session_custom_error_display();

		html_start_box(__('Import Report Template', 'reportit'), '60%', '', '3', 'center', '');

		print "<form action='templates.php' autocomplete='off' method='post' enctype='multipart/form-data'>";

		print "<tr class='textArea'>
			<td>" . __('Select the XML file that contains your Report Template.', 'reportit') . "</td>
			<td>
				<input type='file' name='file' id='file' size='35' maxlength='50000' accept='xml'>
			</td>
		</tr>";

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='template_import_wizard'>
				<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Upload', 'reportit') . "' title='" . __esc('Upload Report Template', 'reportit') . "'>
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

			$save_html = ($_SESSION['sess_reportit']['report_template']['analyse']['compatible'] == 'yes')
				? "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Import', 'reportit') . "' title='" . __esc('Import Report Template', 'reportit') . "'>"
				: "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";

			$info      = $_SESSION['sess_reportit']['report_template']['general'];
			$templates = $_SESSION['sess_reportit']['report_template']['analyse']['templates'];

			clean_xml_waste($info, '<i>unknown</i>');

			html_start_box(__('Summary', 'reportit'), '60%', '', '3', 'center', '');

			form_start('templates.php');

			print "<tr class='textArea'>
				<td colspan='4'></td>
			</tr>
			<tr class='textArea'>
				<td>" . __('Template Name:', 'reportit') . "</td>
				<td>" . $_SESSION['sess_reportit']['report_template']['settings']['description'] . "</td>
				<td class='right'>" . __('Version:', 'reportit') . "</td>
				<td>" . $info['version'] . "</td>
			</tr>
			<tr class='textArea'>
				<td>" . __('Author:', 'reportit')  . "</td>
				<td>" . $info['author'] . "</td>
				<td class='right'>Contact:</td>
				<td>" . $info['contact'] . "</td>
			<tr>
			<tr class='textArea'>
				<td>" . __('Description:', 'reportit') . "</td>
				<td colspan='3' width='85%'>" . nl2br($info['description']) ."</td>
			</tr>
			<tr class='textArea'>
				<td>" . __('Compatible:', 'reportit') . "</td>
				<td colspan='3'>" . $_SESSION['sess_reportit']['report_template']['analyse']['compatible'] . "</td>
			</tr>
			<tr class='textArea'>
				<td>" . __('Data Template:', 'reportit') . "</td>
				<td colspan='3'>";
				($templates) ? form_dropdown('data_template', $templates, '', '', '', '', '') : print __('No Compatible Templates Found', 'reportit');

			print '</tr>';

			print "<tr>
				<td class='saveRow' colspan='4'>
					<input type='hidden' name='action' value='template_import'>
					$save_html
				</td>
			</tr>";

			html_end_box();

			bottom_footer();
		} else {
			header('Location: templates.php?header=false&action=template_upload_wizard');
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
			$measurand                 = $template_measurands['measurand'];
			$measurand['id']           = 0;
			$measurand['template_id']  = $template_id;
			$measurand['calc_formula'] = str_replace($old,$new, $measurand['calc_formula']);

			sql_save($measurand, 'reportit_measurands');
		} else {
			$template_measurands = $template_measurands['measurand'];

			foreach($template_measurands as $measurand) {
				$measurand['id']           = 0;
				$measurand['template_id']  = $template_id;
				$measurand['calc_formula'] = str_replace($old,$new, $measurand['calc_formula']);

				sql_save($measurand, 'reportit_measurands');
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

			sql_save($ds_item, 'reportit_data_source_items', array('id', 'template_id'), false);
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

				sql_save($ds_item, 'reportit_data_source_items', array('id', 'template_id'), false);
			}
		}
	}

	/* destroy the template data saved in current session */
	unset($_SESSION['sess_reportit']['report_template']);

	header('Location: templates.php');
}

function template_filter() {
	global $item_rows;

	html_start_box(__('Report Templates', 'reportit'), '100%', '', '3', 'center', 'templates.php?action=template_new');
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
	global  $config, $template_actions, $link_array, $desc_array, $consolidation_functions, $list_of_data_templates, $order_array;

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

	validate_store_request_vars($filters, 'sess_cc_templates');
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
		'description' => array('display' => __('Name', 'reportit'),             'align' => 'left', 'sort' => 'ASC'),
		'nosort'      => array('display' => __('Data Template', 'reportit'),    'align' => 'left'),
		'enabled'     => array('display' => __('Published', 'reporit'),         'align' => 'left'),
		'nosort2'     => array('display' => __('Locked', 'reportit'),           'align' => 'left'),
		'nosort3'     => array('display' => __('Measurands', 'reportit'),       'align' => 'left', 'sort' => 'ASC'),
		'nosort4'     => array('display' => __('Variables', 'reportit'),        'align' => 'left', 'sort' => 'ASC'),
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
			form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('templates.php?action=template_edit&id=' . $template['id']) . '">' . $template['description']  . '</a>', $template['id'], 'left');

			if (isset($list_of_data_templates[$template['data_template_id']])) {
				form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars(URL_PATH . 'data_templates.php?action=template_edit&id=' . $template['data_template_id']) . '">' . $list_of_data_templates[$template['data_template_id']] . '</a>', $template['id']);
			} else {
				form_selectable_cell("<span class='textError'>" . __('Data template not available', 'reportit') . '</span>', $template['id']);
			}

			form_selectable_cell( ($template['enabled']) ? __('yes', 'reportit') : __('no', 'reportit'), $template['id']);
			form_selectable_cell( ($template['locked']) ? '<i class="fa fa-lock" ria-hidden="true"></i>' : '<i class="fa fa-unlock" ria-hidden="true"></i>', $template['id']);

			if ($template['measurands'] != NULL) {
				form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('measurands.php?id=' . $template['id']) . '"><i class="fa fa-wrench" aria-hidden="true"></i> (' . $template['measurands'] . ')</a>', $template['id']);
			} else {
				form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('measurands.php?action=measurand_edit&template_id=' . $template['id']) . '"><i class="fa fa-plus" aria-hidden="true"></i></a>', $template['id']);
			}

			if ($template['variables'] != NULL) {
				form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('variables.php?id=' . $template['id']) . '"><i class="fa fa-wrench" aria-hidden="true"></i> (' . $template['variables'] . ')</a>', $template['id']);
			} else {
				form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('variables.php?action=variable_edit&template_id=' . $template['id']) . '"><i class="fa fa-plus" aria-hidden="true"></i></a>', $template['id']);
			}

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

	draw_actions_dropdown($template_actions);
	form_end();
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
	$template_data['enabled']          = isset_request_var('template_enabled') ? 'on' : '';
	$template_data['locked']           = isset_request_var('template_locked') ? 'on' : '';
	$template_data['export_folder']    = isset_request_var('template_export_folder') ? get_request_var('template_export_folder') : '';

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
			$ds_items[$ds_id]['data_source_alias'] = trim(get_request_var($ds_alias));
		}
	}

	if (!$used_data_sources) {
		raise_message('reportit_templates__1');
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
			raise_message('reportit_templates__2');
		}
	}

	/* check if we can unlock this template. */
	if ($template_data['locked'] == 0) {
		if (stat_autolock_template($template_data['id'])) {
			raise_message('reportit_templates__3');
		}
	}

	if (!is_error_message()) {
		/* save template data */
		$template_data['id'] = sql_save($template_data, 'reportit_templates');

		/* update template id for data source items if necessary */
		if (get_request_var('id') == 0) {
			foreach($ds_items as $key => $ds_item) {
				$ds_items[$key]['template_id']=$template_data['id'];
			}
		}

		/* remove all data source items which are no longer in use */
		if ($unused_data_sources) {
			db_execute_prepared("DELETE FROM reportit_data_source_items
				WHERE template_id = ?
				AND id IN ($unused_data_sources)",
				array($template_data['id']));
		}

		/* save the data source items */
		foreach($ds_items as $ds_item) {
			sql_save($ds_item, 'reportit_data_source_items', array('id', 'template_id'), false);
		}

		/* return to list view if it was an existing report template */
		if ($template_data['id'] != 0) {
			raise_message(1);
		}else {
			raise_message(2);
		}
	}
	header('Location: templates.php?header=false&action=template_edit&id=' . $template_data['id']);
}

function template_edit() {
	global $consolidation_functions, $list_of_data_templates, $fields_template_edit;

	/* ================= input validation ================= */
	$id = get_filter_request_var('id', FILTER_VALIDATE_INT, array('default'=>0) );
	/* ==================================================== */

	session_custom_error_display();

	if ($id) {
		$template_data = db_fetch_row('SELECT *	FROM reportit_templates	WHERE id = ' . $id);
		$header_label = __('Template Configuration [edit: %s]',$template_data['description'], 'reportit');
	} else {
		$template_data['id'] = 0;
		$header_label = __('Template Configuration [new]', 'reportit');
	}

	if (isset_request_var('data_template')) {
		if (!isset($_SESSION['reportit_tWizard']['data_template']))
		$_SESSION['reportit_tWizard']['data_template'] = get_filter_request_var('data_template');
	}

	if (!isset($template_data['data_template_id'])) {
		$template_data['data_template_id'] = $_SESSION['reportit_tWizard']['data_template'];
	}

	$template_data['data_template_name'] =  $list_of_data_templates[$template_data['data_template_id']];

	if (read_config_option('reportit_auto_export')) {
		$fields_template_edit['template_export_folder']['method'] = 'hidden';
	}

	/* generate input fields for data source aliases */
	$data_source_items = html_template_ds_alias($id, $template_data['data_template_id']);
	$form_array = array_merge($fields_template_edit, $data_source_items);

	/* built 'create links' */
	$links[] = array('href' => 'variables.php?action=variable_edit&template_id=' . $id, 'text' => __('Create a new variable', 'reportit'));
	$links[] = array('href' => 'cc_measurands.php?action=measurand_edit&template_id=' . $id, 'text' => __('Create a new measurand', 'reportit'));
	html_blue_link($links, $id);

	form_start('templates.php');

	html_start_box($header_label, '100%', '', '2', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($form_array, $template_data)
		)
	);

	html_end_box();

	form_save_button('templates.php');
}


function form_actions() {
	global $template_actions, $config;

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
				$template_data['description'] = str_replace(__('<template_title>'), $template_data['description'], get_request_var('template_addition'));
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

						db_execute_prepared('UPDATE reportit_variables
							SET abbreviation = ?
							WHERE id = ?' ,
							array($abbr, $new_id));
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
						$measurand['id']           = 0;
						$measurand['template_id']  = $template_id;
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
				FROM reportit_templates
				WHERE id = ?',
				array($id));

			$template_identifier = $template_description . " [<a href='./templates.php?action=template_edit&id=$id'>$id</a>]";
			$ds_list[$template_identifier] = '';

			//Fetch all descriptions of reports attached to this template
			$template_reports = db_fetch_assoc_prepared('SELECT id, description
				FROM reportit_reports
				WHERE template_id = ?',
				array($id));

			foreach ($template_reports as $key => $value) {
				$ds_list[$template_identifier][] = "[<a href='./cc_reports.php?action=report_edit&id={$template_reports[$key]['id']}'>{$template_reports[$key]['id']}</a>] " . $template_reports[$key]['description'];
			}
		}
	}

	top_header();

	form_start('templates.php');

	html_start_box($template_actions{get_request_var('drp_action')}, '60%', '', '3', 'center', '');

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
