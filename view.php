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

$guest_account = true;

include_once('./include/auth.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_export.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/const_view.php');

set_default_action();

switch (get_request_var('action')) {
	case 'show_report':
		general_header();
		show_report();
		bottom_footer();
		break;
	case 'show_graphs':
		general_header();
		show_graphs();
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
		$sql_where = 'WHERE ' . $table . ".name_cache LIKE '%" . get_request_var('filter') . "%'";
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
		? get_prepared_report_data(get_request_var('id'),'export', $sql_where)
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
			'default' => 'description',
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

	validate_store_request_vars($filters, 'sess_cc_view');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = 'WHERE a.last_run!=0';

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var('filter'))) {
		$sql_where .= " AND a.description LIKE '%" . get_request_var('filter') . "%'";
	}

	if (get_request_var('type') == '-1') {
		$sql_where .= ' AND a.public=1';
	} elseif (get_request_var('type') == '0') {
		$sql_where .= ' AND a.user_id=' . $myId;
	}

	$total_rows = db_fetch_cell("SELECT COUNT(a.id)
		FROM reportit_reports AS a
		$sql_where");

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$report_list = db_fetch_assoc("SELECT a.*, b.description AS template_description
		FROM reportit_reports AS a
		INNER JOIN reportit_templates AS b
		ON b.id = a.template_id
		$sql_where
		$sql_order
		$sql_limit");

	$nav = html_nav_bar('cc_view.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Reports'), 'page', 'main');

	/* start with HTML output */
	html_start_box(__('Reports [%s]', $total_rows), '100%', '', '2', 'center', '');

	?>
	<tr class='odd'>
		<td>
		<form id='form_report' method='get'>
			<table class='fitlerTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Type');?>
					</td>
					<td>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('Public Reports');?></option>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('My Reports');?></option>
						</select>
					</td>
					<td>
						<?php print __('Reports');?>
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
						<input id='refresh' type='submit' value='<?php print __('Go');?>'>
					</td>
					<td>
						<input id='clear' type='button' value='<?php print __('Clear');?>'>
					</td>
				</tr>
			</table>
		</form>
		<script type='text/javascript'>
		function applyFilter() {
			strURL  = 'cc_view.php?action=show_report&header=false';
			strURL += '&filter='+escape($('#filter').val());
			strURL += '&type='+$('#type').val();
			strURL += '&rows='+$('#rows').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL = 'cc_view.php?action=show_report&clear=1&header=false';
			loadPageNoHeader(strURL);
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

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	$desc_array = array(
		'description'          => array('display' => __('Description'), 'align' => 'left', 'sort' => 'ASC'),
		'user_id'              => array('display' => __('Owner'),       'align' => 'left', 'sort' => 'ASC'),
		'template_description' => array('display' => __('Template'),    'align' => 'left', 'sort' => 'ASC'),
		'nosort0'              => array('display' => __('Period %s from - to', $tmz),       'align' => 'right'),
		'nosort1'              => array('display' => __('Last run %s / Runtime [s]', $tmz), 'align' => 'right')
	);

	html_header_sort($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	// Build report list
	if (sizeof($report_list)) {
		foreach ($report_list as $report) {
			$ownerId = $report['user_id'];

			form_alternate_row();

			print '<td><a class="linkEditMain" href="cc_view.php?action=show_report&id=' . $report['id'] . '>' . $report['description'] . '</a></td>';
			print '<td>' . other_name($ownerId) . '</td>';
			print '<td>' . $report['template_description'] . '</td>';
			print '<td class="right">' . (date(config_date_format(), strtotime($report['start_date'])) . ' - ' . date(config_date_format(), strtotime($report['end_date']))) . '</td>';

			list($date, $time) = explode(' ', $report['last_run']);

			print '<td class="right">' . (date(config_date_format(), strtotime($date)) . '&nbsp;' . $time . '&nbsp;&nbsp;/&nbsp;' . $report['runtime']) . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Reports Found') . '</em></td></tr>';
	}

	html_end_box();

	if (sizeof($report_list)) {
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
			)
	);

	validate_store_request_vars($filters, 'sess_cc_show_' . $id);
	/* ================= input validation ================= */

	return $id;
}

function show_report() {
	global $config, $search, $t_limit, $add_info, $export_formats;

	$columns         = 1;
	$limitation      = 0;
	$num_of_sets     = 0;
	$sql_where       = '';
	$subhead         = '';
	$include_mea     = '';
	$cache_id        = '';
	$table           = '';
	$measurands      = array();
	$ds_description  = array();
	$report_summary  = array();
	$archive         = array();
	$additional      = array();
	$report_ds_alias = array();

	$id = validate_report_vars();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	/* ==================== checkpoint ==================== */
	my_report(get_request_var('id'), true);
	$session_max_rows = get_valid_max_rows();
	/* ==================================================== */

	/* form the 'where' clause for our main sql query */
	$table = (get_request_var('archive') != -1)? 'a' : 'c';

	if (get_request_var('filter') != '') {
		$sql_where = 'WHERE ' . $table . ".name_cache LIKE '%" . get_request_var('filter') . "%'";
	}else{
		$sql_where = '';
	}
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
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
          ? get_prepared_report_data(get_request_var('id'),'view', $sql_where)
          : get_prepared_archive_data($cache_id, 'view', $sql_where);

	/* get total number of rows (data items) */
	$source = (get_request_var('archive') != -1)
		? 'reportit_tmp_' . get_request_var('id') . '_' . get_request_var('archive') . ' AS a'
		: 'reportit_results_' . get_request_var('id') . ' AS a'.
		  ' INNER JOIN data_template_data AS c'.
		  ' ON c.local_data_id = a.id';

	$total_rows = db_fetch_cell("SELECT COUNT(a.id)
		FROM $source
		$sql_where");

	$report_ds_alias = $data['report_ds_alias'];
	$report_data     = $data['report_data'];
	$report_results  = $data['report_results'];
	$mea             = $data['report_measurands'];
	$report_header   = $report_data['description'];

	/* create a report summary */
	if (get_request_var('summary')) {
		$report_summary[1][__('Title')]   = $report_data['description'];
		$report_summary[1][__('Runtime')] = $report_data['runtime'] . 's';

		$report_summary[2][__('Owner')]              = $report_data['owner'];
		$report_summary[2][__('Sliding Time Frame')] = ($report_data['sliding'] == 0) ? 'disabled' : 'enabled (' . strtolower($report_data['preset_timespan']) .')';

		$report_summary[3][__('Last Run')]  = $report_data['last_run'];
		$report_summary[3][__('Scheduler')] = ($report_data['scheduled'] == 0) ? 'disabled' : 'enabled (' . $report_data['frequency'] . ')';

		$report_summary[4][__('Period')]                  = $report_data['start_date'] . ' - ' . $report_data['end_date'];
		$report_summary[4][__('Auto Generated RRD list')] = ($report_data['autorrdlist'] == 0)? 'disabled' : 'enabled';
	}

	/* extract result description */
	list($rs_description, $count_rs) = explode('-', $report_data['rs_def']);
	$rs_description = ($rs_description == '') ? false : explode('|', $rs_description);

	if ($rs_description !== false) {
		foreach ($rs_description as $key => $id) {
			if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
				$count_rs--;
				unset($rs_description[$key]);
			} else {
				if (get_request_var('data_source') != -2)
					$measurands[$id] = $mea[$id]['abbreviation'];
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
		$ov_description 	= ($ov_description == '') ? false : explode('|', $ov_description);
		if ($ov_description !== false) {
			foreach ($ov_description as $key => $id) {
				if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
					$count_ov--;
					unset($ov_description[$key]);
				} elseif (get_request_var('data_source') == -1 || get_request_var('data_source') == -2) {
					$measurands[$id] = $mea[$id]['abbreviation'];
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

	/* extract datasource description */
	if ($count_rs > 0) {
		$ds_description = explode('|', $report_data['ds_description']);
		$columns += sizeof($ds_description)*$count_rs;
	}

	if ($count_ov > 0) {
		$ds_description[-2] = 'overall';
		$columns += $count_ov;
	}

	/* save all data source names for the drop down menue.
	if available use the data source alias instead of the internal names */
	$data_sources = $ds_description;
	foreach ($data_sources as $key => $value) {
		if (is_array($report_ds_alias) && array_key_exists($value, $report_ds_alias) && $report_ds_alias[$value] != '')
			$data_sources[$key] = $report_ds_alias[$value];
	}

	/* filter by data source */
	if (get_request_var('data_source') != -1) {
		$ds_description = array($ds_description[get_request_var('data_source')]);
	}

	$nav = html_nav_bar('cc_view.php?action=show_report&id=' . get_request_var('id'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('Reports'), 'page', 'main');

	/* graph view */
	$link = (read_config_option('reportit_graph') == 'on')? './cc_view.php?action=show_graphs&id=' . get_request_var('id') : '';

	/* start HTML output */
	ob_start();

	html_custom_header_box($report_header, false, $link, '<img src="./images/bar.gif" title="' . __('Graph View') . '">');

	?>
	<tr class='odd'>
		<td>
		<form name='form_report' method='get'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input id='filter' type='text' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Data Source');?>
					</td>
					<td>
						<select id='data_source'>
							<option value='-1'<?php if (get_request_var('data_source') == '-1') {?> selected<?php }?>><?php print __('Any');?></option>
							<?php
							if (sizeof($ds_description)) {
								foreach ($data_sources as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('data_source') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Measurand');?>
					</td>
					<td>
						<select id='measurand'>
							<option value='-1'<?php if (get_request_var('measurand') == '-1') {?> selected<?php }?>><?php print __('Any');?></option>
							<?php
							if (sizeof($measurands)) {
								foreach ($measurands as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('measurand') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input id='subhead' type='checkbox'<?php print (get_request_var('subhead') == 1) ? ' checked':'';?>>
					</td>
					<td>
						<label for='subhead'><?php print __('Show Subheads');?>
					</td>
					<td>
						<input id='summary' type='checkbox'<?php print (get_request_var('summary') == 1) ? ' checked':'';?>>
					</td>
					<td>
						<label for='summary'><?php print __('Summary');?>
					</td>
					<td>
						<input id='refresh' type='submit' value='<?php print __('Go');?>'>
					</td>
					<td>
						<input id='clear' type='submit' value='<?php print __('Clear');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Additional');?>
					</td>
					<td>
						<select id='info'>
							<?php
							foreach ($add_info as $key => $value) {
							    print "<option value='" . $key . "'"; if (get_request_var('info') == $key) { print ' selected'; } print '>' . $value[0] . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Items');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							foreach ($item_rows as $key => $value) {
							    print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
							?>
						</select>
					</td>
					<?php if ($archive != false) {?>
					<td>
						<?php print __('Archive');?>
					</td>
					<td>
						<select id='archive'>
							<option value='-1'<?php if (get_request_var('archive') == '-1') {?> selected<?php }?>><?php print __('Current');?></option>
							<?php
							if (sizeof($archive)) {
								foreach ($archive as $key => $value) {
								    print "<option value='" . $key . "'"; if (get_request_var('archive') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php } else { ?>
					<input id='archive' type='hidden' value='0'>
					<?php } ?>
				</tr>
			</table>
		</td>
		<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
		</form>
		<script type='text/javascript'>
		function applyFilter() {
			strURL  = 'cc_view.php?action=show_report&header=false';
			strURL += '&filter='+escape($('#filter').val());
			strURL += '&info='+$('#info').val();
			strURL += '&rows='+$('#rows').val();
			strURL += '&measurand='+$('#measurand').val();
			strURL += '&data_source='+$('#data_source').val();
			strURL += '&archive='+$('#archive').val();
			strURL += '&summary='+$('#summary').is(':checked');
			strURL += '&subhead='+$('#subhead').is(':checked');
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL = 'cc_view.php?action=show_report&clear=1&header=false';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#refresh').click(function() {
				applyFilter();
			});

			$('#info, #rows, #measurand, #data_source, #archive, #summary, #subhead').change(function() {
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

	if (get_request_var('summary')) {
		html_graph_start_box(1, false);
		foreach ($report_summary as $array) {
			print '<tr>';
			foreach ($array as $key => $value) {
				print "<td><b>$key:</b></td></td><td align='left'>$value</td>";
			}
			print '</tr>';
		}
		html_graph_end_box();
		print '<br>';
	}

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	/* print categories */
	print '<tr><td class="even"></td>';
	foreach ($ds_description as $description) {
		$counter = ($description != 'overall') ? $count_rs : $count_ov;
		if (is_array($report_ds_alias) && array_key_exists($description, $report_ds_alias) && $report_ds_alias[$description] != '') {
				$description = $report_ds_alias[$description];
		}
		print "<td colspan='$counter' height='10' class='even'>$description</td>";
	}
	print '</tr>';

	/* print table header */
	$display_text = array(
		'name_cache' => array('display' => __('Data Description'), 'align' => 'left', 'sort' => 'ASC'),
	);

	foreach ($ds_description as $datasource) {
		$name	= ($datasource != 'overall') ? $rs_description : $ov_description;
		if ($name !== false) {
			foreach ($name as $id) {
				$var	= ($datasource != 'overall') ? $datasource . '__' . $id : 'spanned__' . $id;
				$title 	= $mea[$id]['description'];

				if ($mea[$id]['visible']) {
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

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	/* Set preconditions */
	if (sizeof($report_results)) {
		foreach ($report_results as $result) {
			form_alternate_row();

			print '<td>
				<a class="linkEditMain" href="cc_view.php?action=show_graph_overview&id=' . get_request_var('id') . '&rrd=' . $result['id'] . '&cache=' . get_request_var('archive') . '">' . $result['name_cache'] . '</a>';

			if (get_request_var('subhead') == 1) {
				$replace = array ($result['start_time'], $result['end_time'], $result['timezone'], $result['start_day'], $result['end_day']);
				$subhead = str_replace($search, $replace, $result['description']);
				print '<br>' . $subhead;
			}
			print '</td>';

			foreach ($ds_description as $datasource) {
				$name = ($datasource != 'overall') ? $rs_description : $ov_description;

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

					print '<td class="right">';
					print get_unit($value, $rounding, $data_type, $data_precision);
					print '</td>';
				}
			}
		}
	} else {
		print "<tr><td colspan='" . sizeof($display_text) . "'><em>" . __('No Data Items') . "</em></td></tr>";
	}

	/* show additional informations if requested */
	switch (get_request_var('info')) {
	case '-2':
		break;
	case '-1':
		print '<tr></tr>';
		if (sizeof($additional)) {
			for($a=1; $a<5; $a++) {
				form_alternate_row();
				$description = $add_info[$a][0];
				$calc_fct    = $add_info[$a][1];

				print '<td><strong>' . $description . '</strong></td>';
				foreach ($additional as $array){
					print '<td class="right">' . get_unit($calc_fct($array['values']), $array['rounding'], $array['data_type'], $array['data_precision']) . '</td>';
				}
			}
		}

		break;
	default:
		print '<tr></tr>';
		if (sizeof($additional)) {
			form_alternate_row();
			$description = $add_info[get_request_var('info')][0];
			$calc_fct    = $add_info[get_request_var('info')][1];

			print '<td><strong>' . $description . '</strong></td>';
			foreach ($additional as $array){
					print '<td class="right">' . get_unit($calc_fct($array['values']), $array['rounding'], $array['data_type'], $array['data_precision']) . '</td>';
			}
		}

		break;
	}

	html_end_box();

	if (sizeof($report_results)) {
		print $nav;
	}

	print '<form name="custom_dropdown" method="post">';
	draw_actions_dropdown($export_formats, 'cc_view.php', 'single_export');
	print '</form>';

	ob_end_flush();
}

function show_graph_overview() {
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
	$report_data	= $data['report_data'];

	$local_graph_id = db_fetch_cell_prepared('SELECT DISTINCT c.local_graph_id
		FROM data_template_data AS a
		INNER JOIN data_template_rrd AS b
		ON b.local_data_id = a.local_data_id
		INNER JOIN graph_templates_item AS c
		ON c.task_item_id = b.id
		WHERE a.local_data_id = ?',
		array(get_request_var('rrd')));

	$start	= strtotime($report_data['start_date']);
	$end	= strtotime($report_data['end_date'] . ' 23:59:59');

	header("Location: ../../graph.php?header=false&action=zoom&local_graph_id=$local_graph_id&rra_id=0&graph_start=$start&graph_end=$end");
}

function graphs_filter($ds_description, $measurands, $graphs, $archive) {
	global $item_rows;

	?>
	<tr class='odd'>
		<form id='form_graphs' method='get' action='cc_view.php?action=show_graphs'>
		<td>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Data Source');?>
					</td>
					<td>
						<select id='data_source'>
							<option value='-1'><?php print __('Any');?></option>
							<?php
							if (sizeof($ds_description)) {
								foreach ($data_sources as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('data_source') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Measurand');?>
					</td>
					<td>
						<select id='measurang'>
							<option value='-1'><?php print __('Any');?></option>
							<?php
							if (sizeof($measurands)) {
								foreach ($measurands as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('measurand') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input id='refresh' type='submit' value='<?php print __('Go');?>'>
					</td>
					<td>
						<input id='clear' type='submit' value='<?php print __('Clear');?>'>
					</td>
					<td>
						<input id='summary' type='checkbox' <?php (get_request_var('summary') != '' ? print 'checked':'');?>>
					</td>
					<td>
						<label for='summary'><?php print __('Summary');?></label>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Type');?>
					</td>
					<td>
						<select id='type'>
							<?php
							foreach ($graphs as $key => $value) {
							    print "<option value='" . $key . "'"; if (get_request_var('type') == $key) { print ' selected'; } print '>' . title_trim($value, 40) . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Graphs');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							foreach ($item_rows as $key => $value) {
							    print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
							?>
						</select>
					</td>
					<?php if ($archive != false) { ?>
					<td>
						<?php print __('Archive');?>
					</td>
					<td>
						<select id='archive'>
							<option value='-1'<?php if (get_request_var('archive') == '-1') {?> selected<?php }?>><?php print __('Current');?></option>
							<?php
							if (sizeof($archive)) {
								foreach ($archive as $key => $value) {
								    print "<option value='" . $key . "'"; if (get_request_var('archive') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					<?php } ?>
				</tr>
			</table>
		</td>
		<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
		</form>
		<script type='text/javascript'>
		function applyFilter() {
			strURL  = 'cc_view.php?action=show_graphs&header=false';
			strURL += '&filter='+escape($('#filter').val());
			strURL += '&rows='+$('#rows').val();
			strURL += '&measurand='+$('#measurand').val();
			strURL += '&data_source='+$('#data_source').val();
			strURL += '&archive='+$('#archive').val();
			strURL += '&summary='+$('#summary').is(':checked');
			strURL += '&type='+$('#type').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL = 'cc_view.php?action=show_graphs&clear=1&header=false';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#type, #rows, #measurand, #data_source, #archive, #summary').change(function() {
				applyFilter();
			});

			$('#clear').click(function() {
				clearFilter();
			});

			$('#form_graphs').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});
		});
		</script>
	</tr>
	<?php
}

function show_graphs() {
	global $config, $graphs, $limit;

	$columns         = 1;
	$archive         = array();
	$sql_where       = '';
	$description     = '';
	$report_ds_alias = array();

	/* ================= Input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	/* if the user pushed the 'clear' button */
	$id = (read_graph_config_option('reportit_view_filter') == 'on') ? get_request_var('id') : '';

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
		'summary' => array(
			'filter' => FILTER_CALLBACK,
			'default' => false,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_graph_config_option('reportit_g_default')
			),
		'archive' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'measurand' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'data_source' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			)
	);

	validate_store_request_vars($filters, 'sess_cc_view1_' . $id);
	/* ================= input validation ================= */

	/* ==================== Checkpoint ==================== */
	my_report(get_request_var('id'), true);
	check_graph_support();
	/* ==================================================== */

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where .= " LIKE '%" . get_request_var('filter') . "%'";
	}

	/* get informations about the archive if it exists */
	$archive = info_xml_archive(get_request_var('id'));

	/* load report archive and fill up report cache if requested*/
	if (get_request_var('archive') != -1) {
		cache_xml_file(get_request_var('id'), get_request_var('archive'));
		$cache_id = get_request_var('id') . '_' . get_request_var('archive');
	}

	/* load report data */
	$data = (get_request_var('archive') == -1)
		? get_prepared_report_data(get_request_var('id'),'view', $sql_where)
		: get_prepared_archive_data($cache_id, 'view', $sql_where);

	$report_ds_alias = $data['report_ds_alias'];
	$report_data     = $data['report_data'];
	$mea             = $data['report_measurands'];
	$report_header   = $report_data['description'];

	/* create a report summary */
	if (get_request_var('summary')) {
		$report_summary[1]['Title']   = $report_data['description'];
		$report_summary[1]['Runtime'] = $report_data['runtime'] . 's';

		$report_summary[2]['Owner']              = $report_data['owner'];
		$report_summary[2]['Sliding Time Frame'] = ($report_data['sliding'] == 0) ? 'disabled' : 'enabled (' . strtolower($report_data['preset_timespan']) .')';

		$report_summary[3]['Last Run']  = $report_data['last_run'];
		$report_summary[3]['Scheduler'] = ($report_data['scheduled'] == 0) ? 'disabled' : 'enabled (' . $report_data['frequency'] . ')';
		$report_summary[4]['Period']                  = $report_data['start_date'] . ' - ' . $report_data['end_date'];
		$report_summary[4]['Auto Generated RRD list'] = ($report_data['autorrdlist'] == 0)? 'disabled' : 'enabled';
	}

	/* extract result description */
	list($rs_description, $count_rs) = explode('-', $report_data['rs_def']);
	$rs_description = ($rs_description == '') ? false : explode('|', $rs_description);
	if ($rs_description !== false) {
		foreach ($rs_description as $key => $id) {
			if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
				$count_rs--;
				unset($rs_description[$key]);
			} else {
				if (get_request_var('data_source') != -2)
					$measurands[$id] = $mea[$id]['abbreviation'];
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
		$ov_description 	= ($ov_description == '') ? false : explode('|', $ov_description);
		if ($ov_description !== false) {
			foreach ($ov_description as $key => $id) {
				if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
					$count_ov--;
					unset($ov_description[$key]);
				} else {
					if (get_request_var('data_source') == -1 || get_request_var('data_source') == -2)
						$measurands[$id] = $mea[$id]['abbreviation'];
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

	/* extract datasource description */
	if ($count_rs > 0) $ds_description = explode('|', $report_data['ds_description']);
	if ($count_ov > 0) $ds_description[-2] = 'overall';

	/* save all data source name for drop down menue */
	$data_sources = $ds_description;
	foreach ($data_sources as $key => $value) {
		if (is_array($report_ds_alias) && array_key_exists($value, $report_ds_alias) && $report_ds_alias[$value] != '')
			$data_sources[$key] = $report_ds_alias[$value];
	}

	/* filter by data source */
	if (get_request_var('data_source') != -1) {
		$ds_description = array($ds_description[get_request_var('data_source')]);
	}

	$order = (get_request_var('rows') < 0)? 'DESC' : 'ASC';
	$limitation = abs(get_request_var('rows'))*5;

	//----- Start HTML output -----
	ob_start();

	html_start_box($report_header, '100%', '', '2', 'center', '');
	graphs_filter($ds_description, $measurands, $graphs, $archive);
	html_end_box();

	if (get_request_var('summary')) {
		html_graph_start_box(1, false);
		foreach ($report_summary as $array) {
			print '<tr>';
			foreach ($array as $key => $value) {
				print "<td><b>$key:</b></td></td><td align='left'>$value</td>";
			}
			print '</tr>';
		}
		html_graph_end_box();
		print '<br>';
	}

	html_graph_start_box(3, false);
	foreach ($ds_description as $datasource) {
		$description = (is_array($report_ds_alias) && array_key_exists($datasource, $report_ds_alias))
			? ($report_ds_alias[$datasource] != '') ? $report_ds_alias[$datasource]
			: $datasource : $datasource;

		print "<tr class='odd'><td colspan='3' class='textHeaderDark'>" . __('Data Source:') . " $description</td></tr>";

		$name	= ($datasource != 'overall') ? $rs_description : $ov_description;
		if ($name !== false) {
			foreach ($name as $id) {
				$var			= ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
				$title 			= $mea[$id]['description'];
				$rounding		= $mea[$id]['rounding'];
				$unit			= $mea[$id]['unit'];
				$rounding		= $mea[$id]['rounding'];
				$data_type		= $mea[$id]['data_type'];
				$data_precision = $mea[$id]['data_precision'];
				$suffix			= " ORDER BY a.$var $order LIMIT 0, $limitation";

				if ($mea[$id]['visible']) {
					if (get_request_var('archive') == -1) {
						$sql = 	"SELECT a.*, b.*, c.name_cache
							FROM reportit_results_" . get_request_var('id') . " AS a
							INNER JOIN reportit_data_items AS b
							ON b.id = a.id
							AND b.report_id = " . get_request_var('id') . "
							INNER JOIN data_template_data AS c
							ON c.local_data_id = a.id
							WHERE c.name_cache
							$sql_where
							$suffix";
					} else {
						$sql =	"SELECT *
							FROM reportit_tmp_" . get_request_var('id') . "_" . get_request_var('archive') . " AS a
							WHERE a.name_cache
							$sql_where
							$suffix";
					}

					$data = db_fetch_assoc($sql);

					print "<tr bgcolor='#a9b7cb'><td colspan='3' class='textHeaderDark'><strong>Measurand:</strong> $title ({$mea[$id]['abbreviation']})</td></tr>";
					//print "<tr valign='top'><td colspan='2'><a href='./cc_graphs.php?id={get_request_var('id')}&source=$var' style='border: 1px solid #bbbbbb;' alt='$title ({$mea[$id]['abbreviation']})'>hallo</a></td>";
					print "<tr valign='top'><td colspan='2'><img src='./cc_graphs.php?id={get_request_var('id')}&source=$var' style='border: 1px solid #bbbbbb;' alt='$title ({$mea[$id]['abbreviation']})'></td>";
					print "<td colspan='1' width='100%'>";

					if (count($data)) {
						html_start_box('', '100%', '', '2', 'center', '');

						html_header(array(
							__('Pos.'),
							__('Description'),
							__('Results [%s]', $unit)
						));

						foreach ($data as $item){
							$value	= $item[$var];
							$title 	= "{$item['start_day']}&nbsp;-&nbsp;{$item['end_day']}&nbsp;&#10;{$item['start_time']}&nbsp;-&nbsp;{$item['end_time']} {$item['timezone']}";

							form_alternate_row();

							print "<td title='$title'>$i</td>";
							print "<td title='$title'>
										<a class='linkEditMain' href='cc_view.php?action=show_graph_overview&id=" . get_request_var('id') . "&rrd=" . $item['id'] . "&cache=" . get_request_var('archive') . "'>" . $item['name_cache'] . "
								</a>
							</td>";

							print "<td title='$title' align='right'>";

							if ($value == NULL) {
								print "NA";
							} elseif ($value == 0) {
								print $value;
							} else {
								print get_unit($value, $rounding, $data_type, $data_precision);
							}

							print '</td>';
						}

						html_end_box();
					}

					print '</td></tr>';
				}
			}
		}
	}

	html_graph_end_box();
	ob_end_flush();
}

function show_export_wizard($new=false){
	global $config, $export_formats;

	/* start-up sequence */
	if ($new !== false) {
		$_SESSION['reportit']['export'] = array();

		/* save all report ids in $_SESSION */
		foreach ($_POST as $key => $value) {
			if (strstr($key, 'chk_')) {
				$id = substr($key, 4);
				my_report($id, true);
				$_SESSION['reportit']['export'][$id] = array();
				$_SESSION['reportit']['export'][$id]['ids'] = array();
			}
		}
	}

	$report_ids = $_SESSION['reportit']['export'];

	html_wizard_header('Export', 'cc_view.php');

	print "<tr>
		<td class='textArea'>
			<p>" . __('Choose a template your report should depends on.') . "</p><br>
			<b>" . __('Available report templates') . "</b><br>";

	form_dropdown('template', $export_formats, '', '', '', '', '');

	print "</td>
		</tr>";

	$display_text = array(
		array('display' => __('Description'),          'align' => 'left'),
		array('display' => __('Instances: available'), 'align' => 'right'),
		array('display' => __('Instances: selected'),  'align' => 'right')
	);

	html_header($display_text, 2);

	$ids = '';

	foreach ($report_ids as $key => $value) {
		$ids .= "$key,";
	}

	$ids = substr($ids, 0, strlen($ids)-1);

	$report = db_fetch_assoc("SELECT id, description, scheduled, autoarchive
		FROM reportit_reports
		WHERE id IN ($ids)");

	if (sizeof($reports)) {
		foreach ($reports as $report) {
			form_alternate_row();

			print '<td>' . $report['description'] . '</td>';

			$archive = info_xml_archive($report['id']);

			if ($archive) {
				print '<td class="right">' . sizeof($archive) . '</td>';
			} else {
				print '<td class="right">1</td>';
			}

			print '<td class="right">' . sizeof($_SESSION['reportit']['export'][$id]['ids']) . '</td>';

			print '<td class="right">'
				."<a href=\"cc_reports.php?action=remove&id=$key\">"
				.'<img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Delete"></a></td>';

			form_end_row();
		}
	} else {
		print "<tr><td colspan='5'><em>" . __('No Reports Found') . "</em></td></tr>";
	}

	html_end_box();

	html_form_button('cc_view.php', 'create', 'id', false, '60%');
}

