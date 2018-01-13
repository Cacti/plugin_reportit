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
include_once($config['base_path'] . '/plugins/reportit/lib_int/funct_validate.php');
include_once($config['base_path'] . '/plugins/reportit/lib_int/funct_online.php');
include_once($config['base_path'] . '/plugins/reportit/lib_int/funct_shared.php');
include_once($config['base_path'] . '/plugins/reportit/lib_int/funct_html.php');
include_once($config['base_path'] . '/plugins/reportit/lib_int/const_runtime.php');
include_once($config['base_path'] . '/plugins/reportit/lib_int/const_rrdlist.php');

set_default_action();

switch (get_request_var('action')) {
	case 'actions':
		form_actions();
		break;
	case 'save':
		form_save();
		break;
	case 'rrdlist_edit':
		top_header();
		rrdlist_edit();
		bottom_footer();
		break;
	default:
		top_header();
		standard();
		bottom_footer();
		break;
}

function form_save() {
	global $timezone, $shifttime, $shifttime2, $weekday;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('report_id');
	get_filter_request_var('rrdlist_timezone');
	get_filter_request_var('rrdlist_shifttime_start');
	get_filter_request_var('rrdlist_shifttime_end');
	get_filter_request_var('rrdlist_weekday_start');
	get_filter_request_var('rrdlist_weekday_end');

	locked(my_template(get_request_var('report_id')));
	/* ==================================================== */

	/* check start and end of shifttime */
	$a = get_request_var('rrdlist_shifttime_start');
	$b = get_request_var('rrdlist_shifttime_end');

	if ($a == $b && $b == 0) {
		$b = count($shifttime);
	}

	/* prepare data array */
	$rrdlist_data['id']          = get_request_var('id');
	$rrdlist_data['report_id']   = get_request_var('report_id');
	$rrdlist_data['start_day']   = $weekday[get_request_var('rrdlist_weekday_start')];
	$rrdlist_data['end_day']     = $weekday[get_request_var('rrdlist_weekday_end')];
	$rrdlist_data['start_time']  = $shifttime[get_request_var('rrdlist_shifttime_start')];
	$rrdlist_data['end_time']    = $shifttime2[get_request_var('rrdlist_shifttime_end')];
	$rrdlist_data['description'] = get_nfilter_request_var('rrdlist_subhead');

	if (isset_request_var('rrdlist_timezone')) $rrdlist_data['timezone'] = $timezone[get_request_var('rrdlist_timezone')];

	/* save settings */
	sql_save($rrdlist_data, 'reportit_data_items', array('id', 'report_id'), false);

	/* reset report */
	reset_report(get_request_var('report_id'));

	/* return to list view */
	raise_message(1);

	header('Location: cc_rrdlist.php?header=false&id=' . get_request_var('report_id'));
	exit;
}

function standard() {
	global $config, $rrdlist_actions, $link_array, $item_rows;

	$subhead    = '';
	$enable_tmz = read_config_option('reportit_use_tmz');

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
		'id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => 0
			)
	);

	validate_store_request_vars($filters, 'sess_cc_rrdlist');
	/* ================= input validation ================= */

	/* ==================== checkpoint ==================== */
	my_report(get_request_var('id'));
	locked(my_template(get_request_var('id')));
	/* ==================================================== */

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = "WHERE b.name_cache LIKE '%" . get_request_var('filter') . "%' AND a.report_id = " . get_request_var('id');
	} else {
		$sql_where = "WHERE a.report_id = " . get_request_var('id');
	}

	$total_rows = db_fetch_cell("SELECT COUNT(a.id)
		FROM reportit_data_items AS a
   		LEFT JOIN data_template_data as b
		ON b.local_data_id = a.id
		$sql_where");

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$rrdlist = db_fetch_assoc("SELECT a.*, b.name_cache
		FROM reportit_data_items AS a
		LEFT JOIN data_template_data AS b
		ON b.local_data_id = a.id
		$sql_where
		$sql_order
		$sql_limit");

	$report_data = db_fetch_row_pepared('SELECT *
		FROM reportit_reports
		WHERE id = ?',
		array(get_request_var('id')));

	$header_label = __('Data Items [Report: %s %s [%d]', "<a class='pic' href='cc_reports.php?action=report_edit&id=" . get_request_var('id') . '>', $report_data['description'] . '</a>', $total_rows);

	/* define subheader description */
	$description = array(
		__('Description'),
		__('Subhead'),
		__('Shifttime (from - to)'),
		__('Weekdays (from - to)'),
		__('Time Zone')
	);

	if (!$enable_tmz) {
		$description = array_values($description);
		$link_array  = array_values($link_array);

		unset($description[array_search('Time Zone', $description)]);
		unset($link_array[array_search('timezone', $link_array)]);
	}

	$nav = html_nav_bar('cc_rrdlist.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('RRDs'), 'page', 'main');

	$columns = sizeof($link_array);

	/* start with HTML output */
	html_start_box($header_label, '100%', '', '2', 'center', 'cc_items.php?&id=' . get_request_var('id'));

	?>
	<tr class='odd'>
		<td>
		<form id='form_rrdlist' method='get' action='cc_rrdlist.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td width='1'>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('RRDs');?>
					</td>
					<td>
						<select id='rows'>
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
	<script type='text/javascript'>
	function applyFilter() {
		strURL = 'cc_rrdlist.php?header=false&filter='+escape($('#filter').val())+'&rows='+$('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'cc_rrdlist.php?clear=1&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#refresh').click(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#form_rrdlist').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_end_box();

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_checkbox($description);

	if (sizeof($rrdlist)) {
		foreach($rrdlist as $rrd) {
			form_alternate_row_color('line' . $rrd['id'], true);

			if ($rrd['name_cache'] == NULL) {
				form_selectable_cell(__('Does not exist anymore'), $rrd['id']);
			} else {
				form_selectable_cell("<class='linkEditMain' href='cc_rrdlist.php?action=rrdlist_edit&id=" . $rrd['id'] . "&report_id=" . get_request_var('id') . ">" . $rrd['name_cache'] . "</a>", $rrd['id']);
			}

			form_selectable_cell($rrd['description'], $rrd['id']);
			form_selectable_cell($rrd['start_time'] . ' - ' . $rrd['end_time'], $rrd['id']);
			form_selectable_cell($rrd['start_day']  . ' - ' . $rrd['end_day'],  $rrd['id']);

			if ($enable_tmz) form_selectable_cell($rrd['timezone'], $rrd['id']);

			form_checkbox_cell(__('Select'), $rrd['id']);
			form_end_row();
		}
	} else {
		print "<tr><td colspan='6'><em>" . __('No data items found') . "</em></td></tr>";
	}

	html_end_box();

	if (sizeof($rrdlist)) {
		print $nav;
	}

	/*remember report id */
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

	draw_actions_dropdown($rrdlist_actions, 'cc_reports.php');
}

function rrdlist_edit() {
	global $timezone, $shifttime, $shifttime2, $weekday;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('report_id');
	/* ==================================================== */

	/* ==================== checkpoint ==================== */
	my_report(get_request_var('report_id'));
	locked(my_template(get_request_var('report_id')));
	/* ==================================================== */

	$enable_tmz = read_config_option('reportit_use_tmz');

	$rrdlist_data = db_fetch_row_prepared('SELECT a.*, b.name_cache
		FROM reportit_data_items AS a
		LEFT JOIN data_template_data AS b
		ON b.local_data_id=a.id
		WHERE a.id = ?
		AND report_id = ?',
		array(get_request_var('id'), get_request_var('report_id')));

	$header_label = __('Data Object [edit: %s]', $rrdlist_data['name_cache']);

	/* start with HTML output */
	html_start_box($header_label, '100%', '', '2', 'center', '');

	$form_array = array(
		'rrdlist_header1' => array(
			'friendly_name' => __('General'),
			'method' => 'spacer'
		),
		'rrdlist_subhead' => array(
			'friendly_name' => __('Subhead (optional)'),
			'description' => __('Define an additional subhead that should be on display under the interface description.<br> Following variables will be supported (without quotes): \'|t1|\' \'|t2|\' \'|tmz|\' \'|d1|\' \'|d2|\''),
			'method' => 'textarea',
			'textarea_rows'	 => '2',
			'textarea_cols' => '45',
			'default' => '',
			'value' => $rrdlist_data['description']
		)
	);

	if ($enable_tmz) {
		$rrdlist_timezone = array(
			'friendly_name' => __('Time Zone'),
			'description' => __('Select the time zone your following shifttime informations will be based on.'),
			'method' => 'drop_array',
			'default' => '17',
			'value' => array_search($rrdlist_data['timezone'], $timezone),
			'array' => $timezone
		);

		$form_array['rrdlist_timezone'] = $rrdlist_timezone;
	}

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		)
	);

	html_end_box();

	html_start_box('', '100%', '', '2', 'center', '');

	$shift_array = array(
		'rrdlist_header2' => array(
			'friendly_name' => __('Working Time'),
			'method' => 'spacer',
		),
		'rrdlist_shifttime_start' => array(
			'friendly_name' => __('From'),
			'description' => __('The startpoint of duration you want to analyse'),
			'method' => 'drop_array',
			'default' => '0',
			'value' => array_search($rrdlist_data['start_time'], $shifttime),
			'array' => $shifttime
		),
		'rrdlist_shifttime_end' => array(
			'friendly_name' => __('To'),
			'description' => __('The end of analysing time.'),
			'method' => 'drop_array',
			'default' => '287',
			'value' => array_search($rrdlist_data['end_time'], $shifttime2),
			'array' => $shifttime2
		),
		'id' => array(
			'method' => 'hidden_zero',
			'value' => get_request_var('id')
		),
		'report_id' => array(
			'method' => 'hidden_zero',
			'value' => get_request_var('report_id')
		)
	);

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $shift_array
		)
	);

	html_end_box();

	html_start_box('', '100%', '', '2', 'center', '');

	$weekday_array = array(
		'rrdlist_header3' => array(
			'friendly_name' => __('Working Days'),
			'method' => 'spacer',
		),
		'rrdlist_weekday_start' => array(
			'friendly_name' => __('From'),
			'description' => __('Define the band of days where shift STARTS!'),
			'method' => 'drop_array',
			'value' => array_search($rrdlist_data['start_day'], $weekday),
			'array' => $weekday
		),
		'rrdlist_weekday_end' => array(
			'friendly_name' => __('To'),
			'description' => __('Example: For a nightshift from Mo(22:30) till Sat(06:30) define Monday to Friday'),
			'method' => 'drop_array',
			'value' => array_search($rrdlist_data['end_day'], $weekday),
			'array' => $weekday
		)
	);

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $weekday_array
		)
	);

	html_end_box();

	form_save_button('cc_rrdlist.php?&id='. get_request_var('report_id'));
}

function form_actions() {
	global $rrdlist_actions, $config;

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));

		if (get_request_var('drp_action') == '1') { // Remove RRD from RRD table
			$rrdlist_datas = db_fetch_assoc_prepared('SELECT id
				FROM reportit_data_items
				WHERE report_id = ?
				AND ' . array_to_sql_or($selected_items, 'id'),
				array(get_request_var('id')));

			if (sizeof($rrdlist_datas)) {
				foreach ($rrdlist_datas as $rrdlist_data) {
					db_execute_prepared('DELETE FROM reportit_data_items
						WHERE report_id = ?
						AND id = ?', array(get_request_var('id'), $rrdlist_data['id']));

					//Reset report
					reset_report(get_request_var('id'));
				}
			}
		}elseif (get_request_var('drp_action') == '2') { //Copy RRD's reference settings to all other RRDs
			$reference_items = unserialize(stripslashes(get_request_var('reference_items')));

			db_execute_prepared("UPDATE reportit_data_items
				SET start_day = ?, end_day = ?, start_time = ?,
				 end_time = ?, timezone = ?,
				 WHERE report_id = ?",
				array(
					$reference_items[0]['start_day'],
					$reference_items[0]['end_day'],
					$reference_items[0]['start_time'],
					$reference_items[0]['end_time'],
					$reference_items[0]['timezone'],
					get_request_var('id')
				)
			);

			//Reset report
			reset_report(get_request_var('id'));
		}

		header('Location: cc_rrdlist.php?header=false&id=' . get_request_var('id'));
		exit;
	}

	//Set preconditions
	$ds_list = '';
	$rrd_ids = array();

	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			//Fetch rrd id
			$id        = substr($key, 4);
			$rrd_ids[] = $id;

			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

            //Fetch rrd description
            $rrd_description = db_fetch_cell_prepared('SELECT b.name_cache
				FROM reportit_data_items AS a
				LEFT JOIN data_template_data AS b
				ON b.local_data_id = a.id
				WHERE a.id = ?
				AND a.report_id = ?',
				array($id, get_request_var('id')));

			$ds_list[] = $rrd_description;
		}
	}

	top_header();

	form_start('cc_rrdlist.php');

	html_start_box($rrdlist_actions{get_request_var('drp_action')}, '60%', '', '2', 'center', '');

	if (get_request_var('drp_action') == '1') { //DELETE REPORT
		print "<tr><td class='textArea'>
			<p>" . __('Click \'Continue\' to Remove the following Data Items.') . '</p>';

		if (is_array($ds_list)) {
			print	'<p>' . __('List of selected data items:') . '<br>';

			foreach($ds_list as $key => $value) {
				print __('&#160 |_Data Item : %s<br>', $value);
			}
		}

		print '</td></tr>';
	}elseif (get_request_var('drp_action') == '2') {
		// Copy the settings from selected RRD to all
		//Select the first selected checkbox as reference. The others will be ignored.
		//Fetch first's settings

		if (isset($rrd_ids[0])) {
			$rrd_settings = db_fetch_assoc_prepared('SELECT b.name_cache, a.*
				FROM reportit_data_items AS a
				LEFT JOIN data_template_data AS b
				ON b.local_data_id = a.id
				WHERE a.id = ?
				AND a.report_id = ?',
				array($rrd_ids[0], get_request_var('id')));

			print "<tr><td class='textArea'>
				<p>" . __('Click \'Continue\' to Copy the Settings to the other Data Items') . '</p>';

			print __('Selected data item as reference:');
			print '<b><br>&#160' . $rrd_settings[0]['name_cache'] . '</b><p></p>';
			print __('Time Zone:') . '<br>&#160 <b>' . $rrd_settings[0]['timezone'] . '</b><p></p>';
			print __('Weekdays:')  . '<br>&#160 <b>' . ($rrd_settings[0]['start_day'] - $rrd_settings[0]['end_day'])   . '</b><p></p>';
			print __('Shifttime:') . '<br>&#160 <b>' . ($rrd_settings[0]['start_time'] - $rrd_settings[0]['end_time']) . '</b><p></p>';

			print '</td></tr>';
		}
	}

	if (!is_array($ds_list)) {
		print "<tr><td class='odd''><span class='textError'>" . __('You must select at least one Report.') . '</span></td></tr>';

		$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>";
	} else {
		$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;
			<input type='submit' value='" . __('Continue') . "'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='id' value='" . get_request_var('id') . "'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($rrd_ids) ? serialize($rrd_ids) : '') . "'>
			<input type='hidden' name='reference_items' value='" . (isset($rrd_settings) ? serialize($rrd_settings) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	bottom_footer();
}

