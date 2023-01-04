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
include_once(REPORTIT_BASE_PATH . '/lib/const_runtime.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_items.php');

set_default_action();

switch (get_request_var('action')) {
	case 'save':
		save();
		break;
	case 'ajax_hosts':
		get_allowed_ajax_hosts();
		break;
	default:
		top_header();
		standard();
		bottom_footer();
		break;
}

function save(){
	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	/* ==================== checkpoint ==================== */
	my_report(get_request_var('id'));
	locked(my_template(get_request_var('id')));
	/* ==================================================== */

	/* search all checkboxes and put them into array $rrd_ids */
	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			$rrd_ids[] = substr($key, 4);
		}
	}

	/* check default settings and build SQL syntax for saving*/
	if (isset($rrd_ids)) {
		$enable_tmz	= read_config_option('reportit_use_tmz');
		$tmz		= ($enable_tmz) ? "'GMT'" : "'".date('T')."'";
		$columns	= '';
		$values		= '';
		$rrd 		= '';

		/* load data item presets */
		$presets = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_presets
			WHERE id = ?',
			array(get_request_var('id')));

		if (cacti_sizeof($presets)) {
			$presets['report_id'] = get_request_var('id');

			foreach($presets as $key => $value) {
				$columns .= ', ' . $key;

				if ($key != 'id') {
					$values .= ',' . db_qstr($value);
				}
			}
		} else {
			$columns = ' id, report_id';
			$values .= ', ' . db_qstr(get_request_var('id'));
		}

		foreach($rrd_ids as $rd) {
			$rrd .= "($rd $values),";
		}

		$rrd = substr($rrd, 0, strlen($rrd)-1);
		$columns = substr($columns, 1);

		/* save */
		db_execute("INSERT INTO plugin_reportit_data_items ($columns) VALUES $rrd");

		/* reset report */
		reset_report(get_request_var('id'));
	}

	/* return to standard form */
	header('Location: items.php?header=false&id=' . get_request_var('id'));
}

function standard() {
	global $config, $link_array;

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
		'host_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			)
	);
	$filters = api_plugin_hook_function('report_filters', $filters);

	validate_store_request_vars($filters, 'sess_items');
	/* ================= input validation ================= */

	/* ==================== checkpoint ==================== */
	my_report(get_filter_request_var('id'));
	locked(my_template(get_request_var('id')));
	/* ==================================================== */

	$report_data = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_reports
		WHERE id = ?',
		array(get_request_var('id')));

	$current_owner 	= db_fetch_row_prepared('SELECT *
		FROM user_auth
		WHERE id = ?',
		array($report_data['user_id']));


	// TODO create a get_allowed_data_source() function to achieve
	// identical results to the current other graph and template
	// based functions.

	/* load filter settings of that report template this report relies on */
	$template_filter = db_fetch_assoc_prepared('SELECT rt.pre_filter, rt.data_template_id
		FROM plugin_reportit_reports AS rr
		INNER JOIN plugin_reportit_templates AS rt
		ON rr.template_id = rt.id
		WHERE rr.id = ?',
		array(get_request_var('id')));

	/* start building the SQL syntax */
	/* filter all RRDs which are not in RRD table and match with filter settings */
	$sql = 'SELECT DISTINCT a.local_data_id AS id, a.name_cache
		FROM data_template_data AS a
		LEFT JOIN plugin_reportit_data_items AS b
		ON a.local_data_id = b.id
		AND b.report_id = ' . get_request_var('id') . '
		LEFT JOIN data_local AS c
		ON c.id = a.local_data_id
		LEFT JOIN host AS d
		ON d.id = c.host_id';

	/* apply Host Template Id filter, if selected in report configuration*/
	if ($report_data['host_template_id'] != 0) {
		$sql .= " LEFT JOIN host_template AS e ON e.id = d.host_template_id";
	}

	/* check Data Template Id filter */
	$sql .= " WHERE b.id IS NULL
		AND a.local_data_id != '0'
		AND a.data_template_id =" . $template_filter['0']['data_template_id'];

	/* check pre-filter settings of the report template */
	if ($template_filter['0']['pre_filter'] != '') {
		$sql .= " AND a.name_cache LIKE '" . $template_filter['0']['pre_filter'] ."'";
	}

	/* check host filter defined by form */
	if (get_request_var('host_id') == '-1') {
		/* filter nothing */
	} elseif (!isempty_request_var('host_id')) {
		/* show only data items of selected host */
		$sql .= ' AND c.host_id =' . get_request_var('host_id');
	}

	/* check text filter defined by form */
	if (strlen(get_request_var('filter'))) {
		$sql .= ' AND a.name_cache LIKE "%' . get_request_var('filter') . '%"';
	}

	/* check for the specific Host Template Id, if Host Template Id filter has been applied */
	if ($report_data['host_template_id'] != 0) {
		$sql .= ' AND e.id = ' . $report_data['host_template_id'];
	}

	/* check Data Source Filter, if defined in report configuration*/
	if ($report_data['data_source_filter'] != '') {
		$sql .= ' AND a.name_cache LIKE "%' . $report_data['data_source_filter'] . '%"';
	}

	/* use additional where clause for graph permissions if necessary */
	if (read_config_option('auth_method') != 0 & $report_data['graph_permission'] == 1) {
		$sql .= ' AND ' . $sql_where;
	}

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$total_rows = db_fetch_cell(str_replace('DISTINCT a.local_data_id AS id, a.name_cache','COUNT(DISTINCT a.local_data_id)', $sql));

	/* apply sorting functionality and limitation */
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$sql .= ' ' . $sql_order . $sql_limit;

	$rrdlist = db_fetch_assoc($sql);

	$nav = html_nav_bar('items.php?id=' . get_request_var('id') . '&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Items', 'reportit'), 'page', 'main');

	$header_label	= __('Data Items [add to report: <a style="color:yellow" href="reports.php?action=report_edit&id=%d">%s</a>]', get_request_var('id'), $report_data['description'], 'reportit');

	/* show the Host Template Description in the header, if Host Template Id filter was set */
	$ht_desc = db_fetch_cell_prepared('SELECT name
		FROM host_template
		WHERE id = ?',
		array($report_data['host_template_id']));

	if (!strlen($ht_desc)) {
		$ht_desc = __('None', 'reportit');
	}

	/* show the Data Source Filter in the header, if it has been defined */
	$ds_desc = $report_data['data_source_filter'];
	if (!strlen($ds_desc)) {
		$ds_desc = __('None', 'reportit');
	}

	items_filter($header_label);

	print $nav;
	form_start('items.php');
	html_start_box('', '100%', '', '3', 'center', '');

	$desc_array = array(
		'id'                   => array('display' => __('ID', 'reportit'),              'sort' => 'ASC',  'align' => 'left'),
		'name_cache'           => array('display' => __('Data Item Name', 'reportit'),  'sort' => 'ASC',  'align' => 'left'),
	);

	html_header_sort_checkbox($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'items.php?id=' . get_request_var('id'));

	//Set preconditions
	$i = 0;

	if (cacti_sizeof($rrdlist)) {
		foreach($rrdlist as $rrd) {
			form_alternate_row('line' . $rrd['id'], true);
			form_selectable_cell( $rrd['id'], $rrd['id']);
			form_selectable_cell( filter_value($rrd['name_cache'], get_request_var('filter')), $rrd['id'], 'left');
			form_checkbox_cell("Select",$rrd["id"]);
		}
	} else {
		print '<tr><td colspan="2"><em>' . __('No data items', 'reportit') . '</em></td></tr>';
	}

	/*remember report id */
	$form_array = array('id' => array('method' => 'hidden_zero', 'value' => get_request_var('id')));

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		)
	);

	html_end_box(true);

	if ($total_rows > $rows) print $nav;

	form_save_button('rrdlist.php?&id=' . get_request_var('id'), '', '');
}

function items_filter($header_label) {
	global $item_rows;

	html_start_box($header_label, '100%', '', '3', 'center','');
	?>
	<tr class='even'>
		<td>
		<form id='form_reports' action='items.php?id=<?php print get_request_var('id');?>'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'reportit');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<?php print html_host_filter(get_request_var('host_id'));?>
					<td>
						<?php print __('Reports', 'reportit');?>
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
						<input type='button' value='<?php print __esc_x('Button: use filter settings', 'Go');?>' id='refresh'>
					</td>
					<td>
						<input type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear');?>' id='clear'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_filter_request_var('page');?>'>
		</form>
		<script type='text/javascript'>

		function applyFilter() {
			strURL = 'items.php?id=<?php print get_request_var('id');?>'+'&filter='+
				escape($('#filter').val())+
				'&host_id='+$('#host_id').val()+
				'&rows='+$('#rows').val()+
				'&page='+$('#page').val()+
				'&header=false';
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL = 'items.php?id=<?php print get_request_var('id');?>&clear=1&header=false';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#refresh').click(function() {
				applyFilter();
			});

			$('#clear').click(function() {
				clearFilter();
			});

			$('#form_reports').submit(function(event) {
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

