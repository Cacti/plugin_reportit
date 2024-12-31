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

include_once(REPORTIT_BASE_PATH . '/lib/const_templates.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_measurands.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_variables.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_calculate.php');
include_once(REPORTIT_BASE_PATH . '/include/global_forms.php');

$variable_actions = array(
	1 => __('Delete', 'reportit')
);

$var_types = array(
	1 => __('Dropdown', 'reportit'),
	2 => __('Input field', 'reportit')
);

$link_array = array(
	'name',
	'abbreviation',
	'max_value',
	'min_value',
	'default_value',
	'input_type'
);

$list_of_modes = array(
	'ASC',
	'DESC'
);

$measurand_actions  = array(
	2 => __('Delete', 'reportit')
);

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
	case 'variable_edit':
		top_header();
		variable_edit();
		bottom_footer();

		break;
	case 'measurand_edit':
		top_header();
		measurand_edit();
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
		templates();
		bottom_footer();

		break;
}

function template_tabs($id) {
	global $config;

	/* present a tabbed interface */
	$tabs = array(
		'general'    => __('General', 'reportit'),
		'variables'  => __('Variables', 'reportit'),
		'measurands' => __('Metrics', 'reportit')
	);

	$tabs = api_plugin_hook_function('reportit_template_tabs', $tabs);

	get_filter_request_var('tab', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z]+)$/')));

	load_current_session_value('tab', 'sess_reportit_template_tab', 'general');
	$current_tab = get_request_var('tab');

	if (empty($id) && $current_tab == 'general') {
		unset($tabs['variables']);
		unset($tabs['measurands']);
	}

	/* draw the tabs */
	print "<div class='tabs'><nav><ul>";

	if (cacti_sizeof($tabs)) {
		foreach ($tabs as $tab => $name) {
			print "<li><a class='tab" . (($tab == $current_tab) ? " selected'" : "'") .
				" href='" . html_escape($config['url_path'] .
				'plugins/reportit/templates.php' .
				'?id=' . $id .
				'&action=template_edit' .
				'&tab=' . $tab) .
				"'>" . html_escape($name) . '</a></li>';
		}
	}

	print '</ul></nav></div>';
}

function template_wizard($action) {
	global $config, $list_of_data_templates, $known_data_templates, $fields_template_export;

	switch ($action) {
		case 'new':
			top_header();

			if (isset($_SESSION['reportit_tWizard'])) {
				unset($_SESSION['reportit_tWizard']);
			}

			form_start('templates.php', 'chk');

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
				print "<form id='reportit_upload' name='reportit_upload' action='templates.php' autocomplete='off' method='post'  enctype='multipart/form-data'>";
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
			if (isset($_SESSION['sess_reportit']['report_templates'])) {
				unset($_SESSION['sess_reportit']['report_templates']);
			}

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

				html_start_box(__('Summary', 'reportit'), '100%', '', '3', 'center', '');

				html_header($header_array);

				$compatible = false;
				$report_count = 0;
				foreach ($xmldata as $report_template) {
					$info = $report_template->settings;

					if ($report_template->compatible) {
						$compatible = true;
					}

					print "<tr class='textArea'>
						<td>$info->name</td>
						<td>" . ($report_template->compatible ? __('Yes', 'reportit'):__('No', 'reportit')) . "</td>
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
			$output = '<report_templates>' . PHP_EOL;
			foreach ($selected_items as $id) {
				if ($id > 0) {
					/* collect all additional information */
					$id_output = export_report_template($id, 1);

					if ($id_output != false) {
						$output .= $id_output;
					}
				}
			}

			$output .= '</report_templates>' . PHP_EOL;

			header('Content-type: application/xml');
			header('Content-Disposition: attachment; filename=reportit_templates_export_' . date('Ymd_His') . '.xml');
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
							<span>
								<input id='refresh' type='submit' value='<?php print __esc_x('Button: use filter settings', 'Go', 'reportit');?>'>
								<input id='clear' type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear', 'reportit');?>'>
								<input id='import' type='button' value='<?php print __esc_x('Button: import button', 'Import', 'reportit');?>'>
							</span>
						</td>
					</tr>
				</table>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL = 'templates.php?filter='+escape($('#filter').val())+'&rows='+$('#rows').val();
				loadUrl({ url: strURL });
			}

			function clearFilter() {
				strURL = 'templates.php?clear=1';
				loadUrl({ url: strURL });
			}

			$(function() {
				$('#refresh').click(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});

				$('#import').click(function() {
					strURL = 'templates.php?action=template_upload_wizard';
					loadUrl({ url: strURL });
				});

				$('#export').click(function() {
					strURL = 'templates.php?action=template_export_wizard';
					loadUrl({ url: strURL });
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

function templates() {
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

	validate_store_request_vars($filters, 'sess_reportit_templates');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	if (get_request_var('filter') != '') {
		$sql_where    = 'WHERE description LIKE ? OR name LIKE ?';
		$sql_params[] = '%' . get_request_var('filter') . '%';
		$sql_params[] = '%' . get_request_var('filter') . '%';
	} else {
		$sql_where  = '';
		$sql_params = array();
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(plugin_reportit_templates.id)
		FROM plugin_reportit_templates
		$sql_where",
		$sql_params);

	$template_list = db_fetch_assoc("SELECT a.*, b.measurands, c.variables, d.reports
		FROM plugin_reportit_templates AS a
		LEFT JOIN (
			SELECT template_id,
			COUNT(*) AS measurands
			FROM `plugin_reportit_measurands`
			GROUP BY template_id
		) AS b
		ON a.id = b.template_id
		LEFT JOIN (
			SELECT template_id, COUNT(*) AS variables
			FROM `plugin_reportit_variables`
			GROUP BY template_id
		) AS c
		ON a.id = c.template_id
		LEFT JOIN (
			SELECT template_id, COUNT(*) AS reports
			FROM `plugin_reportit_reports`
			GROUP BY template_id
		) AS d
		ON a.id = d.template_id
		$sql_where
		$sql_order
		$sql_limit");

	$display_text = array(
		'name' => array(
			'display' => __('Name', 'reportit'),
			'align'   => 'left',
			'sort'    => 'ASC',
			'tip'     => __('The name of this Report Template.', 'reportit')
		),
		'id' => array(
			'display' => __('ID', 'reportit'),
			'align'   => 'left',
			'sort'    => 'ASC',
			'tip'     => __('The internal identifier of this Report Template.', 'reportit')
		),
		'author' => array(
			'display' => __('Author', 'reportit'),
			'align'   => 'left',
			'sort'    => 'ASC',
			'tip'     => __('The Author of this Report Template.', 'reportit')
		),
		'nosort' => array(
			'display' => __('Data Template', 'reportit'),
			'align'   => 'left'
		),
		'version' => array(
			'display' => __('Version', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC',
			'tip'     => __('The version of this Report Template.', 'reportit')
		),
		'enabled' => array(
			'display' => __('Published', 'reporit'),
			'align'   => 'right'
		),
		'nosort2' => array(
			'display' => __('Locked', 'reportit'),
			'align'   => 'right'
		),
		'nosort3' => array(
			'display' => __('Metrics', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC'
		),
		'nosort4' => array(
			'display' => __('Variables', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC'
		),
		'reports' => array(
			'display' => __('Reports', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC',
			'tip'     => __('The total number of reports using this report template.', 'reportit')
		),
	);

	$nav = html_nav_bar('templates.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Templates', 'reportit'), 'page', 'main');

	template_filter();

	print $nav;

	form_start('templates.php');

	form_hidden_box('tab', 'general', '');

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($template_list)) {
		foreach($template_list as $template) {
			$link = 'templates.php?action=template_edit&tab=general&id=' . $template['id'];

			form_alternate_row('line' . $template['id'], true);

			form_selectable_cell(filter_value($template['name'], get_request_var('filter'), $link), $template['id'], 'left');
			form_selectable_cell($template['id'], $template['id']);

			form_selectable_cell(filter_value($template['author'], get_request_var('filter')), $template['id'], 'left');

			if (isset($list_of_data_templates[$template['data_template_id']])) {
				$link = URL_PATH . 'data_templates.php?action=template_edit&id=' . $template['data_template_id'];

				form_selectable_cell(filter_value($list_of_data_templates[$template['data_template_id']], '', $link), $template['id']);
			} elseif (isset($known_data_templates[$template['data_template_id']])) {
				form_selectable_cell(__('No matching data sources', 'reportit'), $template['id'], '', 'textWarning');
			} else {
				form_selectable_cell(__('Data template not available', 'reportit'), $template['id'], '', 'textError');
			}

			form_selectable_cell(filter_value($template['version'], get_request_var('filter')), $template['id'], '', 'right');
			form_selectable_cell(html_check_icon($template['enabled']), $template['id'], '', 'right');
			form_selectable_cell(html_lock_icon($template['locked']), $template['id'], '', 'right');

			$link = $template['measurands'] != NULL
				? '<a class="linkEditMain" href="' . html_escape('templates.php?action=template_edit&tab=measurands&id=' . $template['id']) . '">'
				: '<a class="linkEditMain" href="' . html_escape('templates.php?action=measurand_edit&tab=measurands&template_id=' . $template['id']) . '">';

			form_selectable_cell($link . html_sources_icon($template['measurands'], __('Edit measurands', 'reportit'), __('Add measurands', 'reportit')) . '</a>', $template['id'], '', 'right');

			$link = $template['variables'] != NULL
				? '<a class="linkEditMain" href="' . html_escape('templates.php?action=template_edit&tab=variables&id=' . $template['id']) . '">'
				: '<a class="linkEditMain" href="' . html_escape('templates.php?action=variable_edit&tab=variables&template_id=' . $template['id']) . '">';

			form_selectable_cell($link . html_sources_icon($template['variables'], __('Edit variables', 'reportit'), __('Add variables', 'reportit')) . '</a>', $template['id'], '', 'right');
			form_selectable_cell( $template['reports'] ? $template['reports'] : '-', $template['id'], '', 'right');

			form_checkbox_cell($template['description'], $template['id']);

			form_end_row();
		}
	} else {
		print "<tr><td colspan='" . (cacti_sizeof($display_text)+1) . "'><em>" . __('No templates', 'reportit') . "</em></td></tr>";
	}

	html_end_box(true);

	if ($total_rows > $rows) {
		print $nav;
	}

	draw_actions_dropdown($template_actions);

	form_end();
}

function form_save() {
	global $list_of_data_templates;
	global $calc_var_names, $rounding, $precision, $type_specifier;

	if (isset_request_var('save_component_template')) {
		$ds_items = array();
		$used_data_sources = '';
		$unused_data_sources = false;

		/* ================= input validation ================= */
		input_validate_input_number(get_request_var('id'));
		input_validate_input_number(get_request_var('data_template'));
		form_input_validate(get_request_var('name'), 'name', '', false, 3);
		form_input_validate(get_request_var('author'), 'author', '', false, 3);
		form_input_validate(get_request_var('version'), 'version', '', false, 3);
		form_input_validate(get_request_var('description'), 'description', '', false, 3);
		form_input_validate(get_request_var('pre_filter'), 'pre_filter', '', true, 3);
		#form_input_validate(get_request_var('data_template_id'));
		/* ==================================================== */

		$template_data = array();
		$template_data['id']               = get_request_var('id');
		$template_data['name']             = get_request_var('name');
		$template_data['description']      = get_request_var('description');
		$template_data['author']           = get_request_var('author');
		$template_data['version']          = get_request_var('version');
		$template_data['pre_filter']       = get_request_var('pre_filter');
		$template_data['data_template_id'] = get_request_var('data_template_id');
		$template_data['enabled']          = isset_request_var('enabled') ? 'on' : '';
		$template_data['locked']           = isset_request_var('locked') ? 'on' : '';
		$template_data['export_folder']    = isset_request_var('export_folder') ? get_request_var('export_folder') : '';

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
		if ($template_data['id'] != 0 && $unused_data_sources !== false) {
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

		header('Location: templates.php?action=template_edit&tab=general&id=' . $template_data['id']);
	} elseif (isset_request_var('save_component_variable')) {
		/* ================= input validation ================= */
		get_filter_request_var('id');
		get_filter_request_var('template_id');

		form_input_validate(get_request_var('name'), 'name', '^[a-zA-Z0-9[:space:]]+$', false, 3);
		form_input_validate(get_request_var('description'), 'description', '[a-zA-Z0-9\n\r]+', false, 3);
		form_input_validate(get_request_var('max_value'), 'max_value', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
		form_input_validate(get_request_var('min_value'), 'min_value', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
		form_input_validate(get_request_var('default_value'), 'default_value', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
		form_input_validate(get_request_var('input_type'), 'input_type', '^[1-2]$', false, 3);

		if (get_request_var('input_type') == 1) {
			form_input_validate(get_request_var('stepping'), 'stepping', '^[0-9]+[.]?[0-9]*$', false, 3);
		}
		/* ==================================================== */


		//Check defined variable
		if (!(get_request_var('max_value') > get_request_var('min_value'))) {
			session_custom_error_message('maximum', __('Maximum has to be greater than minimum.', 'reportit'));
		}

		if (!(get_request_var('min_value') <= get_request_var('default_value') && get_request_var('default') <= get_request_var('maximum'))) {
			session_custom_error_message('default', __('Default value is out of values range.', 'reportit'));
		}

		if (get_request_var('type') == 1) {
			if (!(get_request_var('stepping') > 0) ||
			!(get_request_var('stepping') <= (get_request_var('max_value') - get_request_var('min_value'))))
			session_custom_error_message('stepping', 'Invalid step.');
		}

		$variable_data = array();
		$variable_data['id']            = get_request_var('id');
		$variable_data['name']          = get_request_var('name');
		$variable_data['template_id']   = get_request_var('template_id');
		$variable_data['description']   = get_request_var('description');
		$variable_data['max_value']     = get_request_var('max_value');
		$variable_data['min_value']     = get_request_var('min_value');
		$variable_data['default_value'] = get_request_var('default_value');
		$variable_data['input_type']	= get_request_var('input_type');

		if (isset_request_var('stepping')) {
			$variable_data['stepping']  = get_request_var('stepping');
		}

		if (is_error_message()) {
			raise_message(4);
			header("Location: templates.php?tab=variables&action=variable_edit&id=" . get_request_var('id') . "&template_id=" . get_request_var('template_id'));

		} else {
			//Save data
			$var_id = sql_save($variable_data, 'plugin_reportit_variables');

			if (get_request_var('id') == 0) {
				db_execute_prepared("UPDATE plugin_reportit_variables
					SET abbreviation = ?
					WHERE id = ?", array("c{$var_id}v", $var_id));

				//If its a new one we've to create the entries for all the reports
				//using this template.
				create_rvars_entries($var_id, $variable_data['template_id'], $variable_data['default_value']);
			}

			raise_message(1);

			//Return to list view if it was an existing report
			header('Location: templates.php?action=template_edit&tab=variables&id=' . get_request_var('template_id'));
		}
	} elseif (isset_request_var('save_component_measurand')) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var('id'));
		input_validate_input_number(get_request_var('template_id'));
		input_validate_input_key(get_request_var('data_type'), $type_specifier);
		input_validate_input_key(get_request_var('data_precision'), $precision, true);
		input_validate_input_key(get_request_var('rounding'), array(0,1,2), true);
		form_input_validate(get_request_var('name'), 'name', '', false, 3);
		form_input_validate(get_request_var('abbreviation'), 'abbreviation', '^[a-zA-Z0-9]+$', false, 3);
		form_input_validate(get_request_var('unit'), 'unit', '^[\/\\\$a-zA-Z0-9%²³-]+$', false, 3);
		form_input_validate(get_request_var('calc_formula'), 'calc_formula', '', false, 3);
		form_input_validate(get_request_var('cf'), 'cf', '[1-4]', false, 3);
		/* ==================================================== */

		//Check if the abbreviation is in use.
		$count = db_fetch_cell_prepared('SELECT COUNT(*)
			FROM plugin_reportit_measurands
			WHERE abbreviation = ?
			AND id != ?
			AND template_id = ?',
			array(get_request_var('abbreviation'), get_request_var('id'), get_request_var('template_id')));

		if ($count != 0) {
			session_custom_error_message('abbreviation', __('Duplicate abbreviation', 'reportit'));
		}

		//Check calculation formula
		if (strlen(get_request_var('calc_formula'))) {
			$calc                 = get_request_var('calc_formula');
			$intersizes           = get_interim_results(get_request_var('id'), get_request_var('template_id'));
			$calc_var_names       = array_keys(get_possible_variables(get_request_var('template_id')));
			$data_query_variables = get_possible_data_query_variables(get_request_var('template_id'));
			$error                = validate_calc_formula($calc, $intersizes, $calc_var_names, $data_query_variables);

			if ($error != 'VALID') {
				session_custom_error_message('calc_formula', $error);
			}
		}

		//Check possible dependences with other measurands
		if (!is_error_message_field('abbreviation') && get_request_var('id') != 0) {
			$dependences = array();
			$dependencies = array();

			$new = get_request_var('abbreviation');

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

				if (cacti_sizeof($dependencies)) {
					foreach($dependences as $key => $value) {
						$value['calc_formula'] = str_replace($old, $new, $value['calc_formula']);
						$dependences[$key]     = $value;
					}
				}
			}

			//Check if interim results are used in other measurands
			if (isset_request_var('spanned')) {
				$count = db_fetch_cell_prepared("SELECT COUNT(*)
					FROM plugin_reportit_measurands
					WHERE template_id = ?
					AND id > ?
					AND calc_formula LIKE '%$old:%'",
					array(get_request_var('template_id'), get_request_var('id')))
	;

				if ($count != 0) {
					session_custom_error_message('spanned', __('Interim results are used by other measurands.', 'reportit'));
				}
			}
		}

		$measurand_data = array();
		$measurand_data['id']             = get_request_var('id');
		$measurand_data['template_id']    = get_request_var('template_id');
		$measurand_data['name']           = get_request_var('name');
		$measurand_data['abbreviation']   = strtoupper(get_request_var('abbreviation'));
		$measurand_data['calc_formula']   = get_request_var('calc_formula');
		$measurand_data['unit']           = get_request_var('unit');
		$measurand_data['visible']        = isset_request_var('visible') ? 'on' : '';
		$measurand_data['spanned']        = isset_request_var('spanned') ? 'on' : '';
		$measurand_data['rounding']       = isset_request_var('rounding') ? get_request_var('rounding'): '';
		$measurand_data['cf']             = get_request_var('cf');
		$measurand_data['data_type']      = get_request_var('data_type');
		$measurand_data['data_precision'] = isset_request_var('data_precision') ? get_request_var('data_precision') : '';

		if (is_error_message()) {
			header('Location: templates.php?action=measurand_edit&tab=measurands&id=' . get_request_var('id') . '&template_id=' . get_request_var('template_id'));
		} else {
			//Save data
			sql_save($measurand_data, 'plugin_reportit_measurands');

			//Update dependences if it's necessary
			if (isset($dependences) && sizeof($dependencies)) {
				update_formulas($dependences);
			}

			//Return to list view if it was an existing report
			header('Location: templates.php?action=template_edit&tab=measurands&id=' . get_request_var('template_id'));
			raise_message(1);
		}
	}
}

function template_edit() {
	/* ================= input validation ================= */
	$id = get_filter_request_var('id', FILTER_VALIDATE_INT, array('default'=>0) );
	/* ==================================================== */

	template_tabs($id);

	if (get_request_var('tab') == 'general') {
		templates_general($id);
	} elseif (get_request_var('tab') == 'variables') {
		variables();
	} elseif (get_request_var('tab') == 'measurands') {
		measurands();
	}
}

function templates_general($id) {
	global $consolidation_functions, $list_of_data_templates, $fields_template_edit;

	session_custom_error_display();

	if ($id) {
		$template_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_templates
			WHERE id = ?', array($id));

		$header_label = __esc('Template [ %s - %s ]', $template_data['name'], $template_data['description'], 'reportit');
	} else {
		$template_data['id'] = 0;

		$header_label = __('Template [new]', 'reportit');

		$fields_template_edit['locked']['value']  = 'on';
		$fields_template_edit['locked']['method'] = 'hidden';
	}

	if (isset_request_var('data_template')) {
		if (!isset($_SESSION['reportit_tWizard']['data_template'])) {
			$_SESSION['reportit_tWizard']['data_template'] = get_filter_request_var('data_template');
		}
	}

	if (!isset($template_data['data_template_id'])) {
		$template_data['data_template_id'] = $_SESSION['reportit_tWizard']['data_template'];
	}

	$template_data['data_template_name'] =  $list_of_data_templates[$template_data['data_template_id']];

	if (read_config_option('reportit_auto_export')) {
		$fields_template_edit['export_folder']['method'] = 'hidden';
	}

	/* generate input fields for data source aliases */
	$data_source_items = html_template_ds_alias($id, $template_data['data_template_id']);
	$form_array = array_merge($fields_template_edit, $data_source_items);

	form_start('templates.php?tab=general');

	form_hidden_box('tab', 'general', '');

	html_start_box($header_label, '100%', '', '3', 'center', '');

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
	global $template_actions, $variable_actions, $measurand_actions, $config;

	if (get_nfilter_request_var('tab') == 'general') {
		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

			if (get_request_var('drp_action') == '1') { // DELETE REPORT TEMPLATE
				$template_datas = db_fetch_assoc('SELECT id FROM plugin_reportit_templates WHERE ' . array_to_sql_or($selected_items, 'id'));

				if (cacti_sizeof($template_datas)) {
					foreach ($template_datas as $template_data) {
						db_execute_prepared('DELETE FROM plugin_reportit_templates WHERE id = ?', array($template_data['id']));
						db_execute_prepared('DELETE FROM plugin_reportit_variables WHERE template_id = ?', array($template_data['id']));
						db_execute_prepared('DELETE FROM plugin_reportit_measurands WHERE template_id = ?', array($template_data['id']));
						db_execute_prepared('DELETE FROM plugin_reportit_data_source_items WHERE template_id = ?', array($template_data['id']));

						$template_reports = db_fetch_assoc_prepared('SELECT id
							FROM plugin_reportit_reports
							WHERE template_id = ?',
							array($template_data['id']));

						if (is_array($template_reports)) {
							foreach($template_reports as $template_report) {
								db_execute_prepared('DELETE FROM plugin_reportit_reports WHERE id = ?', array($template_report['id']));
								db_execute_prepared('DELETE FROM plugin_reportit_data_items WHERE report_id = ?', array($template_report['id']));

								db_execute('DROP TABLE IF EXISTS plugin_reportit_results_' . $template_report['id']);

								db_execute_prepared('DELETE FROM plugin_reportit_rvars WHERE report_id = ?', array($template_report['id']));
								db_execute_prepared('DELETE FROM plugin_reportit_presets WHERE id = ?', array($template_report['id']));
								db_execute_prepared('DELETE FROM plugin_reportit_recipients WHERE report_id = ?', array($template_report['id']));
							}
						}
					}
				}
			} elseif (get_request_var('drp_action') == '2') { //DUPLICATE REPORT TEMPLATE
				for ($i=0;($i<count($selected_items));$i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					$template_data = db_fetch_row_prepared('SELECT *
						FROM plugin_reportit_templates
						WHERE id = ?',
						array($selected_items[$i]));

					$template_data['id'] = 0;

					$template_data['name'] = str_replace(__('<template_title>', 'reportit'), $template_data['name'], get_request_var('template_addition'));

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

					$template_data = db_fetch_row_prepared('SELECT *
						FROM plugin_reportit_templates WHERE id = ?',
						array($selected_items[$i]));

					if ($template_data === false || sizeof($template_data) == 0) {
						raise_message(2);
						header('Location: templates.php');
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

			header('Location: templates.php');
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

				$template_identifier = "<a href='templates.php?action=template_edit&tab=general&id={$id}'>{$template['name']}</a>";
				$ds_list[$template_identifier] = array();

				//Fetch all descriptions of reports attached to this template
				$template_reports = db_fetch_assoc_prepared('SELECT id, description
					FROM plugin_reportit_reports
					WHERE template_id = ?',
					array($id));

				foreach ($template_reports as $key => $value) {
					$ds_list[$template_identifier][] = "<a href='./reports.php?action=report_edit&id={$template_reports[$key]['id']}'>{$template_reports[$key]['description']}</a>";
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
				print '<p>' . __('WARNING: Every Report that belongs to these Templates will also be deleted!', 'reportit') . '</p>';

				foreach($ds_list as $key => $value) {
					print '<p>' . __('Template: %s', $key, 'reportit') . '</p>';

					if (is_array($ds_list[$key])) {
						print '<div class="itemlist"><ul>';

						foreach($ds_list[$key] as $report_name => $value) {
							print '<li>' . __('Report: %s', $value, 'reportit') . '</li>';
						}

						print '</ul></div>';
					} else {
						print '<ul>';

						print '<li>' . __('Report: <i>None</i>', 'reportit') . '</li>';

						print '</ul>';
					}
				}
			}

			print '</td></tr>';
		} elseif (get_request_var('drp_action') == '2') { // DUPLICATE REPORT TEMPLATE
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to duplicate the following report templates. You can optionally change the title of those duplicates.', 'reportit') . '</p>';

			if (is_array($ds_list)) {
				print '<p>' . __('List of selected report templates:', 'reportit') . '</p>';

				if (cacti_sizeof($ds_list)) {
					print '<div class="itemlist"><ul>';

					foreach($ds_list as $key => $value) {
						print '<li>' . $key . '</li>';
					}

					print '</ul></div>';
				}
			}

			print '<p>' . __('Title:', 'reportit') . '<br>';

			form_text_box('template_addition', __('<template_title> (1)', 'reportit'), '', '255', '30', 'text');

			print '</p>
				</td>
			</tr>';
		} elseif (get_request_var('drp_action') == '3') {
			/* export report template(s) */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Export the following Report Templates', 'reportit') . '</p>';

			print '<div class="itemlist"><ul>';

			if (is_array($ds_list)) {
				foreach($ds_list as $key => $value) {
					print '<li>' . $key . '</li>';
				}
			}

			print '</ul></div></td></tr>';
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
				<input type='hidden' name='tab' value='general'>
				<input type='hidden' name='selected_items' value='" . (isset($template_ids) ? serialize($template_ids) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();

		form_end();

		bottom_footer();
	} elseif (get_request_var('tab') == 'variables') {
		$error = false;

		// ================= input validation =================
		get_filter_request_var('id');
		// ====================================================

		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

			if (get_request_var('drp_action') == '1') { // delete variables
				db_execute('DELETE FROM plugin_reportit_variables WHERE ' . array_to_sql_or($selected_items, 'id'));
				db_execute('DELETE FROM plugin_reportit_rvars WHERE ' . array_to_sql_or($selected_items, 'variable_id'));
			}

			header('Location: templates.php?tab=variables&id=' . get_request_var('id'));
			exit;
		}

		//Set preconditions
		$ds_list = array(); $i = 0;

		foreach($_POST as $key => $value) {
			if (strstr($key, 'chk_')) {
				//Fetch report id
				$id = substr($key, 4);
				$variable_ids[] = $id;
				// ================= input validation =================
				input_validate_input_number($id);
				// ====================================================

				//Fetch report description
				$variable_description 	= db_fetch_cell_prepared('SELECT name
					FROM plugin_reportit_variables
					WHERE id = ?',
					array($id));

				$ds_list[$variable_description] = '';
			}
		}

		top_header();

		form_start('templates.php?action=template_edit&tab=variables&id=' . get_request_var('id') . '&tab=variables');

		html_start_box($variable_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

		if (get_request_var('drp_action') == '1') { //DELETE REPORT
			print "<tr>
				<td class='textArea'
					<p>" . __('Click \'Continue to Delete the following variables.', 'reportit') . '</p>';

			if (is_array($ds_list)) {
				//Check possible dependences for each variable
				foreach($variable_ids as $id) {
					$name = db_fetch_cell_prepared('SELECT abbreviation
						FROM plugin_reportit_variables
						WHERE id = ?',
						array($id));

					$count = db_fetch_cell_prepared('SELECT COUNT(*)
						FROM plugin_reportit_measurands
						WHERE template_id = ?
						AND calc_formula LIKE ?',
						array(get_request_var('id'), "%$name%"));

					if ($count != 0) {
						$error = true;
						break;
					}
				}

				if (!$error){
					print '<p>' . __('List of selected variables:', 'reportit') . '</p>';
					print '<div class="itemlist"><ul>';

					foreach($ds_list as $key => $value) {
						print '<li>' . __('Variable: %s', $key, 'reportit') . '</li>';
					}

					print '</ul></div>';
				}
			}

			print '</td></tr>';

			if ($ds_list === false || empty($ds_list) || !is_array($ds_list) || $error == true) {
				if ($error) {
					print "<tr><td class='odd'><span class='textError'>" . __('There are one or more variables in use.', 'reportit') . '</span></td></tr>';
				} else {
					print "<tr><td class='odd'><span class='textError'>" . __('You must select at least one variable.', 'reportit') . '</span></td></tr>';
				}

				$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?tab=variables&id=" . get_request_var('id') . "\")'>";
			} else {
				$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"templates.php?tab=variables&id=" . get_request_var('id') . "\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Delete Template Variables', 'reportit') . "'>";
			}
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='tab' value='variables'>
				<input type='hidden' name='selected_items' value='" . (isset($variable_ids) ? serialize($variable_ids) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();

		form_end();

		bottom_footer();
	} elseif (get_request_var('tab') == 'measurands') {
		// ================= input validation =================
		get_filter_request_var('id');
		// ====================================================

		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

			if (get_request_var('drp_action') == '2') { // DELETE MEASURANDS
				db_execute('DELETE FROM plugin_reportit_measurands WHERE ' . array_to_sql_or($selected_items, 'id'));

				//Check if it is necessary to lock the report template
				if (stat_autolock_template(get_request_var('id'))) {
					set_autolock_template(get_request_var('id'));
				}
			}

			header('Location: measurands.php?id=' . get_request_var('id'));
			exit;
		}

		//Set preconditions
		$ds_list = array(); $i = 0;

		foreach($_POST as $key => $value) {
			if (strstr($key, 'chk_')) {
				//Fetch report id
				$id = substr($key, 4);
				$measurand_ids[] = $id;
				// ================= input validation =================
				input_validate_input_number($id);
				// ====================================================

				//Fetch report description
				$measurand_description 	= db_fetch_cell_prepared('SELECT description
					FROM plugin_reportit_measurands
					WHERE id = ?',
					array($id));

				$ds_list[$measurand_description] = '';
			}
		}

		top_header();

		form_start('measurands.php');

		html_start_box($measurand_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

		if (get_request_var('drp_action') == '2') { //DELETE REPORT
			print "<tr class='odd'>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the following Metrics.  Notice: If there are no other Metrics left after this process, the Report Template will be locked automatically.', 'reportit') . '<p>';

			if (is_array($ds_list)) {
				print '<p>' . __('List of selected measurands:', 'reportit') . '</p>';
				print '<div class="itemlist"><ul>';

				foreach($ds_list as $key => $value) {
					print '<li>' . __('Metric: %s', $key, 'reportit') . '</li>';
				}

				print '</ul></div>';
			}

			print '</td></tr>';

			if (!is_array($ds_list) || empty($ds_list)) {
				print "<tr>
					<td class='textArea'>
						<span class='textError'>" . __('You must select at least one measurand.', 'reportit') . '</span>
					</td>
				</tr>';

				$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";
			} else {
				$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Delete Template Metrics', 'reportit') . "'>";
			}
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='tab' value='measurands'>
				<input type='hidden' name='selected_items' value='" . (isset($measurand_ids) ? serialize($measurand_ids) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	}
}

function variables() {
	global $variable_actions, $link_array, $list_of_modes, $var_types, $desc_array;

	$desc_array = array(
		'description' => array(
			'display' => __('Name', 'reportit'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'nosort' => array(
			'display' => __('Internal Name', 'reportit'),
			'align' => 'left'
		),
		'pre_filter' => array(
			'display' => __('Maximum', 'reportit'),
			'align' => 'left'
		),
		'nosort1' => array(
			'display' => __('Minimum', 'reportit'),
			'align' => 'left'
		),
		'nosort2' => array(
			'display' => __('Default', 'reportit'),
			'align' => 'left'
		),
		'nosort3' => array(
			'display' => __('Stepping', 'reportit'),
			'align' => 'left'
		),
		'nosort4' => array(
			'display' => __('Input Type', 'reportit'),
			'align' => 'left'
		),
		'nosort5' => array(
			'display' => __('Options', 'reportit'),
			'align' => 'left'
		),
	);

	$affix = '';

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));

	if (isset_request_var('sort') && isset_request_var('mode')) {
		if (!in_array(get_request_var('sort'), $link_array) || !in_array(get_request_var('mode'), $list_of_modes)) {
			die_html_custom_error();
		} else {
			$affix = ' ORDER BY ' . get_request_var('sort') . ' ' . get_request_var('mode');
		}
	}
	/* ==================================================== */

	$variables_list = db_fetch_assoc_prepared("SELECT *
		FROM plugin_reportit_variables
		WHERE template_id = ?
		$affix",
		array(get_request_var('id')));

	$template = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_templates
		WHERE id = ?',
		array(get_request_var('id')));

	$i = 0;

	$header_label = __("Variables [ Template: %s - %s ]", $template['name'], $template['description'], 'reportit');

	form_start('templates.php?tab=variables');

	form_hidden_box('tab', 'variables', '');

	html_start_box($header_label, '100%', '', '3', 'center', 'templates.php?action=variable_edit&tab=variables&id=0&template_id=' . get_request_var('id'));

	html_header_sort_checkbox($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($variables_list)) {
		foreach($variables_list as $variable) {
			$select_options_count = ($variable['input_type'] == 1) ? (($variable['max_value']-$variable['min_value'])/$variable['stepping'])+1 : false;
			$select_options_class = '';
			$icon = '';

			if ($select_options_count !== false) {
				if ($select_options_count <= 100) {
					$select_options_class = 'deviceUp';
					$icon = 'fa-thumbs-up';
				} else if ($select_options_count <= 500) {
					$select_options_class = 'deviceDownMuted';
					$icon = 'fa-thumbs-down';
				} else {
					$select_options_class = 'deviceDown';
					$icon = 'fa-exclamation-triangle';
				}
			}

			form_alternate_row('line' . $variable['id'], true);

			$url = 'templates.php?action=variable_edit' .
				'&tab=variables' .
				'&id=' . $variable['id'] .
				'&template_id=' . $variable['template_id'];

			form_selectable_cell(filter_value($variable['name'], '', $url), $variable['id']);
			form_selectable_ecell($variable['abbreviation'], $variable['id']);
			form_selectable_cell($variable['max_value'], $variable['id']);
			form_selectable_cell($variable['min_value'], $variable['id']);
			form_selectable_cell($variable['default_value'], $variable['id']);
			form_selectable_cell($variable['stepping'], $variable['id']);
			form_selectable_cell($var_types[$variable['input_type']], $variable['id'], 'left');
			form_selectable_cell('<font class="' . $select_options_class . '"><i class="fa ' . $icon . '" aria-hidden="true"></i> ' . (($select_options_count !== false) ? "($select_options_count)" : __('N/A', 'reportit')) . '</font>', $variable['id']);

			form_checkbox_cell($variable['name'], $variable['id']);

			form_end_row();
		}
	} else {
		print '<tr class="tableRow odd"><td colspan="9"><em>' . __('No Variables Found', 'reportit') . '</em></td></tr>';
	}

	$form_array = array(
		'id' => array(
			'method' =>'hidden_zero',
			'value'  => get_request_var('id')
		)
	);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $form_array
		)
	);

	html_end_box(true);

	draw_actions_dropdown($variable_actions);

	form_end();
}

function variable_edit() {
	global $template_actions, $var_types;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('template_id');
	/* ==================================================== */

	template_tabs(get_request_var('id'));

	if (!isempty_request_var('id')) {
		$variable_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_variables
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __('Variable Configuration [ edit: %s ]', $variable_data['name'], 'reportit');
	} else {
		$header_label = __('Variable Configuration [new]', 'reportit');
	}

	session_custom_error_display();

	$variable_id = (isset_request_var('id') ? get_request_var('id') : '0');
	$template_id = (isset_request_var('template_id') ? get_request_var('template_id') : $variable_data['template_id']);

	$form_array = array(
		'id' => array(
			'method' => 'hidden_zero',
			'value'  => $variable_id
		),
		'template_id' => array(
			'method' => 'hidden_zero',
			'value'  => $template_id
		),
		'save_component_variable' => array(
			'method' => 'hidden_zero',
			'value'  => 1
		),
		'header' => array(
			'friendly_name' => __('General', 'reportit'),
			'method'        => 'spacer'
		),
		'abbreviation'	=> array(
			'friendly_name' => __('Internal name', 'reportit'),
			'description'   => __('A unique identifier which will be created by ReportIt itself. Use this ID within the definition of your calculation formulas to include that value the report user has defined individually for it.', 'reportit'),
			'method'        => 'custom',
			'max_length'    => '100',
			'value'         => (isset($variable_data['abbreviation']) ? $variable_data['abbreviation'] : '-Available after first saving-')
		),
		'name' => array(
			'friendly_name' => __('Name'),
			'description'   => __('A name like "Threshold" for example which should be used as a headline within the report config.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '100',
			'placeholder'   => __('Provide a name for this Variable', 'reportit'),
			'value'         => (isset($variable_data['name']) ? $variable_data['name'] : '')
		),
		'description' => array(
			'friendly_name' => __('Description', 'reportit'),
			'description'   => __('A short, pithy description that explains the sense of this variable.', 'reportit'),
			'method'        => 'textarea',
			'textarea_rows' => '2',
			'textarea_cols' => '50',
			'default'       => '',
			'placeholder'   => __('Provide a meaningful description', 'reportit'),
			'value'         => (isset($variable_data['description']) ? $variable_data['description'] : '')
		),
		'max_value' => array(
			'friendly_name' => __('Maximum Value', 'reportit'),
			'description'   => __('Defines the upper limit of this variable.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '10',
			'value'         => (isset($variable_data['max_value']) ? $variable_data['max_value'] : '')
		),
		'min_value' => array(
			'friendly_name' => __('Minimum Value', 'reportit'),
			'description'   => __('Defines the lower limit of this variable.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '10',
			'value'         => (isset($variable_data['min_value']) ? $variable_data['min_value'] : '')
		),
		'default_value' => array(
			'friendly_name' => __('Default Value', 'reportit'),
			'description'   => __('Sets the default value.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '10',
			'value'         => (isset($variable_data['default_value']) ? $variable_data['default_value'] : '')
		),
		'input_type' => array(
			'friendly_name' => __('Type', 'reportit'),
			'description'   => __('The method the report owner should use to define this variable.', 'reportit'),
			'method'        => 'drop_array',
			'array'         => $var_types,
			'value'         => (isset($variable_data['input_type']) ? $variable_data['input_type'] : '')
		),
		'stepping' => array(
			'friendly_name' => __('Stepping', 'reportit'),
			'description'   => __('Defines the distance between two values if method "DropDown" has been chosen. Please ensure that this value is not set too low, because it defines indirectly the number of options the dropdown field will have. For example the following parameters: MAX:100, MIN:0, STEP:0.01  will result in a select box of 10.001 options. This can cause dramatical performance issues due to a high CPU load at the clients side. Try to keep it under 1000.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '10',
			'value'         => (isset($variable_data['stepping']) && $variable_data['stepping']) ? $variable_data['stepping'] : ''
		),
	);

	?>
	<script type='text/javascript'>
	function change_variable_type(){
		if ($('#input_type').val() == 2) {
			$('#stepping').prop('disabled', true);
		} else {
			$('#stepping').prop('disabled', false);
		}
	}

	$(function(){
		$('#input_type').change(function(){
			change_variable_type();
		});

		/* initiate settings */
		change_variable_type();
	});
	</script>
	<?php

	if (isset($variable_data['id'])) {
		form_start('templates.php?action=template_edit&tab=variables&template_id=' . $template_id . '&id=' . $variable_data['id']);
	} else {
		form_start('templates.php?action=template_edit&tab=variables&template_id=' . $template_id);
	}

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $form_array
		)
	);

	form_save_button('templates.php?action=template_edit&tab=variables&id=' . $template_id);

	html_end_box();
}

function measurand_edit() {
	global $config, $template_actions, $rounding, $consolidation_functions, $type_specifier, $precision;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	$measurand_id = (isset_request_var('id') ? get_request_var('id') : '0');
	$template_id  = (isset_request_var('template_id') ? get_request_var('template_id') : $measurand_data['template_id']);

	$template = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_templates
		WHERE id = ?', array($template_id));

	if (!isempty_request_var('id')) {
		$measurand_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_measurands
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __('Metric Configuration [ edit: %s - %s ]', $template['name'], $measurand_data['description'], 'reportit');
	} else {
		$header_label = __('Metric Configuration [new]', 'reportit');
	}

	$form_array = array(
		'id' => array(
			'method' => 'hidden_zero',
			'value'  => $measurand_id
		),
		'template_id' => array(
			'method' => 'hidden_zero',
			'value'  => $template_id
		),
		'save_component_measurand' => array(
			'method' => 'hidden_zero',
			'value' => 1
		),
		'header' => array(
			'friendly_name' => __('General', 'reportit'),
			'method'        => 'spacer',
			'collapsible'   => 'true'
		),
		'description' => array(
			'friendly_name' => __('Description', 'reportit'),
			'description'   => __('The explanation given to this measurand. This will be shown as legend within exports as well as a tooltip within the presentation of a report itself.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '255',
			'value'         => (isset($measurand_data['description']) ? $measurand_data['description'] : '')
		),
		'abbreviation' => array(
			'friendly_name' => __('Abbreviation', 'reportit'),
			'description'   => __('Define a unique abbreviation for this measurand with max. 8 letters/numbers.', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '10',
			'value'         => (isset($measurand_data['abbreviation']) ? $measurand_data['abbreviation'] : '')
		),
		'unit' => array(
			'friendly_name' => __('Unit', 'reportit'),
			'description'   => __('The unit given to this measurand. e.g. \'Bits/s\'', 'reportit'),
			'method'        => 'textbox',
			'max_length'    => '100',
			'value'         => (isset($measurand_data['unit']) ? $measurand_data['unit'] : '')
		),
		'cf' => array(
			'friendly_name' => __('Consolidation function', 'reportit'),
			'description'   => __('The name of the consolidation function to define which CDPs should be read out.', 'reportit'),
			'method'        => 'drop_array',
			'default'       => '0',
			'value'         => (isset($measurand_data['cf']) ? $measurand_data['cf'] : ''),
			'array'         => $consolidation_functions
		),
		'visible' => array(
			'friendly_name' => __('Visible', 'reportit'),
			'description'   => __('Choose \'enable\' if this measurand should be become part of the final report output. Leave it unflagged if this measurands will only be used as an auxiliary calculation.', 'reportit'),
			'method'        => 'checkbox',
			'value'         => ((isset($measurand_data['visible']) && $measurand_data['visible'] == true) ? 'on' : ''),
			'form_id'       => (isset_request_var('id') ? get_request_var('id') : ''),
			'default'       => 'on',

		),
		'spanned' => array(
			'friendly_name' => __('Separate', 'reportit'),
			'description'   => __('Choose \'enable\' if this measurand will only have one result in total instead of one for every Data Source Item. It\'s result<br>will be shown separately. Use this option in combination with "Visible" = "off" if you are looking for a measurand keeping an interim result only that should be reused within the calculation of other measurands without being visible for end users.', 'reportit'),
			'method'        => 'checkbox',
			'value'         => ((isset($measurand_data['spanned']) && $measurand_data['spanned'] == true) ? 'on' : ''),
			'form_id'       => (isset_request_var('id') ? get_request_var('id') : ''),
			'default'       => '',

		),
		'header2' => array(
			'friendly_name' => __('Formatting', 'reportit'),
			'method'        => 'spacer',
			'collapsible'   => 'true'
		),
		'data_type' => array(
			'friendly_name' => __('Type', 'reportit'),
			'method'        => 'drop_array',
			'array'         => $type_specifier,
			'description'   => __('Defines as what type the data should be treated as.', 'reportit'),
			'value'         => (isset($measurand_data['data_type']) ? $measurand_data['data_type'] : '1' )
		),
		'data_precision' => array(
			'friendly_name' => __('Precision', 'reportit'),
			'description'   => __('Defines how many decimal digits should be displayed for floating-point numbers.', 'reportit'),
			'method'        => 'drop_array',
			'array'         => $precision,
			'value'         => (isset($measurand_data['data_precision']) ? $measurand_data['data_precision'] : '2' )
		),
		'rounding' => array(
			'friendly_name' => __('Prefixes', 'reportit'),
			'description'   => __('Choose the type of prefix being used to format the result. With the use of decimal prefixes \'1024\' will be formatted to \'1.024k\' while the binary prefixes option returns \'1ki\'. Select \'off\' to display the raw data, here \'1024\'.', 'reportit'),
			'method'        => 'drop_array',
			'array'         => $rounding,
			'value'         => (isset($measurand_data['rounding']) ? $measurand_data['rounding'] : '2' )
		),
		'header3' => array(
			'friendly_name' => __('Formula', 'reportit'),
			'method'        => 'spacer',
			'collapsible'   => 'true',
		),
		'calc_formula' => array(
			'friendly_name' => __('Calculation Formula', 'reportit'),
			'description'   => __('The mathematical definition of this measurand. Allowed are all combinations of operators and operands listed below following the rules of mathematics. Use round and square brackets to signify complex terms and the order of operations.', 'reportit'),
			'method'        => 'custom',
			'value'         => "<textarea aria-multiline='true' cols='60' rows='5' id='calc_formula' name='calc_formula'>" . (isset($measurand_data['calc_formula']) ? $measurand_data['calc_formula'] : "" ) . '</textarea>'
		),
		'ops_and_opds' => array(
			'friendly_name' => __('Operators & Operands', 'reportit'),
			'description'   => __('Click on one of the listed operators or operand to append them to your calucalion formula. The tooltip will show you additional information like description, return value, arguments and usage.', 'reportit'),
			'method'        => 'custom',
			'value'         => html_calc_syntax($measurand_id, $template_id)
		),
	);

	?>
	<script type='text/javascript'>
	function change_data_type(){
		if ($('#data_type').val() in {0:'',2:'',3:'',4:'',5:'',6:''}) {
	 		$('#data_precision').prop('disabled', true);
		} else {
			$('#data_precision').prop('disabled', false);
		};

		if ($('#data_type').val() in {0:'', 4:'', 5:'', 6:'', 7:''}) {
			$('#rounding').prop('disabled', true);
		} else {
			$('#ounding').prop('disabled', false);
		}
	}

	function add_to_calc(name) {
		fieldId = document.getElementById('calc_formula');
		old = fieldId.value;
		fieldId.value = old + name;
		fieldId.focus();
		fieldId.value = fieldId.value;
		return false;
	}

	$(function(){
		$('#data_type').change(function() {
			change_data_type();
		});

		$('.reportItHover').tooltip({
			items: '[data-title]',
			content: function() {
				return atob($(this).data('title'))
			}
		});

		/* initiate settings */
		change_data_type();
	});
	</script>

	<?php

	template_tabs(get_request_var('id'));

	form_start('templates.php?action=template_edit&tab=measurands&id=' . get_request_var('id') . '&template_id=' . $template_id);

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $form_array
		)
	);

	html_end_box();

	form_save_button('templates.php?action=template_edit&tab=measurands&id=' . $template_id);

	print '<div id="tooltip"></div>';
}

function measurands() {
	global $measurand_actions, $config, $consolidation_functions;

	/* ================= input validation ================= */
	$id = get_filter_request_var('id');
	/* ==================================================== */

	$measurands_list = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_measurands
		WHERE template_id = ?
		ORDER BY id', array(get_request_var('id')));

	$template = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_templates
		WHERE id = ?',
		array(get_request_var('id')));

	$i = 0;

	$header_label = __('Metrics [ Template: %s - %s ]', $template['name'], $template['description'], 'reportit');

	form_start('templates.php?action=template_edit&tab=measurands&template_id=' . get_request_var('id'));

	form_hidden_box('tab', 'measurands', '');

	html_start_box($header_label, '100%', '', '3', 'center', 'templates.php?action=measurand_edit&tab=measurands&id=0&template_id=' . get_request_var('id'));

	$display_text = array(
		__('Name', 'reportit'),
		__('Abbreviation', 'reportit'),
		__('Unit', 'reportit'),
		__('Consolidation Function', 'reportit'),
		__('Visible', 'reportit'),
		__('Separate', 'reportit'),
		__('Calculation Formula', 'reportit')
	);

	html_header_checkbox($display_text, false);

	if (cacti_sizeof($measurands_list)) {
		foreach($measurands_list as $measurand) {
			$link = 'templates.php?action=measurand_edit&tab=measurands&template_id=' . get_request_var('id') . '&id=' . $measurand['id'];

			form_alternate_row('line' . $measurand['id'], true);

			form_selectable_cell(filter_value($measurand['description'], '', $link), $measurand['id']);
			form_selectable_cell($measurand['abbreviation'], $measurand['id']);
			form_selectable_cell($measurand['unit'], $measurand['id']);
			form_selectable_cell($consolidation_functions[$measurand['cf']], $measurand['id']);
			form_selectable_cell(($measurand['visible'] ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>'), $measurand['id']);
			form_selectable_cell(($measurand['spanned'] ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>'), $measurand['id']);
			form_selectable_cell($measurand['calc_formula'], $measurand['id']);
			form_checkbox_cell($measurand['description'], $measurand['id']);

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Metrics Found', 'reportit') . '</em></td></tr>';
	}

	$form_array = array(
		'id' => array(
			'method' => 'hidden_zero',
			'value' => get_request_var('id')
		)
	);

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		)
	);

	html_end_box(true);

	draw_actions_dropdown($measurand_actions, 'templates.php?action=template_edit&tab=measurands&id=' . get_request_var('id'));

	form_end();
}

