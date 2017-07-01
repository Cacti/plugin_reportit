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
		top_header();
		show_report();
		bottom_footer();
		break;
	case 'show_graphs':
		top_header();
		show_graphs();
		bottom_footer();
		break;
	case 'show_graph_overview':
		show_graph_overview();
		break;
	case 'export':
		top_header();
		show_export_wizard(true);
		bottom_footer();
		break;
	case 'actions':
		export();
		break;
	default:
		top_header();
		standard();
		bottom_footer();
		break;
}

function export() {
	global $config, $export_formats;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('page');
	get_filter_request_var('data_source');
	get_filter_request_var('measurand');
	get_filter_request_var('limit');
	get_filter_request_var('archive');
	get_filter_request_var('subhead');
	get_filter_request_var('summary');
	input_validate_input_key(get_request_var('drp_action'), $export_formats);
	/* ==================================================== */

	/* clean up search string */
	if (isset_request_var('filter')) {
		set_request_var('filter', sanitize_search_string(get_request_var('filter')));
	}

	/* clean up sort_column */
	if (isset_request_var('sort')) {
		set_request_var('sort', sanitize_search_string(get_request_var('sort')));
	}

	/* clean up sort_direction string */
	if (isset_request_var('mode')) {
		set_request_var('mode', sanitize_search_string(get_request_var('mode')));
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	$id = (read_graph_config_option('reportit_view_filter') == 'on') ? get_request_var('id') : '';
	load_current_session_value("page", "sess_reportit_show_{$id}_current_page", "1");
	load_current_session_value("sort", "sess_reportit_show_{$id}_sort", "a.id");
	load_current_session_value("mode", "sess_reportit_show_{$id}_mode", "ASC");
	load_current_session_value("data_source", "sess_reportit_show_{$id}_data_source", "-1");
	load_current_session_value("measurand", "sess_reportit_show_{$id}_measurand", "-1");
	load_current_session_value("filter", "sess_reportit_show_{$id}_filter", "");
	load_current_session_value("info", "sess_reportit_show_{$id}_info", "-2");
	load_current_session_value("limit", "sess_reportit_show_{$id}_limit", "0");
	load_current_session_value("archive", "sess_reportit_show_{$id}_archive", "-1");
	load_current_session_value("subhead", "sess_reportit_show_{$id}_subhead", "0");
	load_current_session_value("summary", "sess_reportit_show_{$id}_summary", "0");

	/* form the 'where' clause for our main sql query */
	$table = (get_request_var('archive') != -1)? 'a' : 'c';
	$affix = "WHERE {$table}.name_cache LIKE '%%{get_request_var('filter')}%%'".
		" ORDER BY " . get_request_var('sort') . " " . get_request_var('mode');

	/* limit the number of rows */
	$limitation = get_request_var('limit')*(-5);
	if ($limitation > 0 ) $affix .=" LIMIT 0," . $limitation;

	/* get informations about the archive if it exists */
	$archive = info_xml_archive(get_request_var('id'));

	/* load report archive and fill up report cache if requested*/
	if (get_request_var('archive') != -1) {
		cache_xml_file(get_request_var('id'), get_request_var('archive'));
		$cache_id = get_request_var('id') . '_' . get_request_var('archive');
	}

	/* load report data */
	$data = (get_request_var('archive') == -1)
		? get_prepared_report_data(get_request_var('id'),'export', $affix)
		: get_prepared_archive_data($cache_id, 'export', $affix);

	/* call export function */
	$export_function = "export_to_" . get_request_var('drp_action');
	$output	= $export_function($data);

	$content_type = strtolower(get_request_var('drp_action'));
	if (get_request_var('drp_action') == 'SML') {
		set_request_var('drp_action', 'xml');
		$content_type = 'vnd.ms-excel';
	}

	/* create filename */
	$filename = str_replace("<report_id>", get_request_var('id'), read_config_option('reportit_exp_filename') . ".{get_request_var('drp_action')}");
	$filename = strtolower($filename);

	/* configure data header */
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Type: application/$content_type");
	header("Content-Disposition: attachment; filename=\"" . $filename . "\"");

	print $output;
}


function standard() {
	global $config, $link_array, $item_rows;

	$myId = my_id();
	$tmz  = (read_config_option('reportit_show_tmz') == 'on') ? '('.date('T').')' : '';

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
			'default' => 'id',
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

	$sql_where = ' WHERE a.last_run!=0';

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var('filter'))) {
		$sql_where .= " AND a.description LIKE '%" . get_request_var('filter') . "%'";
	}

	if (get_request_var('type') == '-1') {
		$sql_where .= ' AND a.public=1';
	}elseif (get_request_var('type') == '0') {
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
	html_start_box(__('Reports [%s]', $total_rows), '100%', '', '2', 'center', "");

	?>
	<tr class='odd'>
		<form id='form_view' method='get'>
		<td>
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
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('Public reports');?></option>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('My reports');?></option>
						</select>
					</td>
					<td>
						<?php print __('VDEFs');?>
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
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	$desc_array = array(
		__('Description'),
		__('Owner'),
		__('Template'),
		__('Period %s from - to', $tmz),
		__('Last run %s / Runtime [s]', $tmz)
	);

	html_header($desc_array);

	/* check version of Cacti -> necessary to support 0.8.6k and lower versions as well*/
	$new_version = check_cacti_version(14);

	$i = 0;

	// Build report list
	if (sizeof($report_list)) {
		foreach($report_list as $report) {
			$ownerId = $report['user_id'];

			form_alternate_row();

			print '<td><a class="linkEditMain" href="cc_view.php?action=show_report&id=' . $report['id'] . '>' . $report['description'] . '</a></td>';
			print '<td>' . other_name($ownerId) . '</td>';
			print '<td>' . $report['template_description'] . '</td>';
			print '<td>' . (date(config_date_format(), strtotime($report['start_date'])) . " - " . date(config_date_format(), strtotime($report['end_date']))) . '</td>';

			list($date, $time) = explode(' ', $report['last_run']);

			print '<td>' . (date(config_date_format(), strtotime($date)) . '&nbsp;' . $time . '&nbsp;&nbsp;/&nbsp;' . $report['runtime']) . '</td>';

			form_end_row();
		}
	}else {
		print '<tr><td colspan="5"><em>' . __('No reports') . '</em></td></tr>';
	}

	html_end_box();

	if (sizeof($report_list)) {
		print $nav;
	}
}

function show_report() {
	global $config, $search, $t_limit, $add_info, $export_formats;

	$columns		= 1;
	$limitation		= 0;
	$num_of_sets	= 0;
	$affix			= '';
	$subhead		= '';
	$include_mea	= '';
	$cache_id		= '';
	$table			= '';
	$measurands		= array();
	$ds_description	= array();
	$report_summary	= array();
	$archive		= array();
	$additional		= array();
	$report_ds_alias= array();

	/* ================= Input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('page');
	get_filter_request_var('data_source');
	get_filter_request_var('measurand');
	get_filter_request_var('limit');
	get_filter_request_var('archive');
	get_filter_request_var('subhead');
	get_filter_request_var('summary');
	/* ==================================================== */

	/* ==================== checkpoint ==================== */
	my_report(get_request_var('id'), TRUE);
	$session_max_rows = get_valid_max_rows();
	/* ==================================================== */

	/* clean up search string */
	if (isset_request_var('filter')) {
		set_request_var('filter', sanitize_search_string(get_request_var('filter')));
	}

	/* clean up sort_column */
	if (isset_request_var('sort')) {
		set_request_var('sort', sanitize_search_string(get_request_var('sort')));
	}

	/* clean up sort_direction string */
	if (isset_request_var('mode')) {
		set_request_var('mode', sanitize_search_string(get_request_var('mode')));
	}

	/* if the user pushed the 'clear' button */
	$id = (read_graph_config_option('reportit_view_filter') == 'on') ? get_request_var('id') : '';

	if (isset_request_var('clear_x')) {
		kill_session_var("sess_reportit_show_{$id}_current_page");
		kill_session_var("sess_reportit_show_{$id}_sort");
		kill_session_var("sess_reportit_show_{$id}_mode");
		kill_session_var("sess_reportit_show_{$id}_data_source");
		kill_session_var("sess_reportit_show_{$id}_measurand");
		kill_session_var("sess_reportit_show_{$id}_filter");
		kill_session_var("sess_reportit_show_{$id}_info");
		kill_session_var("sess_reportit_show_{$id}_limit");
		kill_session_var("sess_reportit_show_{$id}_archive");
		kill_session_var("sess_reportit_show_{$id}_subhead");
		kill_session_var("sess_reportit_show_{$id}_summary");

		unset_request_var('page');
		unset_request_var('sort');
		unset_request_var('mode');
		unset_request_var('data_source');
		unset_request_var('measurand');
		unset_request_var('filter');
		unset_request_var('info');
		unset_request_var('limit');
		unset_request_var('archive');
		unset_request_var('subhead');
		unset_request_var('summary');
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_reportit_show_{$id}_current_page", "1");
	load_current_session_value("sort", "sess_reportit_show_{$id}_sort", "a.id");
	load_current_session_value("mode", "sess_reportit_show_{$id}_mode", "ASC");
	load_current_session_value("data_source", "sess_reportit_show_{$id}_data_source", "-1");
	load_current_session_value("measurand", "sess_reportit_show_{$id}_measurand", "-1");
	load_current_session_value("filter", "sess_reportit_show_{$id}_filter", "");
	load_current_session_value("info", "sess_reportit_show_{$id}_info", "-2");
	load_current_session_value("limit", "sess_reportit_show_{$id}_limit", "0");
	load_current_session_value("archive", "sess_reportit_show_{$id}_archive", "-1");
	load_current_session_value("subhead", "sess_reportit_show_{$id}_subhead", "0");
	load_current_session_value("summary", "sess_reportit_show_{$id}_summary", "0");

	/* set up max number of rows */
	$num_of_rows = $session_max_rows;
	if (get_request_var('subhead')) $num_of_rows = floor(0.5*$num_of_rows);
	if (get_request_var('summary')) $num_of_rows -= 4;
	if ($num_of_rows <= 10) 	 $num_of_rows = 10;

	/* form the 'where' clause for our main sql query */
	$table = (get_request_var('archive') != -1)? 'a' : 'c';
	$affix = 	"WHERE {$table}.name_cache LIKE '%%{get_request_var('filter')}%%'".
				" ORDER BY " . get_request_var('sort') . " " . get_request_var('mode');

	$limitation = get_request_var('limit')*(-5);
	if ($limitation > 0 & $limitation < $num_of_rows) {
		$num_of_sets = $limitation;
		$end		 = $limitation;
	}else{
		$num_of_sets = $end = $num_of_rows;
		if ($limitation > 0 & $num_of_sets*(get_request_var('page')-1)+$end > $limitation)
			$end -= - $limitation + $num_of_sets*(get_request_var('page')-1)+$end;
	}
	$affix .=" LIMIT " . ($num_of_sets*(get_request_var('page')-1)) . "," . $end;


	/* get informations about the archive if it exists */
	$archive = info_xml_archive(get_request_var('id'));

	/* load report archive and fill up report cache if requested*/
	if (get_request_var('archive') != -1) {
		cache_xml_file(get_request_var('id'), get_request_var('archive'));
		$cache_id = get_request_var('id') . '_' . get_request_var('archive');
	}

	/* load report data */
	$data = (get_request_var('archive') == -1)
          ? get_prepared_report_data(get_request_var('id'),'view', $affix)
          : get_prepared_archive_data($cache_id, 'view', $affix);

	/* get total number of rows (data items) */
	$source = (get_request_var('archive') != -1)
				? 'reportit_tmp_' . get_request_var('id') . '_' . get_request_var('archive') . ' AS a'
				: 'reportit_results_' . get_request_var('id') . ' AS a'.
				  ' INNER JOIN data_template_data AS c'.
				  ' ON c.local_data_id = a.id';
	$sql = 	"SELECT COUNT(a.id) FROM $source".
			" WHERE {$table}.name_cache LIKE '%%{get_request_var('filter')}%%'";

	$total_rows = db_fetch_cell($sql);
	if ($total_rows > $limitation && $limitation > 0) $total_rows = $limitation;

	$report_ds_alias = $data['report_ds_alias'];
	$report_data	= $data['report_data'];
	$report_results	= $data['report_results'];
	$mea			= $data['report_measurands'];
	$report_header	= $report_data['description'];

	/* create a report summary */
	if (get_request_var('summary')) {
		$report_summary[1]['Title'] = $report_data['description'];
		$report_summary[1]['Runtime'] = $report_data['runtime'] . 's';
		$report_summary[2]['Owner'] = $report_data['owner'];
		$report_summary[2]['Sliding Time Frame'] = ($report_data['sliding'] == 0) ? 'disabled' : 'enabled (' . strtolower($report_data['preset_timespan']) .')';
		$report_summary[3]['Last Run'] = $report_data['last_run'];
		$report_summary[3]['Scheduler'] = ($report_data['scheduled'] == 0) ? 'disabled' : 'enabled (' . $report_data['frequency'] . ')';
		$report_summary[4]['Period'] = $report_data['start_date'] . " - " . $report_data['end_date'];
		$report_summary[4]['Auto Generated RRD list'] = ($report_data['autorrdlist'] == 0)? 'disabled' : 'enabled';
	}

	/* extract result description */
	list($rs_description, $count_rs) = explode('-', $report_data['rs_def']);
	$rs_description = ($rs_description == '') ? FALSE : explode('|', $rs_description);
	if ($rs_description !== FALSE) {
		foreach($rs_description as $key => $id) {
			if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
				$count_rs--;
				unset($rs_description[$key]);
			}else {
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
		$ov_description 	= ($ov_description == '') ? FALSE : explode('|', $ov_description);
		if ($ov_description !== FALSE) {
			foreach($ov_description as $key => $id) {
				if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
					$count_ov--;
					unset($ov_description[$key]);
				}else {
					If(get_request_var('data_source') == -1 || get_request_var('data_source') == -2)
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
		$ds_description 	= explode('|', $report_data['ds_description']);
		$columns += sizeof($ds_description)*$count_rs;
	}

	if ($count_ov > 0) {
		$ds_description[-2] = 'overall';
		$columns += $count_ov;
	}

	/* save all data source names for the drop down menue.
	if available use the data source alias instead of the internal names */
	$data_sources = $ds_description;
	foreach($data_sources as $key => $value) {
		if (is_array($report_ds_alias) && array_key_exists($value, $report_ds_alias) && $report_ds_alias[$value] != '')
			$data_sources[$key] = $report_ds_alias[$value];
	}

	/* filter by data source */
	if (get_request_var('data_source') != -1) {
		$ds_description = array($ds_description[get_request_var('data_source')]);
	}

	/* generate page list */
	$url_page_select = html_custom_page_list(get_request_var('page'), MAX_DISPLAY_PAGES, $num_of_rows, $total_rows, "cc_view.php?action=show_report&id={get_request_var('id')}");
	$nav = "<tr bgcolor='#6CA6CD' >
		<td colspan='" . $columns . "'>
			<table width='100%' cellspacing='0' cellpadding='0' border='0'>
				<tr>
					<td align='left'>
						<strong>&laquo; "; if (get_request_var('page') > 1) { $nav .= "<a style='color:FFFF00' href='cc_view.php?action=show_report&id=" . get_request_var('id') . "&page=" . (get_request_var('page')-1) . "'>"; } $nav .= "Previous"; if (get_request_var('page') > 1) { $nav .= "</a>"; } $nav .= "</strong>
					</td>\n
					<td align='center' class='textSubHeaderDark'>
						Showing Rows " . (($num_of_rows*(get_request_var('page')-1))+1) . " to " . ((($total_rows < $num_of_rows) || ($total_rows < ($num_of_rows*get_request_var('page')))) ? $total_rows : ($num_of_rows*get_request_var('page'))) . " of $total_rows [$url_page_select]
					</td>\n
					<td align='right'>
						<strong>"; if ((get_request_var('page') * $num_of_rows) < $total_rows) { $nav .= "<a style='color:yellow' href='cc_view.php?action=show_report&id=" . get_request_var('id') . "&page=" . (get_request_var('page')+1) . "'>"; } $nav .= "Next"; if ((get_request_var('page') * $num_of_rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &raquo;</strong>
					</td>\n
				</tr>
			</table>
		</td>
		</tr>\n";

	/* graph view */
	$link = (read_config_option('reportit_graph') == 'on')? "./cc_view.php?action=show_graphs&id={get_request_var('id')}" : "";

	/* start HTML output */
	ob_start();
	html_custom_header_box($report_header, false, $link, "<img src='./images/bar.gif' alt='Graph View' border='0' align='top'>");
	include(REPORTIT_BASE_PATH . '/lib_int/inc_report_table_filter_table.php');
	html_end_box();

	if (get_request_var('summary')) {
		html_graph_start_box(1, false);
		foreach($report_summary as $array) {
			echo "<tr>";
			foreach($array as $key => $value) {
				echo "<td><b>$key:</b></td></td><td align='left'>$value</td>";
			}
			echo "</tr>";
		}
		html_graph_end_box();
		echo "<br>";
	}

	html_report_start_box();

	print $nav;
	echo "<tr><td bgcolor='#E5E5E5'></td>";

	foreach($ds_description as $description) {
		$counter = ($description != 'overall') ? $count_rs : $count_ov;
		if (is_array($report_ds_alias) && array_key_exists($description, $report_ds_alias) && $report_ds_alias[$description] != '') {
				$description = $report_ds_alias[$description];
		}
		print "<th colspan='$counter' height='10' bgcolor='#E5E5E5'>$description</th>";
	}

	print "</tr><tr>
			<td class='textSubHeaderDark' align='left' valign='top'>
			<b><a class='textSubHeaderDark' href='cc_view.php?action=show_report&id={get_request_var('id')}&sort=name_cache&mode="
                                . ((get_request_var('sort') == 'name_cache' && get_request_var('mode') == 'ASC')
                                    ? "DESC'>"
                                    : "ASC'>") . "Data Description</a></b>
			<a title='ascending' href='cc_view.php?action=show_report&id={get_request_var('id')}&sort=name_cache&mode=ASC'>"
			. ((get_request_var('sort') == 'name_cache' && get_request_var('mode') == 'ASC')
                    ? "<img src='./images/red_arrow_up.gif' alt='ASC' border='0' align='absmiddle' title='arranged in ascending order'>"
                    : "<img src='./images/arrow_up.gif' alt='ASC' border='0' align='absmiddle' title='arrange in ascending order'>")
			. "</a><a title='descending' href='cc_view.php?action=show_report&id={get_request_var('id')}&sort=name_cache&mode=DESC'>"
			. ((get_request_var('sort') == 'name_cache' && get_request_var('mode') == 'DESC')
                    ? "<img src='./images/red_arrow_down.gif' alt='DESC' border='0' align='absmiddle' title='arranged in descending order'>"
                    : "<img src='./images/arrow_down.gif' alt='DESC' border='0' align='absmiddle' title='arrange in descending order'>")
			. "</a>
			</td>";

	foreach($ds_description as $datasource) {
		$name	= ($datasource != 'overall') ? $rs_description : $ov_description;
		if ($name !== FALSE) {
			foreach($name as $id) {
				$var	= ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
				$title 	= $mea[$id]['description'];

				if ($mea[$id]['visible']) {
					if (isset_request_var('mode')){
						$sortorder = '&sort_direction=' . get_request_var('mode');
					} else {
						$sortorder = '';
					}
					print "<td class='textSubHeaderDark' align='right'>
							<strong title='$title'>
                                <a class='textSubHeaderDark' href='cc_view.php?action=show_report&id={get_request_var('id')}&sort=$var&mode="
                                . ((get_request_var('sort') == $var && get_request_var('mode') == 'ASC')
                                    ? "DESC'>"
                                    : "ASC'>")
                                . "{$mea[$id]['abbreviation']}&nbsp;[{$mea[$id]['unit']}]</a>&nbsp;<a title='ascending' href='cc_view.php?action=show_report&id={get_request_var('id')}&sort=$var&mode=ASC'>"

							. ((get_request_var('sort') == $var && get_request_var('mode') == 'ASC')
                                    ? "<img src='./images/red_arrow_up.gif' alt='ASC' border='0' align='absmiddle' title='arranged in ascending order'>"
                                    : "<img src='./images/arrow_up.gif' alt='ASC' border='0' align='absmiddle' title='arrange in ascending order'>")
			.                "</a><a title='descending'  href='cc_view.php?action=show_report&id={get_request_var('id')}&sort=$var&mode=DESC'>"
							. ((get_request_var('sort') == $var && get_request_var('mode') == 'DESC')
                                    ? "<img src='./images/red_arrow_down.gif' alt='DESC' border='0' align='absmiddle' title='arranged in descending order'>"
                                    : "<img src='./images/arrow_down.gif' alt='DESC' border='0' align='absmiddle' title='arrange in descending order'>")
			                 . "</a>
							</strong>";
				}
			}
		}
	}
	echo "</tr>";
	/* Set preconditions */
	$i = 0;
	if (sizeof($report_results) > 0) {
		foreach($report_results as $result) {
			form_alternate_row();

			print "<td " . ((get_request_var('sort_column') == 'name_cache') ? "bgcolor='#FFFACD'>" : ">") ."
				<a class='linkEditMain' href='cc_view.php?action=show_graph_overview&id=" . get_request_var('id') . "&rrd=" . $result["id"] . "&cache=" . get_request_var('archive') . "'>
					{$result["name_cache"]}
				</a>";

			if (get_request_var('subhead') == 1) {
				$replace = array ($result['start_time'], $result['end_time'], $result['timezone'], $result['start_day'], $result['end_day']);
				$subhead = str_replace($search, $replace, $result['description']);
				print "<br>$subhead";
			}
			echo '</td>';

			foreach($ds_description as $datasource) {
				$name	= ($datasource != 'overall') ? $rs_description : $ov_description;

				foreach($name as $id) {
					$rounding	= $mea[$id]['rounding'];
					$data_type	= $mea[$id]['data_type'];
					$data_precision = $mea[$id]['data_precision'];
					$var		= ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
					$value		= $result[$var];
					$additional[$var]['values'][] = $value;
					$additional[$var]['rounding'] = $rounding;
					$additional[$var]['data_type'] = $data_type;
					$additional[$var]['data_precision'] = $data_precision;

					echo "<td align='right' " . ((get_request_var('sort') == $var) ? "bgcolor='#FFFACD'>" : ">");
					print get_unit($value, $rounding, $data_type, $data_precision);
					echo '</td>';
				}
			}
		}
	}else {
		print "<tr bgcolor='#F5F5F5'><td colspan='100'><em>No data items</em></td></tr>\n";
	}


	/* show additional informations if requested */
	switch (get_request_var('info')) {
		case '-2':
			break;
		case '-1':
			echo "<tr></tr>";
			if (sizeof($additional)>0) {
				for($a=1; $a<5; $a++) {
					form_alternate_row_color("FFFACD", "FFFACD", $i); $i++;
					$description = $add_info[$a][0];
					$calc_fct = $add_info[$a][1];

					print "<td><strong>$description</strong></td>";
					foreach($additional as $array){
							print "<td align='right'>" . get_unit($calc_fct($array['values']), $array['rounding'], $array['data_type'], $array['data_precision']) . "</td>";
					}
				}
			}
			break;
		default:
			echo "<tr></tr>";
			if (sizeof($additional)>0) {
				form_alternate_row_color("FFFACD", "FFFACD", $i); $i++;
				$description = $add_info[get_request_var('info')][0];
				$calc_fct = $add_info[get_request_var('info')][1];

				print "<td><strong>$description</strong></td>";
				foreach($additional as $array){
						print "<td align='right'>" . get_unit($calc_fct($array['values']), $array['rounding'], $array['data_type'], $array['data_precision']) . "</td>";
				}
			}
			break;
	}

	if ($total_rows > $num_of_rows) {
		print $nav;
	}

	echo '</table><br>';
	echo '<form name="custom_dropdown" method="post">';
	draw_custom_actions_dropdown($export_formats, 'cc_view.php', 'single_export');
	echo '</form>';
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

	$sql = "SELECT DISTINCT c.local_graph_id
			 FROM 			data_template_data 		AS a
			 INNER JOIN 	data_template_rrd 		AS b
			 ON 			b.local_data_id 		= a.local_data_id
			 INNER JOIN 	graph_templates_item 	AS c
			 ON 			c.task_item_id 			= b.id
			 WHERE 			a.local_data_id 		= {get_request_var('rrd')}";
	$local_graph_id = db_fetch_cell($sql);

	$start	= strtotime($report_data['start_date']);
	$end	= strtotime($report_data['end_date'] . ' 23:59:59');
	header("Location: ../../graph.php?action=zoom&local_graph_id=$local_graph_id&rra_id=0&graph_start=$start&graph_end=$end");
}


function show_graphs() {
	global $config, $colors, $graphs, $limit;

	$columns         = 1;
	$archive         = array();
	$affix           = '';
	$description     = '';
	$report_ds_alias = array();

	/* ================= Input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('data_source');
	get_filter_request_var('measurand');
	get_filter_request_var('archive');
	get_filter_request_var('type');
	get_filter_request_var('limit');
	get_filter_request_var('summary');
	/* ==================================================== */

	/* ==================== Checkpoint ==================== */
	my_report(get_request_var('id'), TRUE);
	check_graph_support();
	/* ==================================================== */

	/* clean up search string */
	if (isset_request_var('filter')) {
		set_request_var('filter', sanitize_search_string(get_request_var('filter')));
	}

	/* if the user pushed the 'clear' button */
	$id = (read_graph_config_option('reportit_view_filter') == 'on') ? get_request_var('id') : '';

	if (isset_request_var('clear_x')) {
		kill_session_var("sess_reportit_show_{$id}_data_source");
		kill_session_var("sess_reportit_show_{$id}_measurand");
		kill_session_var("sess_reportit_show_{$id}_filter");
		kill_session_var("sess_reportit_show_{$id}_archive");
		kill_session_var("sess_reportit_show_{$id}_type");
		kill_session_var("sess_reportit_show_{$id}_limit");
		kill_session_var("sess_reportit_show_{$id}_summary");

		unset_request_var('data_source');
		unset_request_var('measurand');
		unset_request_var('filter');
		unset_request_var('archive');
		unset_request_var('type');
		unset_request_var('limit');
		unset_request_var('summary');
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("data_source", "sess_reportit_show_{$id}_data_source", "-1");
	load_current_session_value("measurand", "sess_reportit_show_{$id}_measurand", "-1");
	load_current_session_value("filter", "sess_reportit_show_{$id}_filter", "");
	load_current_session_value("archive", "sess_reportit_show_{$id}_archive", "-1");
	load_current_session_value("type", "sess_reportit_show_{$id}_type", read_graph_config_option('reportit_g_default'));
	load_current_session_value("limit", "sess_reportit_show_{$id}_limit", "-2");
	load_current_session_value("summary", "sess_reportit_show_{$id}_summary", "0");

	/* form the 'where' clause for our main sql query */
	$affix .= " LIKE '%%" . get_request_var('filter') . "%%'";

	/* get informations about the archive if it exists */
	$archive = info_xml_archive(get_request_var('id'));

	/* load report archive and fill up report cache if requested*/
	if (get_request_var('archive') != -1) {
		cache_xml_file(get_request_var('id'), get_request_var('archive'));
		$cache_id = get_request_var('id') . '_' . get_request_var('archive');
	}

	/* load report data */
	$data = (get_request_var('archive') == -1)
			? get_prepared_report_data(get_request_var('id'),'view', $affix)
			: get_prepared_archive_data($cache_id, 'view', $affix);

	$report_ds_alias = $data['report_ds_alias'];
	$report_data     = $data['report_data'];
	$mea             = $data['report_measurands'];
	$report_header   = $report_data['description'];

	/* create a report summary */
	if (get_request_var('summary')) {
		$report_summary[1]['Title'] = $report_data['description'];
		$report_summary[1]['Runtime'] = $report_data['runtime'] . 's';
		$report_summary[2]['Owner'] = $report_data['owner'];
		$report_summary[2]['Sliding Time Frame'] = ($report_data['sliding'] == 0) ? 'disabled' : 'enabled (' . strtolower($report_data['preset_timespan']) .')';
		$report_summary[3]['Last Run'] = $report_data['last_run'];
		$report_summary[3]['Scheduler'] = ($report_data['scheduled'] == 0) ? 'disabled' : 'enabled (' . $report_data['frequency'] . ')';
		$report_summary[4]['Period'] = $report_data['start_date'] . " - " . $report_data['end_date'];
		$report_summary[4]['Auto Generated RRD list'] = ($report_data['autorrdlist'] == 0)? 'disabled' : 'enabled';
	}

	/* extract result description */
	list($rs_description, $count_rs) = explode('-', $report_data['rs_def']);
	$rs_description = ($rs_description == '') ? FALSE : explode('|', $rs_description);
	if ($rs_description !== FALSE) {
		foreach($rs_description as $key => $id) {
			if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
				$count_rs--;
				unset($rs_description[$key]);
			}else {
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
		$ov_description 	= ($ov_description == '') ? FALSE : explode('|', $ov_description);
		if ($ov_description !== FALSE) {
			foreach($ov_description as $key => $id) {
				if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == 0) {
					$count_ov--;
					unset($ov_description[$key]);
				}else {
					If(get_request_var('data_source') == -1 || get_request_var('data_source') == -2)
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
	foreach($data_sources as $key => $value) {
		if (is_array($report_ds_alias) && array_key_exists($value, $report_ds_alias) && $report_ds_alias[$value] != '')
			$data_sources[$key] = $report_ds_alias[$value];
	}

	/* filter by data source */
	if (get_request_var('data_source') != -1) {
		$ds_description = array($ds_description[get_request_var('data_source')]);
	}

	/* Filter settings */
	$order = (get_request_var('limit') < 0)? 'DESC' : 'ASC';
	$limitation = abs(get_request_var('limit'))*5;

	//----- Start HTML output -----
	ob_start();
	html_custom_header_box($report_header, false, "./cc_view.php?action=show_report&id={get_request_var('id')}", "<img src='./images/tab.gif' alt='Tabular View' border='0' align='top'>");
	include_once(REPORTIT_BASE_PATH . '/lib_int/inc_report_graphs_filter_table.php');
	html_end_box();

	if (get_request_var('summary')) {
		html_graph_start_box(1, false);
		foreach($report_summary as $array) {
			echo "<tr>";
			foreach($array as $key => $value) {
				echo "<td><b>$key:</b></td></td><td align='left'>$value</td>";
			}
			echo "</tr>";
		}
		html_graph_end_box();
		echo "<br>";
	}

	html_graph_start_box(3, false);
	foreach($ds_description as $datasource) {
		$description = (is_array($report_ds_alias) && array_key_exists($datasource, $report_ds_alias))
						? ($report_ds_alias[$datasource] != '')
							? $report_ds_alias[$datasource]
							: $datasource
						: $datasource;
		print "<tr bgcolor='#" . $colors["header_panel"] . "'><td colspan='3' class='textHeaderDark'><strong>Data Source:</strong> $description</td></tr>";

		$name	= ($datasource != 'overall') ? $rs_description : $ov_description;
		if ($name !== FALSE) {
			foreach($name as $id) {
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
						$sql = 	"SELECT a.*, b.*, c.name_cache FROM reportit_results_{get_request_var('id')} AS a
								 INNER JOIN reportit_data_items AS b
								 ON (b.id = a.id AND b.report_id = {get_request_var('id')})
								 INNER JOIN data_template_data AS c
								 ON c.local_data_id = a.id
								 WHERE c.name_cache ". $affix . $suffix;
					}else {
						$sql =	"SELECT * FROM reportit_tmp_{get_request_var('id')}_{get_request_var('archive')} AS a
								 WHERE a.name_cache ". $affix . $suffix;
					}

					$data = db_fetch_assoc($sql);
					echo "<tr bgcolor='#a9b7cb'><td colspan='3' class='textHeaderDark'><strong>Measurand:</strong> $title ({$mea[$id]['abbreviation']})</td></tr>";
					//echo "<tr valign='top'><td colspan='2'><a href='./cc_graphs.php?id={get_request_var('id')}&source=$var' style='border: 1px solid #bbbbbb;' alt='$title ({$mea[$id]['abbreviation']})'>hallo</a></td>";
					echo "<tr valign='top'><td colspan='2'><img src='./cc_graphs.php?id={get_request_var('id')}&source=$var' style='border: 1px solid #bbbbbb;' alt='$title ({$mea[$id]['abbreviation']})'></td>";
					echo "<td colspan='1' width='100%'>";
					if (count($data)>0) {
						html_report_start_box();
					html_header(array("Pos.","Description", "Results [$unit]"));
						$i = 0;
						foreach($data as $item){
							$value	= $item[$var];
							$title 	= "{$item['start_day']}&nbsp;-&nbsp;{$item['end_day']}&nbsp;&#10;{$item['start_time']}&nbsp;-&nbsp;{$item['end_time']} {$item['timezone']}";

							form_alternate_row_color($colors["alternate"], $colors["light"], $i); $i++;

							echo "<td title='$title'>$i</td>";
							echo "<td title='$title'>
										<a class='linkEditMain' href='cc_view.php?action=show_graph_overview&id={get_request_var('id')}&rrd={$item['id']}&cache={get_request_var('archive')}'>
										{$item['name_cache']}
										</a>
								  </td>";
							echo "<td title='$title' align='right'>";

							if ($value == NULL) {
								print "NA";
							}elseif ($value == 0) {
								print $value;
							}else {
								print get_unit($value, $rounding, $data_type, $data_precision);
							}

							echo "</td>";
						}
						echo "</table>";

					}
					echo "</td></tr>";
				}
			}
		}
	}

	html_graph_end_box();
	ob_end_flush();
}



function show_export_wizard($new=false){
	global $config,$colors, $export_formats;

	/* start-up sequence */
	if ($new !== false) {
		$_SESSION['reportit']['export'] = array();

		/* save all report ids in $_SESSION */
		foreach($_POST as $key => $value) {
			if (strstr($key, 'chk_')) {
				$id = substr($key, 4);
				my_report($id, TRUE);
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

	foreach($report_ids as $key => $value) {
		$ids .= "$key,";
	}

	$ids = substr($ids, 0, strlen($ids)-1);

	$report = db_fetch_assoc("SELECT id, description, scheduled, autoarchive
		FROM reportit_reports
		WHERE id IN ($ids)");

	if (sizeof($reports)) {
		foreach ($reports as $report) {
			form_alternate_row();

			echo '<td>' . $report['description'] . '</td>';

			$archive = info_xml_archive($report['id']);

			if ($archive) {
				echo '<td class="right">' . sizeof($archive) . '</td>';
			}else {
				echo '<td class="right">1</td>';
			}

			echo '<td class="right">' . sizeof($_SESSION['reportit']['export'][$id]['ids']) . '</td>';

			echo '<td class="right">'
				."<a href=\"cc_reports.php?action=remove&id=$key\">"
				.'<img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Delete"></a></td>';

			form_end_row();
		}
	} else {
		echo "<tr><td colspan='5'><em>" . __('No Reports Found') . "</em></td></tr>";
	}

	html_end_box();

	html_form_button("cc_view.php", "create", "id", false, "60%");
}

