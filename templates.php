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
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

chdir(__DIR__ . '/../../');
require('include/auth.php');

if (!defined('REPORTIT_BASE_PATH')) {
	include_once(__DIR__ . '/setup.php');
	reportit_define_constants();
}

include_once(REPORTIT_BASE_PATH . '/lib/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/include/global_forms.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_templates.php');

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
	global $config, $list_of_data_templates, $known_data_templates, $fields_template_export;

	switch ($action) {
	case 'new':
		top_header();
		if (isset($_SESSION['reportit_tWizard'])) unset($_SESSION['reportit_tWizard']);

		form_start('templates.php','chk');

		html_start_box(__('New Report Template', 'reportit'), '60%', '', '3', 'left', '');

		if (cacti_sizeof($list_of_data_templates) == 0) {
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
					<p>" . __('Choose a Data Template this Report Template should depend on.  Unused Data Templates are hidden.', 'reportit') . "</p><p>";
			form_dropdown('data_template', $list_of_data_templates, '', '', '', '', '');
			print "</p></td>
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
	case 'upload':
		top_header();

		session_custom_error_display();

		if (cacti_version_compare('1.2.0', CACTI_VERSION, '>')) {
			print "<form id='reportit_upload' name='reportit_upload' action='templates.php' autocomplete='off' method='post'  enctype='multipart/form-data'>\n";
		} else {
			form_start('templates.php', 'reportit_upload', true);
		}

		html_start_box(__('Import Report Template', 'reportit'), '60%', '', '3', 'center', '');

		print "<tr>
			<td class='textArea'>
				<p>" . __('Select the XML file that contains your Report Template.', 'reportit') . "</p>
				<p><input type='file' name='file' id='file' size='35' maxlength='50000' accept='xml'></p>
			</td>
		</tr>
		<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='template_import_wizard'>
				<input type='submit' value='" . __esc('Import', 'reportit') . "' title='" . __esc('Import Report Templates', 'reportit') . "' class='		<input type='submit' value='" . __esc('Import', 'reportit') . "' title='" . __esc('Import Report Templates', 'reportit') . "' class='ui-button ui-corner-all ui-widget ui-state-active'
			</td>
		</tr>";

		html_end_box();

		bottom_footer();

		break;
	case 'import':
		/* clean up user session */
		if (isset($_SESSION['sess_reportit']['report_templates'])) unset($_SESSION['sess_reportit']['report_templates']);

		if (validate_uploaded_templates() == true) {
			top_header();

			$data      = $_SESSION['sess_reportit']['report_templates'];
			$xmldata   = simplexml_load_string($data);

			$header_array = array(
				'name'          => array('display' => __('Name', 'reportit')),
				'compatible'    => array('display' => __('Compatible', 'reportit')),
				'version'       => array('display' => __('Version', 'reportit')),
				'author'        => array('display' => __('Author', 'reportit')),
				'data_template' => array('display' => __('Data Template', 'reportit')),
				'description'   => array('display' => __('Description', 'reportit')),
			);

			form_start('templates.php?action=template_import_wizard');
			html_start_box(__('Summary', 'reportit'), '90%', '', '2', 'center', '');
			html_header($header_array);

			$compatible = false;
			$report_count = 0;
			foreach ($xmldata as $report_template) {
				$info = $report_template->settings;
				if ($report_template->compatible) {
					$compatible = true;
				}

				print "
				<tr class='textArea'>
					<td>$info->name</td>
					<td>" . ($report_template->compatible?'Yes':'No') . "</td>
					<td>$info->version</td>
					<td>$info->author</td>
					<td>";

				$data_templates = $report_template->data_templates;
				if (count($data_templates->children()) == 1) {
					print "<input type='hidden' name='tds$report_count' id='tds$report_count' value='" .
						$report_template->data_templates->data_template->id .
						"' />";
					foreach ($report_template->data_templates->children() as $data_template) {
						print $data_template->name;
						/*
						print "$data_template->name (";
						$ds=0;
						foreach ($report_template->data_source_items[0] as $data_source) {
							print ($ds?', ': '') . $data_source->data_source_name;
							$ds++;
						}
						print ")";
						*/
					}
				} else {
					$templates_array = xml_to_array($report_template->data_templates, true);
					$templates = array();
					foreach ($templates_array as $template_item) {
						$templates[$template_item['id']] = $template_item['name'];
					}

					if ($templates) {
						form_dropdown('tds' . $report_count, $templates, '', '', '', '', '');
					} else {
						print __('No Compatible Templates Found', 'reportit');
						$compatible = false;
					}
				}
				print "</td>
					<td>$info->description</td>
					</tr>";
				$report_count++;
			}

			$save_html = ($compatible)
				? "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Import', 'reportit') . "' title='" . __esc('Import Report Template', 'reportit') . "'>"
				: "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";

			print "<tr>
				<td class='saveRow' colspan='6'>
					<input type='hidden' name='action' value='template_import'>
					$save_html
				</td>
			</tr>";

			html_end_box();
			form_end();

			bottom_footer();
		} else {
			header('Location: templates.php?action=template_upload_wizard');
		}

		bottom_footer();

		break;
	}
}

function template_export() {
	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));

		if ($selected_items != false) {
			$output = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<report_templates>' . PHP_EOL;
			foreach ($selected_items as $id) {
				if ($id > 0) {
					/* collect all additional information */
					$id_output = export_report_template($id, 1);
					if ($id_output != false) {
						$output .= $id_output;
					}
				}
			}

			$output .= "</report_templates>\n";
			header('Content-type: application/xml');
			header('Content-Disposition: attachment; filename=reportit_templates_export_'.date('Ymd_His').'.xml');
			print $output;
		}
	}
	exit();
}

function template_import() {
	header('Location: templates.php?action=template_upload_wizard');

	/* ================= input validation ================= */
	get_filter_request_var('data_template');
	/* ==================================================== */

	if (!isset($_SESSION['sess_reportit']['report_templates'])) {
		header('Location: templates.php?action=template_upload_wizard');
	}

	$xml_string = $_SESSION['sess_reportit']['report_templates'];
	$xml_data   = simplexml_load_string($xml_string);

	$report_count = 0;
	foreach ($xml_data as $report_template) {
		import_template($report_template, get_request_var('tds' . $report_count));
		$report_count++;
	}

	/* destroy the template data saved in current session */
	unset($_SESSION['sess_reportit']['report_templates']);

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
							if (cacti_sizeof($item_rows)) {
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
	global  $config, $template_actions, $link_array, $desc_array, $consolidation_functions, $known_data_templates, $list_of_data_templates, $order_array;

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

	$template_list = db_fetch_assoc("SELECT a.*, b.measurands, c.variables, d.reports
		FROM plugin_reportit_templates AS a
		LEFT JOIN (SELECT template_id, COUNT(*) AS measurands FROM `plugin_reportit_measurands` GROUP BY template_id) AS b
		ON a.id = b.template_id
		LEFT JOIN (SELECT template_id, COUNT(*) AS variables FROM `plugin_reportit_variables` GROUP BY template_id) AS c
		ON a.id = c.template_id
		LEFT JOIN (SELECT template_id, COUNT(*) AS reports FROM `plugin_reportit_reports` GROUP BY template_id) AS d
		ON a.id = d.template_id
		$sql_where
		$sql_order
		$sql_limit");

	$display_text = array(
		'id'          => array('display' => __('Id', 'reportit'),               'align' => 'left', 'sort' => 'ASC', 'tip' => __('The internal identifier of this Report Template.', 'reportit')),
		'name'        => array('display' => __('Name', 'reportit'),             'align' => 'left', 'sort' => 'ASC', 'tip' => __('The name of this Report Template.', 'reportit')),
		'author'      => array('display' => __('Author', 'reportit'),           'align' => 'left', 'sort' => 'ASC', 'tip' => __('The Author of this Report Template.', 'reportit')),
		'version'     => array('display' => __('Version', 'reportit'),          'align' => 'left', 'sort' => 'ASC', 'tip' => __('The version of this Report Template.', 'reportit')),
		'nosort'      => array('display' => __('Data Template', 'reportit'),    'align' => 'left'),
		'enabled'     => array('display' => __('Published', 'reporit'),         'align' => 'left'),
		'nosort2'     => array('display' => __('Locked', 'reportit'),           'align' => 'left'),
		'nosort3'     => array('display' => __('Measurands', 'reportit'),       'align' => 'left', 'sort' => 'ASC'),
		'nosort4'     => array('display' => __('Variables', 'reportit'),        'align' => 'left', 'sort' => 'ASC'),
		'reports'     => array('display' => __('Reports', 'reportit'),          'align' => 'left', 'sort' => 'ASC', 'tip' => __('The total number of reports using this report template.', 'reportit')),
	);

	$nav = html_nav_bar('templates.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Templates', 'reportit'), 'page', 'main');

	template_filter();

	print $nav;

	form_start('templates.php');
	html_start_box('', '100%', '', '2', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($template_list)) {
		foreach($template_list as $template) {
			form_alternate_row('line' . $template['id'], true);
			form_selectable_cell($template['id'], $template['id']);
			form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('templates.php?action=template_edit&id=' . $template['id']) . '" title="' . htmlspecialchars($template['description']) . '">' . filter_value($template['name'], get_request_var('filter')) . '</a>', $template['id'], 'left');
			form_selectable_cell(filter_value($template['author'], get_request_var('filter')), $template['id'], 'left');
			form_selectable_cell(filter_value($template['version'], get_request_var('filter')), $template['id'], 'left');

			if (isset($list_of_data_templates[$template['data_template_id']])) {
				form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars(URL_PATH . 'data_templates.php?action=template_edit&id=' . $template['data_template_id']) . '">' . $list_of_data_templates[$template['data_template_id']] . '</a>', $template['id']);
			} elseif (isset($known_data_templates[$template['data_template_id']])) {
				form_selectable_cell("<span class='textWarning'>" . __('No matching data sources', 'reportit') . '</span>', $template['id']);
			} else {
				form_selectable_cell("<span class='textError'>" . __('Data template not available', 'reportit') . '</span>', $template['id']);
			}

			form_selectable_cell(html_check_icon($template['enabled']), $template['id']);
			form_selectable_cell(html_lock_icon($template['locked']), $template['id']);

			$link = $template['measurands'] != NULL
				? '<a class="linkEditMain" href="' . htmlspecialchars('measurands.php?id=' . $template['id']) . '">'
				: '<a class="linkEditMain" href="' . htmlspecialchars('measurands.php?action=measurand_edit&template_id=' . $template['id']) . '">';

			form_selectable_cell($link . html_sources_icon($template['measurands'], __('Edit measurands'), __('Add measurands')) . '</a>', $template['id']);

			$link = $template['variables'] != NULL
				? '<a class="linkEditMain" href="' . htmlspecialchars('variables.php?id=' . $template['id']) . '">'
				: '<a class="linkEditMain" href="' . htmlspecialchars('variables.php?action=measurand_edit&template_id=' . $template['id']) . '">';

			form_selectable_cell($link . html_sources_icon($template['variables'], __('Edit variables'), __('Add variables')) . '</a>', $template['id']);

			form_selectable_cell( $template['reports'] ? $template['reports'] : '-', $template['id']);
			form_checkbox_cell($template['description'], $template['id']);
			form_end_row();
		}
	} else {
		print "<tr><td colspan='7'><em>" . __('No templates', 'reportit') . "</em></td></tr>";
	}

	html_end_box(true);

	if (cacti_sizeof($template_list)) {
		print $nav;
	}

	draw_actions_dropdown($template_actions);
	form_end();
}

function form_save() {
	global $list_of_data_templates;

	$ds_items = array();
	$used_data_sources = '';
	$unused_data_sources = false;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('template_data_template'));
	form_input_validate(get_request_var('template_name'), 'template_name', '', false, 3);
	form_input_validate(get_request_var('template_author'), 'template_author', '', false, 3);
	form_input_validate(get_request_var('template_version'), 'template_version', '', false, 3);
	form_input_validate(get_request_var('template_description'), 'template_description', '', false, 3);
	form_input_validate(get_request_var('template_filter'), 'template_filter', '', true, 3);
	#form_input_validate(get_request_var('data_template_id'));
	/* ==================================================== */

	$template_data = array();
	$template_data['id']               = get_request_var('id');
	$template_data['name']             = get_request_var('template_name');
	$template_data['description']      = get_request_var('template_description');
	$template_data['author']           = get_request_var('template_author');
	$template_data['version']          = get_request_var('template_version');
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
	if ($template_data['id'] != 0 & $unused_data_sources !== false) {
		/* get the list of unused data sources */
		$sql = "SELECT data_source_name
				FROM data_template_rrd
				WHERE local_data_id = 0
				AND data_template_id = {$template_data['data_template_id']}
				AND id NOT IN (". substr($used_data_sources,0,-1) . ")";
		$pattern = db_custom_fetch_flat_string($sql, '|');

		$sql = "SELECT `abbreviation`
				FROM plugin_reportit_measurands
				WHERE `template_id` = {$template_data['id']}
				AND `calc_formula` REGEXP '($pattern)'";
		$measurands = db_custom_fetch_flat_string($sql, ', ');

		if ($measurands !== false) {
			raise_message('reportit_templates__2');
		}
	}

	/* check if we can lock this template. */
	if ($template_data['locked'] == '') {
		if (stat_autolock_template($template_data['id'])) {
			raise_message('reportit_templates__3');
		}
	}

	if (!is_error_message()) {
		/* save template data */

		$template_data['id'] = sql_save($template_data, 'plugin_reportit_templates');

		/* update template id for data source items if necessary */
		if (get_request_var('id') == 0) {
			foreach($ds_items as $key => $ds_item) {
				$ds_items[$key]['template_id']=$template_data['id'];
			}
		}

		/* remove all data source items which are no longer in use */
		if ($unused_data_sources) {
			db_execute_prepared("DELETE FROM plugin_reportit_data_source_items
				WHERE template_id = ?
				AND id IN ($unused_data_sources)",
				array($template_data['id']));
		}

		/* save the data source items */
		foreach($ds_items as $ds_item) {
			sql_save($ds_item, 'plugin_reportit_data_source_items', array('id', 'template_id'), false);
		}

		/* return to list view if it was an existing report template */
		if ($template_data['id'] != 0) {
			raise_message(1);
		} else {
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
		$template_data = db_fetch_row('SELECT *	FROM plugin_reportit_templates	WHERE id = ' . $id);
		$header_label = __('Template Configuration [edit: %s]',$template_data['description'], 'reportit');
	} else {
		$template_data['id'] = 0;
		$header_label = __('Template Configuration [new]', 'reportit');

		$fields_template_edit['template_locked']['value'] = 'on';
		$fields_template_edit['template_locked']['method'] = 'hidden';
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
	$links[] = array('href' => 'measurands.php?action=measurand_edit&template_id=' . $id, 'text' => __('Create a new measurand', 'reportit'));
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
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if (get_request_var('drp_action') == '1') { // DELETE REPORT TEMPLATE
			$template_datas = db_fetch_assoc('SELECT id FROM plugin_reportit_templates WHERE ' . array_to_sql_or($selected_items, 'id'));

			if (cacti_sizeof($template_datas) > 0) {
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

				if (cacti_sizeof($template_variables)) {
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

				if (cacti_sizeof($template_measurands)) {
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

				if (cacti_sizeof($template_ds_items)) {
					foreach($template_ds_items as $data_source_item) {
						$data_source_item['template_id'] = $template_id;

						sql_save($data_source_item, 'plugin_reportit_data_source_items', array('id', 'template_id'), false);
					}
				}
			}
		} elseif (get_request_var('drp_action') == '3') { //DUPLICATE REPORT TEMPLATE
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				$template_data = db_fetch_row('SELECT * FROM plugin_reportit_templates WHERE id = ' . $selected_items[$i]);
				if ($template_data === false || sizeof($template_data) == 0) {
					raise_message(2);
					header('Location: templates.php?header=false');
					exit;
				}
			}

			top_header();
			print '<div id="downloading"><p>Please wait ... downloading ...</p></div>
<script text="text/javascript">
	function DownloadStart(url) {
		document.getElementById("download_iframe").onload = function() {
			document.location = "templates.php";
		}
		document.getElementById("download_iframe").src = url;
		setTimeout(function() {
			document.location = "templates.php";
		}, 10000);
	}

	$(function() {
		DownloadStart(\'templates.php?action=template_export&selected_items=' . get_nfilter_request_var('selected_items') . '\');
	});
</script>
<iframe id="download_iframe" style="display:none;"></iframe>
';
			bottom_footer();
			exit;
		}

		header('Location: templates.php?header=false');
		exit;
	}

	//Set preconditions
	$ds_list = array(); $i = 0;

	foreach ($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			//Fetch template id
			$id = substr($key, 4);
			$template_ids[] = $id;

			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			//Fetch Template description
			$template = db_fetch_row_prepared('SELECT name, description
				FROM plugin_reportit_templates
				WHERE id = ?',
				array($id));

			if ($template === false) {
				$template = array('name' => 'Unknown Template', 'description' => '');
			}

			if (empty($template['name'])) {
				$template['name'] = $template['description'];
			}

			$template_identifier = "<a href='templates.php?action=template_edit&id={$id}'>{$template['name']}</a>";
			$ds_list[$template_identifier] = array();

			//Fetch all descriptions of reports attached to this template
			$template_reports = db_fetch_assoc_prepared('SELECT id, name, description
				FROM plugin_reportit_reports
				WHERE template_id = ?',
				array($id));

			foreach ($template_reports as $key => $value) {
				$ds_list[$template_identifier][] = "<a href='./reports.php?action=report_edit&id={$template_reports[$key]['id']}'>{$template_reports[$key]['name']}</a> (" . $template_reports[$key]['description'] . ')';
			}
		}
	}

	top_header();

	form_start('templates.php');

	html_start_box($template_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

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

			if (cacti_sizeof($ds_list)) {
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
	} elseif (get_request_var('drp_action') == '3') {
		/* export report template(s) */
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Export the following Report Templates', 'reportit') . '</p><ul>';

		if (is_array($ds_list)) {
			foreach($ds_list as $key => $value) {
				print '<li>' . $key . '</li>';
			}
		}

		print '</ul></td>
		</tr>';
	}

	$save_focus = ' class="ui-button ui-corner-all ui-widget ui-state-active"';
	$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";
	if ($ds_list === false || !is_array($ds_list) || empty($ds_list)) {
		print "<tr>
			<td class='textArea'>
				<span class='textError'>" . __('You must select at least one Report Template.', 'reportit') . '</span>
			</td>
		</tr>';

	} elseif (get_request_var('drp_action') == '1') {
		$save_html .= "&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Delete Report Templates', 'reportit') . "'$save_focus>";
	} elseif (get_request_var('drp_action') == '2') {
		$save_html = "&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Duplicate Report Templates', 'reportit') . "'$save_focus>";
	} elseif (get_request_var('drp_action') == '3') {
		$save_html = "&nbsp;<input type='submit' value='" . __esc('Export', 'reportit') . "' title='" . __esc('Export Report Templates', 'reportit') . "'$save_focus";
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
