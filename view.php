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

$guest_account = true;

chdir(__DIR__ . '/../../');
require_once('include/auth.php');

if (!defined('REPORTIT_BASE_PATH')) {
	include_once(__DIR__ . '/setup.php');
	reportit_define_constants();
}

include_once(REPORTIT_BASE_PATH . '/lib/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_export.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_view.php');

set_default_action();

switch (get_request_var('action')) {
	case 'show_report':
		general_header();
		show_report();
		bottom_footer();
		break;
	case 'show_graph_overview':
		show_graph_overview();
		break;
	case 'export':
		general_header();
		show_export_wizard(true);
		bottom_footer();
		break;
	case 'actions':
		export();
		break;
	default:
		general_header();
		standard();
		bottom_footer();
		break;
}

function export() {
	global $config, $export_formats;

	$id = validate_report_vars();

	/* form the 'where' clause for our main sql query */
	$table = (get_request_var('archive') != -1)? 'a' : 'c';

	if (get_request_var('filter') != '') {
		$sql_where = 'WHERE ' . $table . '.name_cache LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
	} else {
		$sql_where = '';
	}

	$sql_order = get_order_string();

	/* get informations about the archive if it exists */
	$archive = info_xml_archive(get_request_var('id'));

	/* load report archive and fill up report cache if requested*/
	if (get_request_var('archive') != -1) {
		cache_xml_file(get_request_var('id'), get_request_var('archive'));
		$cache_id = get_request_var('id') . '_' . get_request_var('archive');
	}

	/* load report data */
	$data = (get_request_var('archive') == -1)
		? get_prepared_report_data(get_request_var('id'), 'export', $sql_where)
		: get_prepared_archive_data($cache_id, 'export', $sql_where);

	/* call export function */
	$export_function = 'export_to_' . get_request_var('drp_action');
	$output	= $export_function($data);

	$content_type = strtolower(get_request_var('drp_action'));
	if (get_request_var('drp_action') == 'SML') {
		set_request_var('drp_action', 'xml');
		$content_type = 'vnd.ms-excel';
	}

	/* create filename */
	$filename = str_replace('<report_id>', get_request_var('id'), read_config_option('reportit_exp_filename') . '.' . get_request_var('drp_action'));
	$filename = strtolower($filename);

	/* configure data header */
	header('Cache-Control: public');
	header('Content-Description: File Transfer');
	header('Content-Type: application/' . $content_type);
	header('Content-Disposition: attachment; filename="' . $filename . '"');

	print $output;
}

function standard() {
	global $config, $item_rows;

	$myId = my_id();
	$tmz  = (read_config_option('reportit_show_tmz') == 'on') ? '(' . date('T') . ')' : '';

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
		),
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '0',
			'pageset' => true
		)
	);

	validate_store_request_vars($filters, 'sess_reportit_view');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where  = 'WHERE a.last_run != 0';
	$sql_params = array();

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var('filter'))) {
		$sql_where .= ' AND a.description LIKE ?';
		$sql_params[] = '%' . get_request_var('filter') . '%';
	}

	if (get_request_var('type') == '-1') {
		$sql_where .= " AND a.public = 'on'";
	} elseif (get_request_var('type') == '0') {
		$sql_where .= ' AND a.user_id= ?';
		$sql_params[] = $myId;
	}

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(a.id)
		FROM plugin_reportit_reports AS a
		$sql_where",
		$sql_params);

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$report_list = db_fetch_assoc_prepared("SELECT a.*, b.description AS template_description
		FROM plugin_reportit_reports AS a
		INNER JOIN plugin_reportit_templates AS b
		ON b.id = a.template_id
		$sql_where
		$sql_order
		$sql_limit",
		$sql_params);

	/* start with HTML output */
	html_start_box(__('Reports Filter', 'reportit'), '100%', '', '3', 'center', '');

	?>
	<tr class='odd'>
		<td>
			<form id='form_report' action='view.php'>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Search', 'reportit');?>
						</td>
						<td>
							<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
						</td>
						<td>
							<?php print __('Type', 'reportit');?>
						</td>
						<td>
							<select id='type'>
								<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('Public Reports', 'reportit');?></option>
								<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('My Reports', 'reportit');?></option>
							</select>
						</td>
						<td>
							<?php print __('Reports', 'reportit');?>
						</td>
						<td>
							<select id='rows' onChange='applyFilter()'>
								<option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default', 'reportit');?></option>
								<?php
								if (cacti_sizeof($item_rows)) {
									foreach ($item_rows as $key => $value) {
										print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
									}
								}
								?>
							</select>
						</td>
						<td>
							<span>
								<input id='refresh' type='submit' value='<?php print __('Go', 'reportit');?>'>
								<input id='clear' type='button' value='<?php print __('Clear', 'reportit');?>'>
							</span>
						</td>
					</tr>
				</table>
			</form>
			<script type='text/javascript'>
			function applyFilter() {
				strURL  = 'view.php?action=standard';
				strURL += '&filter='+escape($('#filter').val());
				strURL += '&type='+$('#type').val();
				strURL += '&rows='+$('#rows').val();
				loadUrl({ url: strURL });
			}

			function clearFilter() {
				strURL = 'view.php?action=standard&clear=1';
				loadUrl({ url: strURL });
			}

			$(function() {
				$('#type, #rows').change(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});

				$('#form_report').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});
			</script>
		</td>
	</tr>
	<?php

	html_end_box();

	$desc_array = array(
		'name' => array(
			'display' => __('Name', 'reportit'),
			'align'   => 'left',
			'sort'    => 'ASC'
		),
		'user_id' => array(
			'display' => __('Owner', 'reportit'),
			'align'   => 'left',
			'sort'    => 'ASC'
		),
		'template_description' => array(
			'display' => __('Template', 'reportit'),
			'align'   => 'left',
			'sort'    => 'ASC'
		),
		'nosort0' => array(
			'display' => __('Period (From - To)', 'reportit'),
			'align'   => 'left'
		),
		'last_started' => array(
			'display' => __('Last Started %s', $tmz, 'reportit'),
			'align'   => 'left',
			'sort'    => 'DESC'
		),
		'last_runtime' => array(
			'display' => __('Last Runtime [s]', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC'
		)
	);

	$nav = html_nav_bar('view.php?filter=' . get_request_var('filter'), 20, get_request_var('page'), $rows, $total_rows, cacti_sizeof($desc_array), __('Reports', 'reportit'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'));

	// Build report list
	if (cacti_sizeof($report_list)) {
		foreach ($report_list as $report) {
			$link = 'view.php?action=show_report&clear=1&id=' . $report['id'];
			$ownerId = $report['user_id'];

			form_alternate_row();

			form_selectable_cell(filter_value($report['name'], get_request_var('filter'), $link), $ownerId);
			form_selectable_cell(other_name($ownerId), $ownerId);
			form_selectable_cell($report['template_description'], $ownerId);
			form_selectable_cell(date(config_date_format(), strtotime($report['start_date'])) . ' - ' . date(config_date_format(), strtotime($report['end_date'])), $ownerId);
			form_selectable_cell($report['last_started'], $ownerId);
			form_selectable_cell(sprintf('%01.1f', $report['last_runtime']), $ownerId, '', 'right');

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($desc_array) . '"><em>' . __('No Reports Found', 'reportit') . '</em></td></tr>';
	}

	html_end_box();

	if ($total_rows > $rows) {
		print $nav;
	}
}

function validate_report_vars() {
	/* if the user pushed the 'clear' button */
	$id = (read_graph_config_option('reportit_view_filter') == 'on') ? get_filter_request_var('id') : '';

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
			'default' => 'a.id',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'data_source' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'measurand' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'archive' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'info' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-2'
		),
		'subhead' => array(
			'filter' => FILTER_CALLBACK,
			'default' => false,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
		),
		'summary' => array(
			'filter' => FILTER_CALLBACK,
			'default' => false,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
		),
		'graph_mode' => array(
			'filter' => FILTER_CALLBACK,
			'default' => false,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
		)
	);

	validate_store_request_vars($filters, 'sess_reportit_show_' . $id);
	/* ================= input validation ================= */

	return $id;
}

function show_report() {
	global $config, $search, $t_limit, $add_info, $export_formats, $item_rows;

	$limitation      = 0;
	$columns         = 0;
	$num_of_sets     = 0;
	$sql_where       = '';
	$subhead         = '';
	$include_mea     = '';
	$cache_id        = '';
	$table           = '';
	$measurands      = array();
	$ds_description  = array();
	$rs_description  = array();
	$ov_description  = array();
	$report_summary  = array();
	$archive         = array();
	$report_ds_alias = array();

	$id = validate_report_vars();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	/* ==================== checkpoint ==================== */
	my_report(get_filter_request_var('id'), true);
	$session_max_rows = get_valid_max_rows();
	/* ==================================================== */

	/* form the 'where' clause for our main sql query */
	$table = (get_request_var('archive') != -1)? 'a' : 'c';

	if (get_request_var('filter') != '') {
		$sql_where = 'WHERE ' . $table . '.name_cache LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
	} else {
		$sql_where = '';
	}

	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	$sql_order = get_order_string();

	if (!isempty_request_var('subhead')) {
		$sql_order = str_replace('ORDER BY ', 'ORDER BY description, ', $sql_order);
	}

	$sql_affix = $sql_where . $sql_order . $sql_limit;

	/* get informations about the archive if it exists */
	$archive = info_xml_archive(get_request_var('id'));

	/* load report archive and fill up report cache if requested*/
	if (get_request_var('archive') != -1) {
		cache_xml_file(get_request_var('id'), get_request_var('archive'));

		$cache_id = get_request_var('id') . '_' . get_request_var('archive');
	}

	/* load report data */
	if (get_request_var('archive') == -1) {
		$data = get_prepared_report_data(get_request_var('id'), 'view', $sql_affix);
	} else {
		$data = get_prepared_archive_data($cache_id, 'view', $sql_affix);
	}

	/* get total number of rows (data items) */
	if (get_request_var('archive') != -1) {
		$source = 'plugin_reportit_tmp_' . get_request_var('id') . '_' . get_request_var('archive') . ' AS a
			INNER JOIN data_template_data AS c
			ON c.local_data_id = a.id';
	} else {
		$source = 'plugin_reportit_results_' . get_request_var('id') . ' AS a
			INNER JOIN data_template_data AS c
			ON c.local_data_id = a.id';
	}

	$total_rows = db_fetch_cell("SELECT COUNT(a.id)
		FROM $source
		$sql_where");

	/**
	 * save all data source names for the drop down menu.
	 * if available use the data source alias instead of the internal names
	 * extract result description
	 */
	$report_data = $data['report_data'];
	$mea         = $data['report_measurands'];
	//print_r($data);exit;

	if (strpos($data['report_data']['rs_def'], '-') !== false) {
		list($rs_description, $count_rs) = explode('-', $data['report_data']['rs_def']);
	} else {
		$rs_description = false;
		$count_rs       = 0;
	}

	$rs_description = (empty($rs_description) ? false : explode('|', $rs_description));

	if ($rs_description !== false) {
		foreach ($rs_description as $key => $id) {
			if (!isset($mea[$id]['visible']) || $mea[$id]['visible'] == '') {
				$count_rs--;
				unset($rs_description[$key]);
			} else {
				if (get_request_var('data_source') != -2) {
					$measurands[$id] = $mea[$id]['abbreviation'];
				}
			}
		}
		if (get_request_var('measurand') != -1) {
			if (in_array(get_request_var('measurand'), $rs_description)) {
				$rs_description = array(get_request_var('measurand'));
				$count_rs = 1;
				$count_ov = 0;
			}
		}
	}

	/* extract 'Overall' description */
	if (!isset($count_ov)) {
		list($ov_description, $count_ov) = explode('-', $report_data['sp_def']);

		$ov_description = ($ov_description == '') ? false : explode('|', $ov_description);

		if ($ov_description !== false) {
			foreach ($ov_description as $key => $id) {
				if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == '') {
					$count_ov--;
					unset($ov_description[$key]);
				} else {
					if (get_request_var('data_source') == -1 || get_request_var('data_source') == -2) {
						$measurands[$id] = $mea[$id]['abbreviation'];
					}
				}
			}

			if (get_request_var('measurand') != -1) {
				if (in_array(get_request_var('measurand'), $ov_description)) {
					$ov_description = array(get_request_var('measurand'));
					$count_ov = 1;
					$count_rs = 0;
				}
			}
		}
	}

	if ($count_rs > 0) {
		$ds_description = explode('|', $report_data['ds_description']);
		$columns += sizeof($ds_description)*$count_rs;
	}

	if ($count_ov > 0) {
		$ds_description[-2] = 'overall';
		$columns += $count_ov;
	}

	$data_sources = $ds_description;
	foreach ($data_sources as $key => $value) {
		if (is_array($report_ds_alias) && array_key_exists($value, $report_ds_alias) && $report_ds_alias[$value] != '')
			$data_sources[$key] = $report_ds_alias[$value];
	}

	/* filter by data source */
	if (get_request_var('data_source') != -1) {
		$ds_description = array($ds_description[get_request_var('data_source')]);
	}

	/* start HTML output */
	$report_header = $data['report_data']['name'];

	html_start_box(__($report_header), '100%', '', '3', 'center', '');

	ob_start();
	?>
	<tr class='odd'>
		<td>
			<form id='form_report' action='view.php'>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Metric', 'reportit');?>
						</td>
						<td>
							<select id='measurand'>
								<option value='-1'<?php if (get_request_var('measurand') == '-1') {?> selected<?php }?>><?php print __('All', 'reportit');?></option>
								<?php
								if (cacti_sizeof($measurands)) {
									foreach ($measurands as $key => $value) {
										print "<option value='" . $key . "'"; if (get_request_var('measurand') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
									}
								}
								?>
							</select>
						</td>
						<td>
							<?php print __('Data Source', 'reportit');?>
						</td>
						<td>
							<select id='data_source'>
								<option value='-1'<?php if (get_request_var('data_source') == '-1') {?> selected<?php }?>><?php print __('All', 'reportit');?></option>
								<?php
								if (cacti_sizeof($ds_description)) {
									foreach ($data_sources as $key => $value) {
										print "<option value='" . $key . "'"; if (get_request_var('data_source') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
									}
								}
								?>
							</select>
						</td>
						<?php
						$chk_fields = array(
							'graph_mode' => __('Show Top 10 Graphs', 'reportit'),
							'subhead'    => __('Show Subheads', 'reportit'),
							'summary'    => __('Show Summary', 'reportit'),
						);

						foreach ($chk_fields as $chk_name => $chk_desc) {
							print '<td><span>';

							$chk_value = get_request_var($chk_name);
							$chk_set   = !isempty_request_var($chk_name) ? 'on' : '';

							print "<input id='$chk_name' class='ui-state-default ui-corner-all' type='checkbox' " . ($chk_set ? ' checked':'') . '>';
							print "<label for='$chk_name' title='" . html_escape($chk_desc) . "'>" . $chk_desc . "</label>";

							print '</span></td>';
						}

						?>
						<td>
							<span>
								<input type='submit' value='<?php print __esc_x('Button: use filter settings', 'Go', 'reportit');?>' id='refresh'>
								<input type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear', 'reportit');?>' id='clear'>
							</span>
						</td>
					</tr>
				</table>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Search', 'reportit');?>
						</td>
						<td>
							<input id='filter' size='30' type='text' value='<?php print get_request_var('filter');?>'>
						</td>
						<td><?php print __('Additional', 'reportit');?></td>
						<td>
							<select id='info'>
								<?php
								foreach ($add_info as $key => $value) {
								    print "<option value='" . $key . "'"; if (get_request_var('info') == $key) { print ' selected'; } print '>' . $value[0] . '</option>';
								}
								?>
							</select>
						</td>
						<td><?php print __('Items', 'reportit');?></td>
						<td>
							<select id='rows'>
								<option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default', 'reportit');?></option>
								<?php
								foreach ($item_rows as $key => $value) {
								    print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
								?>
							</select>
						</td>
						<?php if ($archive != false) {?>
						<td><?php print __('Archive', 'reportit');?></td>
						<td>
							<select id='archive'>
								<option value='-1'<?php if (get_request_var('archive') == '-1') {?> selected<?php }?>><?php print __('Current', 'reportit');?></option>
								<?php
								if (cacti_sizeof($archive)) {
									foreach ($archive as $key => $value) {
									    print "<option value='" . $key . "'"; if (get_request_var('archive') == $key) { print ' selected'; } print '>' . $value . '</option>';
									}
								}
								?>
							</select>
						</td>
						<?php } else { ?>
						<input id='archive' type='hidden' value='-1'>
						<?php } ?>
					</tr>
				</table>
			</form>
			<script type='text/javascript'>
			function applyFilter() {
				strURL  = 'view.php?action=show_report';
				strURL += '&id=<?php print get_request_var('id');?>';
				strURL += '&filter='+escape($('#filter').val());
				strURL += '&info='+$('#info').val();
				strURL += '&rows='+$('#rows').val();
				strURL += '&measurand='+$('#measurand').val();
				strURL += '&data_source='+$('#data_source').val();
				strURL += '&archive='+$('#archive').val();
				strURL += '&graph_mode='+($('#graph_mode').is(':checked')?'on':'');
				strURL += '&summary='+($('#summary').is(':checked')?'on':'');
				strURL += '&subhead='+($('#subhead').is(':checked')?'on':'');
				loadUrl({ url: strURL });
			}

			function clearFilter() {
				strURL = 'view.php?action=show_report&id=<?php print get_request_var('id');?>&clear=1';
				loadUrl({ url: strURL });
			}

			$(function() {
				$('#info, #rows, #measurand, #data_source, #archive, #graph_mode, #summary, #subhead').change(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});

				$('#form_report').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});
			</script>
		</td>
	</tr>
	<?php

	html_end_box();

	if (!isempty_request_var('summary')) {
		$report_summary[1][__('Title', 'reportit')]   = $data['report_data']['name'];
		$report_summary[1][__('Runtime', 'reportit')] = $data['report_data']['last_runtime'] . 's';

		$report_summary[2][__('Owner', 'reportit')]              = $data['report_data']['owner'];
		$report_summary[2][__('Sliding Time Frame', 'reportit')] = ($data['report_data']['sliding'] == '') ? 'disabled' : 'enabled (' . strtolower($data['report_data']['preset_timespan']) .')';

		$report_summary[3][__('Last Run', 'reportit')]  = $data['report_data']['last_run'];
		$report_summary[3][__('Enabled', 'reportit')] = ($data['report_data']['enabled'] == '') ? 'disabled' : 'enabled (' . $data['report_data']['frequency'] . ')';

		$report_summary[4][__('Period', 'reportit')]                  = $data['report_data']['start_date'] . ' - ' . $data['report_data']['end_date'];
		$report_summary[4][__('Auto Generated RRD list', 'reportit')] = ($data['report_data']['autorrdlist'] == '')? 'disabled' : 'enabled';

		html_start_box('', '100%', '', '3', 'center', '');

		foreach ($report_summary as $array) {
			print '<tr>';

			foreach ($array as $key => $value) {
				print "<td><b>$key:</b></td></td><td align='left'>" . html_escape($value) . '</td>';
			}

			print '</tr>';
		}

		html_end_box();
	}

	if (isempty_request_var('graph_mode')) {
		show_table_view($data, $ds_description, $rs_description, $ov_description, $count_ov, $count_rs, $columns, $rows, $total_rows);
	} else {
		show_graph_view($data, $ds_description, $rs_description, $ov_description, $count_ov, $count_rs);
	}
}

function show_table_view($data, $ds_description, $rs_description, $ov_description, $count_ov, $count_rs, $columns, $rows, $total_rows) {
	global $config, $search, $t_limit, $add_info, $export_formats, $item_rows;

	$report_ds_alias = $data['report_ds_alias'];
	$report_data     = $data['report_data'];
	$report_results  = $data['report_results'];
	$report_header   = $report_data['name'];
	$mea             = $data['report_measurands'];

	$nav = html_nav_bar('view.php?action=show_report&id=' . get_request_var('id'), 20, get_request_var('page'), $rows, $total_rows, $columns, __('Reports', 'reportit'), 'page', 'main');
	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	/* print table header */
	$display_text = array(
		'name_cache' => array(
			'display' => __('Data Description', 'reportit'),
			'align'   => 'left',
			'sort'    => 'ASC'
		)
	);

	foreach ($ds_description as $datasource) {
		$name = ($datasource != 'overall') ? $rs_description : $ov_description;

		if ($name !== false) {
			foreach ($name as $id) {
				$var   = ($datasource != 'overall') ? $datasource . '__' . $id : 'spanned__' . $id;
				$title = $mea[$id]['name'];

				if ($mea[$id]['visible'] != '') {
					$display_text[$var] = array(
						'display' => $mea[$id]['abbreviation'] . ' [' . $mea[$id]['unit'] . ']',
						'sort'    => 'DESC',
						'align'   => 'right',
						'tip'     => $title
					);
				}
			}
		}
	}

	if (isempty_request_var('graph_mode')) {
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), '1', 'view.php?action=show_report&id=' . get_request_var('id'));
	}

	/* Set preconditions */
	$last_subhead = '';
	$additional = array();
	if (cacti_sizeof($report_results)) {
		foreach ($report_results as $result) {
			if (!isempty_request_var('subhead')) {
				$replace = array ($result['start_time'], $result['end_time'], $result['timezone'], $result['start_day'], $result['end_day']);
				$subhead = str_replace($search, $replace, $result['name']);

				if (empty($subhead)) {
					$subhead = __('-- NO SUBHEADING --', 'reportit');
				}

				if ($last_subhead != $subhead) {
					$last_subhead = $subhead;

					print "<tr class='cactiTableTitle' style='float: none; display: table-row;'>";
					print "<th class='textSubHeaderDark' style='float: none; display: table-cell;'>" . html_escape($subhead) . '</th>';

					foreach ($ds_description as $description) {
						$counter = ($description != 'overall') ? $count_rs : $count_ov;

						if (is_array($report_ds_alias) && array_key_exists($description, $report_ds_alias) && $report_ds_alias[$description] != '') {
							$description = $report_ds_alias[$description];
						}

						print "<th colspan='$counter' class='textSubHeaderDark' style='float: none; display: table-cell; text-align: center;'>" . html_escape($description) . '</th>';
					}

					print '</tr>';
				}
			}

			form_alternate_row();

			print '<td>
				<a class="linkEditMain" href="view.php?action=show_graph_overview&id=' . get_request_var('id') . '&rrd=' . $result['id'] . '&cache=' . get_request_var('archive') . '">' . filter_value($result['name_cache'], get_request_var('filter')) . '</a>';

			print '</td>';

			foreach ($ds_description as $datasource) {
				$name  = ($datasource != 'overall') ? $rs_description : $ov_description;
				$first = '';

				foreach ($name as $id) {
					$rounding       = $mea[$id]['rounding'];
					$data_type      = $mea[$id]['data_type'];
					$data_precision = $mea[$id]['data_precision'];
					$var            = ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
					$value          = $result[$var];

					$additional[$var]['values'][]       = $value;
					$additional[$var]['rounding']       = $rounding;
					$additional[$var]['data_type']      = $data_type;
					$additional[$var]['data_precision'] = $data_precision;

					print "<td class='right'$first>";

					print get_unit($value, $rounding, $data_type, $data_precision);

					print '</td>';

					$first = '';
				}
			}
		}
	} else {
		print "<tr><td colspan='" . sizeof($display_text) . "'><em>" . __('No Data Items', 'reportit') . '</em></td></tr>';
	}

	/* show additional informations if requested */
	switch (get_request_var('info')) {
		case '-2':
			break;
		case '-1':
			print '<tr></tr>';

			if (cacti_sizeof($additional)) {
				for($a=1; $a<5; $a++) {
					form_alternate_row();

					$description = $add_info[$a][0];
					$calc_fct    = $add_info[$a][1];

					print '<td>' . $description . '</td>';

					foreach ($additional as $array){
						print '<td class="right">' . get_unit($calc_fct($array['values']), $array['rounding'], $array['data_type'], $array['data_precision']) . '</td>';
					}
				}
			}

			break;
		default:
			print '<tr></tr>';

			if (cacti_sizeof($additional)) {
				form_alternate_row();
				$description = $add_info[get_request_var('info')][0];
				$calc_fct    = $add_info[get_request_var('info')][1];

				print '<td>' . $description . '</td>';
				foreach ($additional as $array){
					print '<td class="right">' . get_unit($calc_fct($array['values']), $array['rounding'], $array['data_type'], $array['data_precision']) . '</td>';
				}
			}

			break;
	}

	html_end_box();

	if ($total_rows > $rows) {
		print $nav;
	}

	print '<form name="custom_dropdown" method="post">';

	draw_actions_dropdown($export_formats,0);

	print '</form>';

	ob_end_flush();
}

function show_graph_view($data, $ds_description, $rs_description, $ov_description, $count_ov, $count_rs) {
	global $config, $colors, $graphs, $limit;

	$affix            = '';
	$description      = '';
	$limitation       = 10;

	$report_ds_alias  = $data['report_ds_alias'];
	$report_data      = $data['report_data'];
	$mea              = $data['report_measurands'];
	$report_header    = $report_data['name'];

	if (cacti_sizeof($ds_description)) {
		foreach($ds_description as $datasource) {
			$description = (is_array($report_ds_alias) && array_key_exists($datasource, $report_ds_alias))
				? ($report_ds_alias[$datasource] != '') ? $report_ds_alias[$datasource] : $datasource : $datasource;

			html_start_box(__('Data Source: %s', $description, 'reportit'), '100%', false, '3', 'center', '');

			$name = ($datasource != 'overall') ? $rs_description : $ov_description;

			if ($name !== false) {
				$graph_id = 0;

				foreach($name as $id) {
					$var            = ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
					$title          = $mea[$id]['name'];
					$rounding       = $mea[$id]['rounding'];
					$unit           = $mea[$id]['unit'];
					$rounding       = $mea[$id]['rounding'];
					$data_type      = $mea[$id]['data_type'];
					$data_precision = $mea[$id]['data_precision'];
					$order          = 'DESC';
					$suffix			= " ORDER BY a.$var $order LIMIT 0, $limitation";

					if ($mea[$id]['visible'] != '') {
						if (get_request_var('archive') == -1) {
							$data = db_fetch_assoc("SELECT a.$var, b.*, c.name_cache
								FROM plugin_reportit_results_" . get_request_var('id') . " AS a
								INNER JOIN plugin_reportit_data_items AS b
								ON b.id = a.id
								AND b.report_id = " . get_request_var('id') . "
								INNER JOIN data_template_data AS c
								ON c.local_data_id = a.id
								$suffix");
						} else {
							$table = 'plugin_reportit_tmp_' . get_request_var('id') . '_' . get_request_var('archive');

							$data = db_fetch_assoc("SELECT * FROM $table AS a $suffix");
						}

						print "<tr class='tableHeader'>
							<td colspan='2' class='textHeaderDark'>" . __esc('Metric: %s (%s)', $title, $mea[$id]['abbreviation'], 'reportit') . '</td>
						</tr>';

						$graph_data = array();

						foreach ($data as $row)	{
							$graph_data[$row['name_cache']] = $row[$var];
						}

						print '<tr>';
						print '<td>' . plugin_reportit_graph('graph_' . $var . '_' . $graph_id, $graph_data) . '</td>';

						$graph_id++;

						print "<td style='width:100%;vertical-align:text-top'>";

						html_start_box('', '100%', '', '3', 'center', '');

						if (cacti_sizeof($data)) {
							$display_text = array(
								array(
									'display' => __('Pos.', 'reportit'),
									'align' => 'left'
								),
								array(
									'display' => __('Description', 'reportit'),
									'align' => 'left'
								),
								array(
									'display' => __('Results [%s]', $unit, 'reportid'),
									'align' => 'right'
								)
							);

							html_header($display_text);

							$i = 0;
							foreach($data as $item) {
								$i++;

								$value	= $item[$var];
								$title 	= "{$item['start_day']}&nbsp;-&nbsp;{$item['end_day']}&nbsp;&#10;{$item['start_time']}&nbsp;-&nbsp;{$item['end_time']} {$item['timezone']}";

								form_alternate_row();

								print "<td title='$title'>$i</td>";

								print "<td title='$title'>
									<a class='linkEditMain' href='view.php?action=show_graph_overview&id=" . get_request_var('id') . "&rrd={$item['id']}&cache=" . get_request_var('archive') . "'>{$item['name_cache']}</a>
								</td>";

								print "<td title='$title' class='right'>";

								if ($value == NULL) {
									print 'NA';
								} elseif ($value == 0) {
									print $value;
								} else {
									print get_unit($value, $rounding, $data_type, $data_precision);
								}

								print '</td>';

								form_end_row();
							}
						}

						html_end_box();

						print '</td></tr>';
					}
				}
			}

			html_end_box();
		}
	}

	ob_end_flush();
}

function show_graph_overview() {
	global $config;

	/* ================= Input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('rrd'));
	input_validate_input_number(get_request_var('cache'));
	/* ==================================================== */

	/* load report archive and fill up report cache if requested*/
	if (get_request_var('cache') != -1) {
		cache_xml_file(get_request_var('id'), get_request_var('cache'));
		$cache_id = get_request_var('id') . '_' . get_request_var('cache');
	}

	/* load report data */
	$data = (get_request_var('cache') == -1)
		? get_prepared_report_data(get_request_var('id'),'view')
		: get_prepared_archive_data($cache_id, 'view');

	$report_data = $data['report_data'];

	$local_graph_id = db_fetch_cell_prepared('SELECT DISTINCT gti.local_graph_id
		FROM data_template_data AS dtd
		INNER JOIN data_template_rrd AS dtr
		ON dtd.local_data_id = dtr.local_data_id
		INNER JOIN graph_templates_item AS gti
		ON gti.task_item_id = dtr.id
		WHERE dtd.local_data_id = ?
		AND gti.local_graph_id NOT IN (SELECT local_graph_id FROM aggregate_graphs)',
		array(get_request_var('rrd')));

	$start	= strtotime($report_data['start_date']);
	$end	= strtotime($report_data['end_date'] . ' 23:59:59');

	header('Location: ' . $config['url_path'] . "graph.php?action=zoom&local_graph_id=$local_graph_id&rra_id=0&graph_start=$start&graph_end=$end");
	exit;
}

function plugin_reportit_graph($graph_id, $graph_data) {
	global $config;

	$content = '';

	$xid = substr(md5($graph_id), 0, 7);

	$labels = array();
	$values = array();

	$content  = '<div id="treemap_' . $xid. '"></div>';
	$content .= '<script type="text/javascript">';
	$content .= 'treemap_' . $xid . ' = bb.generate({';
	$content .= ' tile: "dice",';
	$content .= ' bindto: "#treemap_' . $xid . '",';

	$content .= ' size: {';
	$content .= '  height: 400,';
	$content .= '  width: 800';
	$content .= ' },';

	$content .= ' data: {';
	$content .= '  columns: [';

	foreach ($graph_data as $key => $value) {
		$content .= '["' . $key . '", ' . $value . '],';
	}

	$content .= '  ],';
	$content .= '  type: "treemap",';
	$content .= '  labels: {';
	$content .= '    position: { x: 0, y: 15},';
	$content .= '    colors: "#fff"';
	$content .= '  }';
	$content .= '  },';

	$content .= '  treemap: {';
	$content .= '    label: {';
	$content .= '      threshold: 0.03, show: false,';
	$content .= '    }';
	$content .= '  },';

	/**
	 * correct touch issue with FireFox
	 * Billboard.js issue #3854 on GitHub
	 */
	$content .= '  interaction: {inputType: {mouse: true, touch:false}}';

	$content .= '});';
	$content .= '</script>';

	return $content;
}

