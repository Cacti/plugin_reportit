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
require_once(CACTI_PATH_LIBRARY . '/reports.php');
require_once(CACTI_PATH_LIBRARY . '/poller.php');
require_once(CACTI_PATH_LIBRARY . '/api_scheduler.php');

if (!defined('REPORTIT_BASE_PATH')) {
	require_once(__DIR__ . '/setup.php');
	reportit_define_constants();
}

require_once(REPORTIT_BASE_PATH . '/include/global_arrays.php');
require_once(REPORTIT_BASE_PATH . '/lib/funct_online.php');
require_once(REPORTIT_BASE_PATH . '/lib/const_runtime.php');
require_once(REPORTIT_BASE_PATH . '/lib/const_reports.php');
require_once(REPORTIT_BASE_PATH . '/lib/const_rrdlist.php');
require_once(REPORTIT_BASE_PATH . '/lib/funct_validate.php');
require_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
require_once(REPORTIT_BASE_PATH . '/lib/funct_html.php');

set_default_action();

switch (get_request_var('action')) {
	case 'actions':
		form_actions();

		break;
	case 'report_edit':
		top_header();
		report_edit();
		bottom_footer();

		break;
	case 'rrdlist_edit':
		top_header();
		rrdlist_edit();
		bottom_footer();

		break;
	case 'report_add':
		top_header();
		report_wizard();
		bottom_footer();

		break;
	case 'recipient_add':
		$add_recipients = true;
	case 'save':
		form_save();

		break;
	case 'remove':
		remove_recipient();

		break;
	default:
		top_header();
		standard();
		bottom_footer();

		break;
}

function report_wizard() {
	$templates_list = array();
	$templates      = array();

	$templates_list = db_fetch_assoc("SELECT id, description
		FROM plugin_reportit_templates
		WHERE `locked` = '' AND `enabled` = 'on' ORDER BY description");

	$templates_locked = db_fetch_cell("SELECT count(id) as rower
		FROM plugin_reportit_templates
		WHERE NOT (`locked` = '' AND `enabled` = 'on') ORDER BY description");

	if (isset($_SESSION['reportit'])) {
		unset($_SESSION['reportit']);
	}

	form_start('reportit.php');

	html_start_box(__('New Report', 'reportit'), '60%', '', '3', 'center', '');

	if (cacti_sizeof($templates_list) == 0) {
		raise_message('no_unlocked', __('There are no unlocked and enabled report templates available (%s locked or disabled).', $templates_locked, 'reportit'), MESSAGE_LEVEL_ERROR);
		header('Location: reportit.php');
		exit;
	} else {
		$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"reportit.php\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Create a new report', 'reportit') . "'>";

		foreach($templates_list as $tmp) {
			$templates[$tmp['id']] = $tmp['name'];
		}

		print "<tr class='even'>
			<td>
				<p>" . __('Choose a template this report should depend on.', 'reportit') . "</p>
			</td>
		<td>";

		form_dropdown('template', $templates, '', '', '', '', '');

		print '</td>
		</tr>';
	}

	print "<tr>
		<td class='saveRow' colspan='2'>
			<input type='hidden' name='action' value='report_edit'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();
}

function report_filter() {
	global $item_rows;

	html_start_box( __('Report Filters', 'reportit'), '100%', '', '3', 'center', 'reportit.php?action=report_add');
	?>
	<tr class='even'>
		<td>
			<form id='form_reports' action='reportit.php'>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Search', 'reportit');?>
						</td>
						<td>
							<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
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
										print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . "</option>";
									}
								}
								?>
							</select>
						</td>
						<td>
							<span>
								<input type='submit' value='<?php print __esc_x('Button: use filter settings', 'Go', 'reportit');?>' id='refresh'>
								<input type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear', 'reportit');?>' id='clear'>
							</span>
						</td>
					</tr>
				</table>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL = 'reportit.php?filter='+
					escape($('#filter').val())+
					'&rows='+$('#rows').val()+
					'&page='+$('#page').val();
				loadUrl({ url: strURL });
			}

			function clearFilter() {
				strURL = 'reportit.php?clear=1';
				loadUrl({ url: strURL });
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

function standard() {
	global $report_actions, $minutes, $report_states, $report_schedule_frequency;

	$affix       = '';
	$columns     = 0;
	$myId        = my_id();
	$myName      = my_name();
	$reportAdmin = re_admin();
	$tmz         = (read_config_option('reportit_show_tmz') == 'on') ? '('.date('T').')' : '';
	$enable_tmz  = read_config_option('reportit_use_tmz');

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
		'owner' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
		),
		'template' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
		),
	);

	validate_store_request_vars($filters, 'sess_reportit_reports');
	/* ================= Input validation ================= */

	if ($reportAdmin) {
		/* fetch user names */
		$ownerlist = db_fetch_assoc('SELECT DISTINCT a.user_id as id, c.username
			FROM plugin_reportit_reports AS a
			LEFT JOIN plugin_reportit_templates AS b
			ON b.id = a.template_id
			LEFT JOIN user_auth AS c
			ON c.id = a.user_id
			ORDER BY c.username');

		/* fetch template list */
		$sql = 'SELECT DISTINCT b.id, b.description
			FROM plugin_reportit_reports AS a
			INNER JOIN plugin_reportit_templates AS b
			ON b.id = a.template_id';

		if (get_request_var('owner') !== '-1' && !isempty_request_var('owner')) {
			$sql .= ' WHERE a.user_id = ' . get_request_var('owner') . ' ORDER BY b.description';
			$templatelist = db_fetch_assoc($sql);

			if (cacti_sizeof($templatelist)>0) {
				foreach($templatelist as $template) {
					if ($template['id'] == get_request_var('template')) {
						$a = 1;
						break;
					}
				}

				if (!isset($a)) {
					get_request_var('template', '-1');
				}
			}
		} else {
			$templatelist = db_fetch_assoc($sql);
		}
	}

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$affix .= " WHERE a.name LIKE '%" . get_request_var('filter') . "%'";
	}

	/* check admin's filter settings */
	if ($reportAdmin) {
		if (get_request_var('owner') == '-1') {
			/* filter nothing */
		} elseif (!isempty_request_var('owner')) {
			/* show only data items of selected report owner */
			$affix .= ' AND a.user_id =' . get_request_var('owner');
		}
		if (get_request_var('template') == '-1') {
			/* filter nothing */
		} elseif (!isempty_request_var('template')) {
			/* show only data items of selected template */
			$affix .= ' AND a.template_id =' . get_request_var('template');
		}
	} else {
		/* filter for user */
		$affix .= "AND a.user_id = $myId";
	}

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$total_rows  = db_fetch_cell("SELECT COUNT(a.id) FROM plugin_reportit_reports AS a $affix");

	$report_list = db_fetch_assoc('SELECT a.*, b.description AS template_description, c.ds_cnt, d.username, b.locked
		FROM plugin_reportit_reports AS a
		LEFT JOIN plugin_reportit_templates AS b
		ON b.id = a.template_id
		LEFT JOIN
		(SELECT report_id, count(*) as ds_cnt FROM `plugin_reportit_data_items` GROUP BY report_id) AS c
		ON c.report_id = a.id
		LEFT JOIN user_auth AS d
		ON d.id = a.user_id' . $affix .
		' ORDER BY ' . get_request_var('sort_column') . ' ' . get_request_var('sort_direction') .
		' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows);

	$desc_array = array(
		'name' => array(
			'display' => __('Name', 'reportit'),
			'sort'    => 'ASC',
		),
		'id' => array(
			'display' => __('ID', 'reportit'),
			'sort'    => 'ASC',
		),
		'nosort0' => array(
			'display' => __("Period %s From - To", $tmz, 'reportit')
		),
		'frequency' => array(
			'display' => __('Schedule Frequency', 'reportit'),
			'sort'    => 'ASC'
		),
		'state' => array(
			'display' => __('State', 'reportit'),
			'sort'    => 'ASC',
		),
		'last_started' => array(
			'display' => __('Last Started %s', $tmz, 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC',
		),
		'last_runtime' => array(
			'display' => __('Last Runtime [s]', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC',
		),
		'public' => array(
			'display' => __('Public', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC',
		),
		'enabled' => array(
			'display' => __('Enabled', 'reportit'),
			'align'   => 'right',
			'sort'    => 'ASC',
		),
		'ds_cnt' => array(
			'display' => __('Data Sources', 'reportit'),
			'align'   => 'right',
			'sort'    => 'DESC',
		),
	);

	/* start with HTML output */
	report_filter();

	$nav = html_nav_bar('reportit.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($desc_array), __('Reports', 'reportit'), 'page', 'main');

	print $nav;

	form_start('reportit.php');

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'reportit.php');

	if (cacti_sizeof($report_list)) {
		foreach($report_list as $report) {
			$link = 'reportit.php?action=report_edit&id=' . $report['id'];

			form_alternate_row('line' . $report['id'], true);

			form_selectable_cell(filter_value($report['name'], get_request_var('filter'), $link), $report['id']);
			form_selectable_cell(filter_value($report['id'], get_request_var('filter'), $link), $report['id']);

			if ($report['sliding'] == 'on' && $report['last_started'] == '0000-00-00 00:00:00') {
				$dates = rp_get_timespan($report['preset_timespan'], $report['present'], $enable_tmz);
				form_selectable_cell(date(config_date_format(), strtotime($dates['start_date'])) . " - " . date(config_date_format(), strtotime($dates['end_date'])), $report['id']);
			} else {
				form_selectable_cell(($report['start_date'] == '0000-00-00' ? '00-00-0000' : date(config_date_format(), strtotime($report['start_date']))) . ' - ' . ($report['start_date'] == '0000-00-00' ? '00-00-0000' : date(config_date_format(), strtotime($report['end_date']))), $report['id']);
			}

			if ($report['enabled'] == 'on') {
				form_selectable_cell($report_schedule_frequency[$report['frequency']], $report['id']);
			} else {
				form_selectable_cell(__('Disabled', 'reportit'), $report['id']);
			}

			form_selectable_cell($report_states[$report['state']], $report['id']);

			if ($report['last_started'] == '0000-00-00 00:00:00') {
				form_selectable_cell(__('N/A', 'reportit'), $report['id'], '', 'right');
			} else {
				$link = "view.php?action=show_report&id={$report['id']}";

				form_selectable_cell(filter_value($report['last_started'], '', $link), $report['id'], '', 'right');
			}

			form_selectable_cell(sprintf("%01.1f", $report['last_runtime']), $report['id'], '', 'right');
			form_selectable_cell(html_check_icon($report['public']), $report['id'], '', 'right');
			form_selectable_cell(html_check_icon($report['enabled']), $report['id'], '', 'right');

			$link = "reportit.php?action=report_edit&tab=items&id={$report['id']}";

			print "<td class='right'><a class='linkEditMain href='$link'>" . html_sources_icon($report['ds_cnt'], __('Edit sources', 'reportit'), __('Add sources', 'reportit')) . '</a></td>';

			if (!$report['locked'] && $report['state'] < 1) {
				form_checkbox_cell(__esc('Select %s', $report['name'], 'reportit'), $report['id'], '', 'right');
			} else {
				print '<td class="right">' . html_lock_icon('on', __('Report has been locked', 'reportit')) . '</td>';
			}

			form_end_row();
		}
	} else {
		print "<tr><td colspan='10'><em>" . __('No reports', 'reportit') . "</em></td></tr>";
	}

	html_end_box(true);

	if ($total_rows > $rows) {
		print $nav;
	}

	draw_actions_dropdown($report_actions);

	form_end();
}

function remove_recipient() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('rec');
	/* ==================================================== */

	/* ==================== Checkpoint ==================== */
	my_report(get_request_var('id'));
	/* ==================================================== */

	db_execute_prepared('DELETE FROM plugin_reportit_recipients
		WHERE id = ?
		AND report_id = ?',
		array(get_request_var('rec'), get_request_var('id')));

	header('Location: reportit.php?action=report_edit&id=' . get_request_var('id') . '&tab=email');
	exit;
}

function form_save() {
	global 	$templates, $timespans, $frequency, $timezone, $shifttime, $shifttime2, $weekday, $format, $add_recipients;

	global $timezone, $shifttime, $shifttime2, $weekday;

	$owner = array();
	$post  = $_POST;

	$sql = "SELECT DISTINCT a.id, a.username as name FROM user_auth AS a
		INNER JOIN user_auth_realm AS b
		ON a.id = b.user_id WHERE (b.realm_id = " . REPORTIT_USER_OWNER . " OR b.realm_id = " . REPORTIT_USER_VIEWER . ")
		ORDER BY username";

	$owner = db_custom_fetch_assoc($sql, 'id', false);

	/* ================= Input Validation ================= */
	get_filter_request_var('tab', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '(general|presets|admin|email|items)')));
	get_filter_request_var('id');
	get_filter_request_var('template_id');
	get_filter_request_var('site_id');
	get_filter_request_var('host_template_id');
	get_filter_request_var('id');
	get_filter_request_var('owner');

	/* item specific validation */
	get_filter_request_var('report_id');
	get_filter_request_var('rrdlist_timezone');
	get_filter_request_var('rrdlist_shifttime_start');
	get_filter_request_var('rrdlist_shifttime_end');
	get_filter_request_var('rrdlist_weekday_start');
	get_filter_request_var('rrdlist_weekday_end');
	/* ==================================================== */

	if (!isset($post['tab'])) {
		$post['tab'] = 'general';
	}

	/* stop if user is not authorised to save a report config */
	if ($post['tab'] != 'items') {
		if ($post['id'] != 0) {
			my_report($post['id']);
		}
	} elseif ($post['report_id'] != 0) {
		my_report($post['report_id']);
	}

	if (!re_owner()) {
		die_html_custom_error(__('Not Authorized', 'reportit'), true); //this should normally done by Cacti itself
	}

	/* check for the type of saving if it was sent through the email tab */
	switch($post['tab']) {
		case 'presets':
		 	input_validate_input_blacklist($post['id'], array(0));
			input_validate_input_key($post['rrdlist_timezone'], $timezone, true);
			input_validate_input_key($post['rrdlist_shifttime_start'], $shifttime);
			input_validate_input_key($post['rrdlist_shifttime_end'], $shifttime2);
			input_validate_input_key($post['rrdlist_weekday_start'], $weekday);
			input_validate_input_key($post['rrdlist_weekday_end'], $weekday);

			form_input_validate($post['rrdlist_subhead'], 'rrdlist_subhead', '' , true, 3);

			form_input_validate($post['data_source_filter'], 'data_source_filter', '', true, 3);

			break;
		case 'admin':
			input_validate_input_blacklist($post['id'], array(0));

			break;
		case 'email':
			if (!$add_recipients) {
				form_input_validate($post['email_subject'], 'email_subject', '' ,false,3);
				form_input_validate($post['email_body'], 'email_body', '', false, 3);
				input_validate_input_key($post['email_format'], $format);
			} else {
				/* if javascript is disabled */
				form_input_validate($post['email_address'], 'email_address', '', false, 3);
			}

			break;
		case 'items':
			if (isset($post['save_component_rrdlist'])) {
				/* ================= input validation ================= */
				locked(my_template($post['report_id']));
				/* ==================================================== */

				/* check start and end of shifttime */
				$a = $post['rrdlist_shifttime_start'];
				$b = $post['rrdlist_shifttime_end'];

				if ($a == $b && $b == 0) {
					$b = count($shifttime);
				}

				/* prepare data array */
				$rrdlist_data['id']          = $post['id'];
				$rrdlist_data['report_id']   = $post['report_id'];
				$rrdlist_data['start_day']   = $weekday[$post['rrdlist_weekday_start']];
				$rrdlist_data['end_day']     = $weekday[$post['rrdlist_weekday_end']];
				$rrdlist_data['start_time']  = $shifttime[$post['rrdlist_shifttime_start']];
				$rrdlist_data['end_time']    = $shifttime2[$post['rrdlist_shifttime_end']];
				$rrdlist_data['description'] = $post['rrdlist_subhead'];

				if (isset($post['rrdlist_timezone'])) {
					$rrdlist_data['timezone'] = $timezone[$post['rrdlist_timezone']];
				}

				/* save settings */
				sql_save($rrdlist_data, 'plugin_reportit_data_items', array('id', 'report_id'), false);

				/* reset report */
				reset_report($post['report_id']);

				/* return to list view */
				raise_message(1);

				header('Location: reportit.php?action=report_edit&tab=items&id=' . $post['report_id']);
				exit;
			}

			break;
		default:
			if (isset($post['preset_timespan'])) {
				input_validate_input_key($post['preset_timespan'], $timespans, true);
			}

			/* if template is locked we don't know if the variables have been changed */
			locked($post['template_id']);

			form_input_validate($post['name'], 'name', '', false, 3);

			/* validate start- and end date if sliding time should not be used */
			if (!isset($post['dynamic'])) {
				if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $post['start_date'])) {
					session_custom_error_message('start_date', 'Invalid date');
				}

				if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $post['end_date'])) {
					session_custom_error_message('end_date', 'Invalid date');
				}

				if (!is_error_message()) {
					list($ys, $ms, $ds) = explode('-', $post['start_date']);
					list($ye, $me, $de) = explode('-', $post['end_date']);

					if (!checkdate($ms, $ds, $ys)) session_custom_error_message('start_date', 'Invalid date');
					if (!checkdate($me, $de, $ye)) session_custom_error_message('end_date', 'Invalid date');

					if (($start_date = mktime(0,0,0,$ms,$ds,$ys)) > ($end_date = mktime(0,0,0,$me,$de,$ye)) || $ys > $ye || $ys > date('Y')) {
						session_custom_error_message('start_date', 'Start date lies ahead');
					}
					if (($end_date = mktime(0,0,0,$me,$de,$ye)) > time() || $ye > date('Y')) {
						session_custom_error_message('start_date', 'End date lies ahead');
					}
				}
			}
	}

	/* return if validation failed */
	if (is_error_message()) {
		header('Location: reportit.php?action=report_edit&id=' . $post['id'] . '&tab=' . $post['tab']);
		exit;
	}

	switch($post['tab']) {
		case 'presets':
			$rrdlist_data['id']         = $post['id'];
			$rrdlist_data['start_day']  = $weekday[$post['rrdlist_weekday_start']];
			$rrdlist_data['end_day']    = $weekday[$post['rrdlist_weekday_end']];
			$rrdlist_data['start_time'] = $shifttime[$post['rrdlist_shifttime_start']];
			$rrdlist_data['end_time']   = $shifttime2[$post['rrdlist_shifttime_end']];

			if (isset($post['rrdlist_timezone'])) {
				$rrdlist_data['timezone']      = $timezone[$post['rrdlist_timezone']];
			}

			if (isset($post['rrdlist_subhead'])) {
				$rrdlist_data['description']   = $post['rrdlist_subhead'];
			}

			$report_data['id']                 = $post['id'];
			$report_data['site_id']            = $post['site_id'];
			$report_data['host_template_id']   = $post['host_template_id'];
			$report_data['data_source_filter'] = $post['data_source_filter'];

			/* save settings */
			sql_save($report_data, 'plugin_reportit_reports');
			sql_save($rrdlist_data, 'plugin_reportit_presets', 'id', false);

			break;
		case 'admin':
			$report_data['id']               = $post['id'];
			$report_data['graph_permission'] = (isset($post['graph_permission']) ? 'on':'');
			$report_data['enabled']          = (isset($post['enabled']) ? 'on':'');
			$report_data['autorrdlist']      = (isset($post['autorrdlist']) ? 'on':'');
			$report_data['auto_email']       = (isset($post['auto_email']) ? 'on':'');

			/* save settings */
			sql_save($report_data, 'plugin_reportit_reports');

			break;
		case 'email':
			if (!$add_recipients) {
				$report_data['id']            = $post['id'];
				$report_data['notify_list']   = $post['notify_list'];
				$report_data['email_subject'] = $post['email_subject'];
				$report_data['email_body']    = $post['email_body'];
				$report_data['email_format']  = $post['email_format'];

				/* save settings */
				sql_save($report_data, 'plugin_reportit_reports');
			} else {
				$id = $post['id'];

				$addresses = array();
				if ($post['email_address'] != '' && strpos($post['email_address'], ';')) {
					$addresses   = explode(';', $post['email_address']);
				} elseif ($post['email_address'] != '' && strpos($post['email_address'], ',')) {
					$addresses   = explode(',', $post['email_address']);
				} else {
					$addresses[] = $post['email_address'];
				}

				$recipients = array();
				if ($post['email_recipient'] != '' && strpos($post['email_recipient'], ';')) {
					$recipients   = explode(';', $post['email_recipient']);
				} elseif ($post['email_recipient'] != '' && strpos($post['email_recipient'], ',')) {
					$recipients   = explode(',', $post['email_recipient']);
				} else {
					$recipients[] = $post['email_recipient'];
				}

				if (cacti_sizeof($addresses)) {
					foreach($addresses as $key => $value) {
						$value = trim($value);

						if (!preg_match("/(^[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*@[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*\\.[a-zA-Z]{2,3}$)/", $value)) {
							cacti_log('WARNING: Unable to add email address "' . $value . '" to RIReport[' . $id . ']', false, 'REPORTIT');
							session_custom_error_message('email_address', 'Invalid email address');
						} else {
							if (array_key_exists($key, $recipients) && $recipients[$key][1] != '[') {
								$name = $recipients[$key];
							} else {
								$name = '';
							}

							db_execute_prepared('INSERT INTO plugin_reportit_recipients
								(report_id, email, name) VALUES (?,?,?)',
								array($id, $value, $name));
						}
					}
				}
			}

			break;
		case 'items':
			/* ================= input validation ================= */
			locked(my_template($post['report_id']));
			/* ==================================================== */

			/* check start and end of shifttime */
			$a = $post['rrdlist_shifttime_start'];
			$b = $post['rrdlist_shifttime_end'];

			if ($a == $b && $b == 0) {
				$b = count($shifttime);
			}

			/* prepare data array */
			$rrdlist_data['id']           = $post['id'];
			$rrdlist_data['report_id']    = $post['report_id'];
			$rrdlist_data['start_day']    = $weekday[$post['rrdlist_weekday_start']];
			$rrdlist_data['end_day']      = $weekday[$post['rrdlist_weekday_end']];
			$rrdlist_data['start_time']   = $shifttime[$post['rrdlist_shifttime_start']];
			$rrdlist_data['end_time']     = $shifttime2[$post['rrdlist_shifttime_end']];
			$rrdlist_data['description']  = $post['rrdlist_subhead'];

			if (isset($post['rrdlist_timezone'])) {
				$rrdlist_data['timezone'] = $timezone[$post['rrdlist_timezone']];
			}

			/* save settings */
			sql_save($rrdlist_data, 'plugin_reportit_data_items', array('id', 'report_id'), false);

			/* reset report */
			reset_report($post['report_id']);

			/* return to list view */
			raise_message(1);

			break;
		default:
			$report_data['id']              = $post['id'];

			$report_data['user_id']         = isset($post['owner']) ? $post['owner']:'';
			$report_data['name']            = $post['name'];
			$report_data['template_id']     = $post['template_id'];
			$report_data['public']          = $post['public'];

			$report_data['preset_timespan'] = isset($post['timespan']) ? $timespans[$post['timespan']] : '';

			$report_data['start_date']      = $post['start_date'];
			$report_data['end_date']        = $post['end_date'];

			$report_data['sliding']         = $post['dynamic'];

			if (isset($post['present'])) {
				$report_data['present']     = $post['present'];
			}

			$report_data['enabled']         = (isset($post['enabled']) ? 'on':'');
			$report_data['autorrdlist']     = (isset($post['autorrdlist']) ? 'on':'');
			$report_data['auto_email']      = (isset($post['auto_email']) ? 'on':'');

			$report_data = api_scheduler_augment_save($report_data, $post);

			/* define the owner if it's a new configuration */
			if ($post['id'] == 0) {
				$report_data['user_id'] = my_id();
			}

			//Now we've to keep our variables
			$vars     = array();
			$rvars    = array();
			$var_data = array();

			foreach($post as $key => $value) {
				if (strstr($key, 'var_')) {
					$id = substr($key, 4);
					$vars[$id] = $value;
				}
			}

			$rvars = db_fetch_assoc_prepared('SELECT a.*, b.id AS b_id, b.value
				FROM plugin_reportit_variables AS a
				LEFT JOIN plugin_reportit_rvars AS b
				ON a.id = b.variable_id
				AND report_id = ?
				WHERE a.template_id = ?',
				array($post['id'], $post['template_id']));

			foreach($rvars as $key => $v) {
				$value = $vars[$v['id']];
				if ($v['input_type'] == 1) {
					$i = 0;
					$array = array();
					$a = $v['min_value'];
					$b = $v['max_value'];
					$c = $v['stepping'];

					for($i=$a; $i <= $b; $i+=$c) {
						$array[] = $i;
					}

					$value = $array[$value];

					if ($value > $v['max_value'] || $value < $v['min_value']) die_html_custom_error('', true);
				} else {
					if ($value > $v['max_value'] || $value < $v['min_value']) {

						session_custom_error_message($v['name'], "{$v['name']} is out of range");
						break;
					}
				}

				//If there's no error we can go on
				$var_data[] = array(
					'id'          => (($v['b_id'] != NULL) ? $v['b_id'] : 0),
					'template_id' => $post['template_id'],
					'report_id'   => $post['id'],
					'variable_id' => $v['id'],
					'value'       => $value
				);
			}

			/* start saving process or return is_error_message()*/
			if (is_error_message()) {
				header('Location: reportit.php?action=report_edit&id=' . $post['id'] . '&tab=' . $post['tab']);

				exit;
			} else {
				/* save report config */
				$report_id = sql_save($report_data, 'plugin_reportit_reports');

				/* save additional report variables */
				foreach($var_data as $data) {
					if ($post['id'] == 0) {
						$data['report_id'] = $report_id;
					}

					sql_save($data, 'plugin_reportit_rvars');
				}
			}
	}

	header('Location: reportit.php?action=report_edit&id=' . (isset($report_id)? $report_id : $post['id']) . '&tab=' . $post['tab']);

	raise_message(1);
}

function report_edit() {
	global $templates, $timespans, $graph_timespans, $frequency, $archive, $tabs;
	global $weekday, $timezone, $shifttime, $shifttime2, $format;
	global $form_array_admin, $form_array_presets, $form_array_general, $form_array_email;
	global $rrdlist_actions, $link_array, $item_rows;

	if (!isset_request_var('tab')) {
		set_request_var('tab', 'general');
	}

	/* ================= input validation ================= */
	get_filter_request_var('tab', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '(general|presets|admin|email|items)')));
	get_filter_request_var('id');
	get_filter_request_var('template');
	/* ==================================================== */

	/* ==================== Checkpoint ==================== */
	my_report(get_request_var('id'));
	/* ==================================================== */

	/* load config settings if it's not a new one */
	if (!isempty_request_var('id')) {
		$report_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_reports
			WHERE id = ?',
			array(get_request_var('id')));

		$rrdlist_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_presets
			WHERE id = ?',
			array(get_request_var('id')));

		$report_recipients = db_fetch_assoc_prepared('SELECT *
			FROM plugin_reportit_recipients
			WHERE report_id = ?',
			array(get_request_var('id')));

		$header_label = '[edit: ' . $report_data['name'] . ']';

		/* update rrdlist_data */
		if ($rrdlist_data) {
			$rrdlist_data['timezone']   = array_search($rrdlist_data['timezone'],$timezone);
			$rrdlist_data['start_time'] = array_search($rrdlist_data['start_time'],$shifttime);
			$rrdlist_data['end_time']   = array_search($rrdlist_data['end_time'],$shifttime2);
			$rrdlist_data['start_day']  = array_search($rrdlist_data['start_day'],$weekday);
			$rrdlist_data['end_day']    = array_search($rrdlist_data['end_day'],$weekday);
		}

		/* update report_data array for getting compatible to Cacti's drawing functions */
		$report_data['preset_timespan'] = array_search($report_data['preset_timespan'], $timespans);

		/* replace all binary settings to get compatible with Cacti's draw functions */
		$rpm = array(
			'public',
			'sliding',
			'present',
			'enabled',
			'autorrdlist',
			'subhead',
			'graph_permission',
			'auto_email',
			'email_compression',
			'autoexport_no_formatting'
		);

		foreach($report_data as $key => $value) {
			if (in_array($key, $rpm)) {
				if ($value == 1) {
					$report_data[$key] = 'on';
				}
			}
		}

		/* load values for host_template_filter */
		$filter = db_fetch_cell_prepared('SELECT pre_filter
			FROM plugin_reportit_templates
			WHERE id = ?',
			array($report_data['template_id']));

		$tmp = db_fetch_assoc_prepared('SELECT id, description
			FROM plugin_reportit_templates
			WHERE pre_filter = ?',
			array($filter));
	} else {
		$header_label	= '[new]';
		$report_data = array();
		$report_data['user_id'] = my_id();
	}

	$id	= (isset_request_var('id') ? get_request_var('id') : '0');
	$rrdlist_data['id']= $id;

	if (isset_request_var('template')) {
		if (!isset($_SESSION['reportit']['template'])) {
			$_SESSION['reportit']['template'] = get_request_var('template');
		}
	}

	if (isset($report_data['template_id'])) {
		$template_id = $report_data['template_id'];
	} elseif (isset($_SESSION['reportit']['template'])) {
		$template_id = $_SESSION['reportit']['template'];
	} else {
		$template_id = 0;
	}

	/* leave if base template is locked */
	if ($template_id) {
		locked($template_id);
	}

	$report_data['template_id'] = $template_id;

	$report_data['template'] = db_fetch_cell_prepared('SELECT description
		FROM plugin_reportit_templates
		WHERE id = ?',
		array($template_id));

	/* start with HTML output */
	if ($id != 0) {
		/* unset the administration tab if user isn't a report admin */
		if (!re_admin()) {
			unset($tabs['admin']);
		}

		/* remove the email tab if emailing is deactivated globally */
		if (read_config_option('reportit_email') != 'on') {
			unset($tabs['email']);
		}
	} else {
		unset($tabs['admin']);
		unset($tabs['presets']);
		unset($tabs['email']);
	}

	/* draw the categories tabs on the top of the page */
	$current_tab = get_request_var('tab');

	if (cacti_sizeof($tabs)) {
		$i = 0;

		/* draw the tabs */
		print "<div class='tabs'><nav><ul role='tablist'>";

		foreach ($tabs as $tab => $name) {
			print "<li class='subTab'><a class='tab" . ($tab == $current_tab ? " selected'" : "'") .
				" href='" . html_escape(CACTI_PATH_URL .  'plugins/reportit/reportit.php' .
				'?action=report_edit' .
				'&id=' . $id .
				'&tab=' . $tab) .
				"'>" . html_escape($name) . '</a></li>';
		}

		print '</ul></nav></div>';
	}

	if (get_request_var('tab') !== 'items') {
		form_start('reportit.php');
	}

	html_start_box(__('Report Configuration (%s) %s', $tabs[$current_tab], $header_label, 'reportit'), '100%', '', '3', 'center', '');

	switch(get_request_var('tab')) {
		case 'presets':
			draw_edit_form(
				array(
					'config' => array('no_form_tag'=> true),
					'fields' => inject_form_variables($form_array_presets, $rrdlist_data, $report_data)
				)
			);

			break;
		case 'admin':
			draw_edit_form(
				array(
					'config' => array('no_form_tag'=> true),
					'fields' => inject_form_variables($form_array_admin, $report_data)
				)
			);

			break;
		case 'email':
			draw_edit_form(
				array(
					'config' => array('no_form_tag'=> true),
					'fields' => inject_form_variables($form_array_email, $report_data)
				)
			);

			html_end_box();

			html_start_box('Individual Email Recipients', '100%', '', '3', 'center', '');

			$display_text = array(
				'name'   => array('display' => __('Name', 'reportit'), 'width' => '50%'),
				'email'  => array('display' => __('Email', 'reportit')),
				'action' => array('display' => __('Action', 'reportit'))
			);

			html_header($display_text);

			if (cacti_sizeof($report_recipients)) {
				foreach ($report_recipients as $recipient) {
					form_alternate_row();
					print '<td>' . $recipient['name'] . '</td>';
					print '<td>' . $recipient['email'] . '</td>';
					print '<td class="right">';
					print '<a class="deletequery fa fa-times" href="reportit.php?action=remove&id=' . get_request_var('id') . '&rec=' . $recipient['id'] . '"></a></td>';
					print '</tr>';
				}
			} else {
				print '<tr><td colspan="3"><em>' . __('No recipients found', 'reportit') . '</em></td></tr>';
			}

			break;
		case 'items':
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
				'associated' => array(
					'filter' => FILTER_VALIDATE_REGEXP,
					'options' => array('options' => array('regexp' => '(true|false)')),
					'pageset' => true,
					'default' => 'true'
				),
				'sort_column' => array(
					'filter' => FILTER_CALLBACK,
					'default' => 'name_cache',
					'options' => array('options' => 'sanitize_search_string')
				),
				'sort_direction' => array(
					'filter' => FILTER_CALLBACK,
					'default' => 'ASC',
					'options' => array('options' => 'sanitize_search_string')
				),
			);

			validate_store_request_vars($filters, 'sess_reportit_rrdlist');
			/* ================= input validation ================= */

			/* ==================== checkpoint ==================== */
			my_report(get_filter_request_var('id'));
			locked(my_template(get_filter_request_var('id')));
			/* ==================================================== */

			if (get_request_var('rows') == '-1') {
				$rows = read_config_option('num_rows_table');
			} else {
				$rows = get_request_var('rows');
			}

			$report_data = db_fetch_row_prepared('SELECT *
				FROM plugin_reportit_reports
				WHERE id = ?',
				array(get_request_var('id')));

			$template_data = db_fetch_row_prepared('SELECT *
				FROM plugin_reportit_templates
				WHERE id = ?',
				array($report_data['template_id']));

			if (get_request_var('associated') != 'true') {
				$sql_where    = 'WHERE ri.report_id = ? AND dtd.data_template_id = ? AND dtd.local_data_id > 0';
				$sql_params[] = get_request_var('id');
				$sql_params[] = $template_data['data_template_id'];
			} else {
				$sql_where    = 'WHERE (ri.report_id = ? OR ri.report_id IS NULL) AND dtd.data_template_id = ? AND dtd.local_data_id > 0';
				$sql_params[] = get_request_var('id');
				$sql_params[] = $template_data['data_template_id'];
			}

			/* first filter comes from the report */
			if ($report_data['site_id'] > 0) {
				$sql_where   .= ($sql_where != '' ? ' AND ':'WHERE ') . ' h.site_id = ?';
				$sql_params[] = $report_data['site_id'];
			}

			if ($report_data['host_template_id'] > 0) {
				$sql_where   .= ($sql_where != '' ? ' AND ':'WHERE ') . ' h.host_template_id = ?';
				$sql_params[] = $report_data['host_template_id'];
			}

			if ($report_data['data_source_filter'] != '') {
				$sql_where   .= ($sql_where != '' ? ' AND ':'WHERE ') . ' dtd.name_cache LIKE ?';
				$sql_params[] = '%' . $report_data['data_source_filter'] . '%';
			}

			/* form the 'where' clause for our main sql query */
			if (get_request_var('filter') != '') {
				$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " dtd.name_cache LIKE ?";
				$sql_params[] = '%' . get_request_var('filter') . '%';
			}

			if (get_request_var('associated') != 'true') {
				$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " ri.id IS NOT NULL";
			}

			$total_rows = db_fetch_cell_prepared("SELECT COUNT(dtd.id)
				FROM data_template_data AS dtd
				INNER JOIN data_local AS dl
				ON dtd.local_data_id = dl.id
				INNER JOIN host AS h
				ON h.id = dl.host_id
				LEFT JOIN plugin_reportit_data_items AS ri
				ON dtd.local_data_id = ri.id
				$sql_where",
				$sql_params);

			$sql_order = get_order_string();
			$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

			$rrdlist = db_fetch_assoc_prepared("SELECT dtd.local_data_id AS id, dtd.name_cache,
				ri.id AS ri_id, ri.report_id, ri.description, ri.start_day, ri.end_day, ri.start_time, ri.end_time, ri.timezone
				FROM data_template_data AS dtd
				INNER JOIN data_local AS dl
				ON dtd.local_data_id = dl.id
				INNER JOIN host AS h
				ON h.id = dl.host_id
				LEFT JOIN plugin_reportit_data_items AS ri
				ON dtd.local_data_id = ri.id
				$sql_where
				$sql_order
				$sql_limit",
				$sql_params);

			/* define subheader description */
			$desc_array = array(
				'name_cache' => array(
					'display' => __('Data Source Name', 'reportit'),
					'sort' => 'ASC',
					'align' => 'left'
				),
				'id' => array(
					'display' => __('ID', 'reportit'),
					'sort' => 'ASC',
					'align' => 'left'
				),
				'nosort0' => array(
					'display' => __('Associated', 'reportit'),
					'align' => 'left'
				),
				'description' => array(
					'display' => __('Subhead', 'reportit'),
					'sort' => 'ASC',
					'align' => 'left'
				),
				'nosort1' => array(
					'display' => __('Shifttime (From - To)', 'reportit')
				),
				'nosort2' => array(
					'display' => __('Weekdays (From - To)', 'reportit')
				),
				'timezone' => array(
					'display' => __('Time Zone', 'reportit'),
					'sort' => 'ASC',
					'align' => 'left'
				),
			);

			/* start with HTML output */
			html_start_box('', '100%', '', '3', 'center', '');

			?>
			<tr class='odd'>
				<td>
				<form id='form_rrdlist' action='reportit.php?tab=items&id=<?php print get_request_var('id');?>'>
					<table class='filterTable'>
						<tr>
							<td>
								<?php print __('Search', 'reportit');?>
							</td>
							<td>
								<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
							</td>
							<td>
								<?php print __('RRDs', 'reportit');?>
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
									<input type='checkbox' id='associated' <?php print (get_request_var('associated') == 'true' || get_request_var('associated') == 'on' ? 'checked':'');?>>
									<label for='associated'><?php print __('Show All');?></label>
								</span>
							</td>
							<td>
							</td>
							<td>
								<span>
									<input type='submit' value='<?php print __esc_x('Button: use filter settings', 'Go', 'reportit');?>' id='refresh'>
									<input type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear', 'reportit');?>' id='clear'>
								</span>
							</td>
						</tr>
					</table>
				</form>
				<script type='text/javascript'>
				function applyFilter() {
					strURL  = 'reportit.php?action=report_edit&tab=items';
					strURL += '&id=<?php print get_request_var('id');?>';
					strURL += '&filter='+escape($('#filter').val());
					strURL += '&associated=' + $('#associated').is(':checked');
					strURL += '&rows='+$('#rows').val();
					loadUrl({ url: strURL });
				}

				function clearFilter() {
					strURL  = 'reportit.php?action=report_edit&tab=items'
					strURL += '&clear=1';
					strURL += '&id=<?php print get_request_var('id');?>';
					loadUrl({ url: strURL });
				}

				$(function() {
					$('#refresh').click(function() {
						applyFilter();
					});

					$('#clear').click(function() {
						clearFilter();
					});

					$('#associated').change(function() {
						applyFilter();
					});

					$('#form_rrdlist').submit(function(event) {
						event.preventDefault();
						applyFilter();
					});
				});
				</script>
				</td>
			</tr>
			<?php

			html_end_box();

			$nav = html_nav_bar('reportit.php?tab=items&id=' . get_request_var('id') . '&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($desc_array), __('Data Sources', 'reportit'), 'page', 'main');

			print $nav;

			form_start('reportit.php?tab=items&id=' . get_request_var('id'));

			html_start_box('', '100%', '', '3', 'center', '');

			html_header_sort_checkbox($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'reportit.php?action=report_edit&tab=items&id=' . get_request_var('id'));

			if (cacti_sizeof($rrdlist)) {
				foreach($rrdlist as $rrd) {
					if ($rrd['description'] == '') {
						$rrd['description'] = '-';
					}

					form_alternate_row( 'line' . $rrd['id'], true );

					if ($rrd['name_cache'] == NULL) {
						form_selectable_cell(__('Does not exist anymore', 'reportit'), $rrd['id']);
					} else {
						$link = "reportit.php?tab=items&action=rrdlist_edit&id=" . $rrd['id'] . "&report_id=" . get_request_var('id');

						form_selectable_cell(filter_value($rrd['name_cache'], get_request_var('filter'), $link), $rrd['id']);
					}

					form_selectable_cell($rrd['id'], $rrd['id']);

					if ($rrd['ri_id'] > 0) {
						form_selectable_cell('<span class="accessGranted">' . __('Included in Report', 'reportit') . '</span>', $rrd['id']);
					} else {
						form_selectable_cell('<span class="accessRestricted">' . __('Not Included in Report', 'reportit') . '</span>', $rrd['id']);
					}

					form_selectable_cell($rrd['description'], $rrd['id']);

					if ($rrd['start_time'] != '') {
						form_selectable_cell($rrd['start_time'] . ' - ' . $rrd['end_time'], $rrd['id']);
					} else {
						form_selectable_cell('-', $rrd['id']);
					}

					if ($rrd['start_time'] != '') {
						form_selectable_cell($rrd['start_day']  . ' - ' . $rrd['end_day'],  $rrd['id']);
					} else {
						form_selectable_cell('-', $rrd['id']);
					}

					if ($rrd['timezone'] != '') {
						form_selectable_cell($rrd['timezone'], $rrd['id']);
					} else {
						form_selectable_cell('-', $rrd['id']);
					}

					form_checkbox_cell(__('Select', 'reportit'), $rrd['id']);

					form_end_row();
				}
			} else {
				print "<tr><td colspan='6'><em>" . __('No data items found', 'reportit') . "</em></td></tr>";
			}

			html_end_box(true);

			if ($total_rows > $rows) {
				print $nav;
			}

			draw_actions_dropdown($rrdlist_actions);

			form_end();

			break;
		default:
			draw_edit_form(
				array(
					'config' => array('no_form_tag'=> true),
					'fields' => inject_form_variables($form_array_general, $report_data)
				)
			);

			api_scheduler_javascript();

			$template_variables = html_report_variables($id, $template_id );

			/* draw input fields for variables */
			if ($template_variables !== false) {
				draw_edit_form(
					array(
						'config' => array('no_form_tag'=> true),
						'fields' => $template_variables
					)
				);
			}
	}

	html_end_box();

	if (get_request_var('tab') !== 'items') {
		form_save_button('reportit.php');

		?>
		<script type='text/javascript'>
		$(function() {
			if ($('#_dynamic').length > 0) {
				dyn_general_tab();
				$('#dynamic').click(function() {
					dyn_general_tab();
				});
			}

			if ($('#enabled').length > 0) {
				dyn_admin_tab();
				$('#enabled').click(function() {
					dyn_admin_tab();
				});
			}

			$('#add_recipients_x').click(function(e) {
				e.preventDefault();
				$.get('reportit.php' +
					'?tab=email&action=recipient_add&id=' + $('#id').val() +
					'&report_email_address=' + encodeURI($('#email_address').val()) +
					'&report_email_recipient=' + encodeURI($('#email_recipient').val()))
				.done(function(data) {
					checkForLogout(data);
					$('#main').empty().hide();
					$('div[class^="ui-"]').remove();
					$('#main').html(data);
					applySkin();

					$('#email_address').attr('placeholder','<?php print __('Email address of a recipient (or comma separated list)', 'reportit');?>');
					$('#email_recipient').attr('placeholder','<?php print __('[OPTIONAL] Name of a recipient (or comma separated list of names)', 'reportit');?>');
				});
			});

			$('#email_address').attr('placeholder','<?php print __('Email address of a recipient (or comma separated list)', 'reportit');?>');
			$('#email_recipient').attr('placeholder','<?php print __('[OPTIONAL] Name of a recipient (or comma separated list of names)', 'reportit');?>');
		});

		function dyn_general_tab() {
			if ($('#dynamic').is(':checked')) {
				$('#start_date').val('yyyy-mm-dd');
				$('#start_date').prop('disabled', true);
				$('#end_date').val('yyyy-mm-dd');
				$('#end_date').prop('disabled', true);
				$('#present').prop('disabled', false);
				$('#timespan').prop('disabled', false);
			} else {
				$('#start_date').prop('disabled', false);
				$('#end_date').prop('disabled', false);
				$('#present').prop('disabled', true);
				$('#timespan').prop('disabled', true);
			}
		}

		function dyn_admin_tab() {
			if ($('#enabled').is(':checked')) {
				$('#frequency').prop('disabled', false);
				$('#autorrdlist').prop('disabled', false);

				if ($('#autoarchive').length) {
					$('#autoarchive').prop('disabled', false);
				}

				if ($('#email').length) {
					$('#email').prop('disabled', false);
				}

				if ($('#autoexport').length) {
					$('#autoexport').prop('disabled', false);
				}
			} else {
				$('#frequency').prop('disabled', true);
				$('#autorrdlist').prop('disabled', true);

				if ($('#autoarchive').length) {
					$('#autoarchive').prop('disabled', true);
				}

				if ($('#email').length) {
					$('#email').prop('disabled', true);
				}

				if ($('#autoexport').length) {
					$('#autoexport').prop('disabled', true);
				}
			}
		}
		</script>
		<?php
	}
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
		FROM plugin_reportit_data_items AS a
		LEFT JOIN data_template_data AS b
		ON b.local_data_id=a.id
		WHERE a.id = ?',
		array(get_request_var('id')));

	if ($rrdlist_data !== false && sizeof($rrdlist_data)) {
		set_request_var('report_id', $rrdlist_data['report_id']);
	}

	$header_label = __('Data Source [edit: %s]', $rrdlist_data['name_cache'], 'reportit');

	/* start with HTML output */

	form_start('reportit.php?action=rrdlist_edit&tab=items&id=' . get_request_var('id') . '&report_id=' . get_request_var('report_id'));

	html_start_box($header_label, '100%', '', '3', 'center', '');

	$form_array = array(
		'rrdlist_header1' => array(
			'friendly_name' => __('General', 'reportit'),
			'method' => 'spacer'
		),
		'save_component_rrdlist' => array(
			'method' => 'hidden',
			'value' => '1'
		),
		'rrdlist_subhead' => array(
			'friendly_name' => __('Subhead (optional)', 'reportit'),
			'description' => __('Define an additional subhead that should be on display under the interface description.<br> Following variables will be supported (without quotes): \'|t1|\' \'|t2|\' \'|tmz|\' \'|d1|\' \'|d2|\'', 'reportit'),
			'method' => 'textarea',
			'textarea_rows'	 => '2',
			'textarea_cols' => '45',
			'default' => '',
			'value' => $rrdlist_data['description']
		)
	);

	if ($enable_tmz) {
		$rrdlist_timezone = array(
			'friendly_name' => __('Time Zone', 'reportit'),
			'description' => __('Select the time zone your following shifttime information will be based on.', 'reportit'),
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

	$shift_array = array(
		'rrdlist_header2' => array(
			'friendly_name' => __('Working Time', 'reportit'),
			'method' => 'spacer',
		),
		'rrdlist_shifttime_start' => array(
			'friendly_name' => __('From', 'reportit'),
			'description' => __('The startpoint of duration you want to analyse', 'reportit'),
			'method' => 'drop_array',
			'default' => '0',
			'value' => array_search($rrdlist_data['start_time'], $shifttime),
			'array' => $shifttime
		),
		'rrdlist_shifttime_end' => array(
			'friendly_name' => __('To', 'reportit'),
			'description' => __('The end of analysing time.', 'reportit'),
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

	$weekday_array = array(
		'rrdlist_header3' => array(
			'friendly_name' => __('Working Days', 'reportit'),
			'method' => 'spacer',
		),
		'rrdlist_weekday_start' => array(
			'friendly_name' => __('From', 'reportit'),
			'description' => __('Define the band of days where shift STARTS!', 'reportit'),
			'method' => 'drop_array',
			'value' => array_search($rrdlist_data['start_day'], $weekday),
			'array' => $weekday
		),
		'rrdlist_weekday_end' => array(
			'friendly_name' => __('To', 'reportit'),
			'description' => __('Example: For a nightshift from Mo(22:30) till Sat(06:30) define Monday to Friday', 'reportit'),
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

	form_save_button('reportit.php?action=report_edit&tab=items&id=' . get_request_var('report_id'));
}

function form_actions() {
	global $report_actions, $report_states, $rrdlist_actions;

	/* ================= input validation ================= */
	get_filter_request_var('drp_action');
	get_filter_request_var('tab', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '(general|presets|admin|email|items)')));
	/* ==================================================== */

	if (get_request_var('tab') != 'items') {
		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));

			if (get_request_var('drp_action') == '1') { // Run Report Now
				$php_binary = read_config_option('path_php_binary');

				for ($i=0;($i<count($selected_items));$i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */
					//Only one report is allowed to run at the same time, so select the first one:
					$report_id = $selected_items[$i];

					if (intval($report_id) > 0) {
						//Update $_SESSION
						$_SESSION['run'] = '1';

						exec_background($php_binary, CACTI_PATH_BASE . '/plugins/reportit/poller_reportit.php --report-id=' . $report_id);
					}
				}

				sleep(2);
			} elseif (get_request_var('drp_action') == '2') { // Delete Report
				$report_datas = db_fetch_assoc('SELECT id
					FROM plugin_reportit_reports
					WHERE ' . array_to_sql_or($selected_items, 'id'));

				if (cacti_sizeof($report_datas) > 0) {
					$counter_data_items = 0;
					foreach ($report_datas as $report_data) {
						$counter_data_items += db_fetch_cell_prepared('SELECT COUNT(*)
							FROM plugin_reportit_data_items
							WHERE report_id = ?',
							array($report_data['id']));

						db_execute_prepared('DELETE FROM plugin_reportit_reports WHERE id = ?', array($report_data['id']));
						db_execute_prepared('DELETE FROM plugin_reportit_presets WHERE id = ?', array($report_data['id']));
						db_execute_prepared('DELETE FROM plugin_reportit_rvars WHERE report_id = ?', array($report_data['id']));
						db_execute_prepared('DELETE FROM plugin_reportit_recipients WHERE report_id = ?', array($report_data['id']));
						db_execute_prepared('DELETE FROM plugin_reportit_data_items WHERE report_id = ?', array($report_data['id']));
						db_execute('DROP TABLE IF EXISTS plugin_reportit_results_' . $report_data['id']);
					}

					if ($counter_data_items > 200) {
						db_execute('OPTIMIZE TABLE `plugin_reportit_data_items`');
					}
				}
			} elseif (get_request_var('drp_action') == '3') { // Duplicate Report
				for ($i=0;($i<count($selected_items));$i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					$report_data = db_fetch_row_prepared('SELECT *
						FROM plugin_reportit_reports
						WHERE id = ?', array($selected_items[$i]));

					$report_data['id']   = 0;
					$report_data['name'] = str_replace("<report_title>", $report_data['name'], get_request_var('report_addition'));
					$new_id = sql_save($report_data, 'plugin_reportit_reports');

					//Copy original rrdlist table  to new rrdlist table
					$data_items = db_fetch_assoc_prepared('SELECT *
						FROM plugin_reportit_data_items
						WHERE report_id = ?',
						array($selected_items[$i]));

					if (cacti_sizeof($data_items)) {
						foreach($data_items as $data_item) {
							$data_item['report_id'] = $new_id;
							sql_save($data_item, 'plugin_reportit_data_items', array('id', 'report_id'), false);
						}
					}

					/* duplicate the presets settings */
					$report_presets = db_fetch_row_prepared('SELECT *
						FROM plugin_reportit_presets
						WHERE id = ?',
						array($selected_items[$i]));

					$report_presets['id'] = $new_id;
					sql_save($report_presets, 'plugin_reportit_presets', 'id', false);

					/* duplicate list of recipients */
					$report_recipients = db_fetch_assoc_prepared('SELECT *
						FROM plugin_reportit_recipients
						WHERE report_id = ?',
						array($selected_items[$i]));

					if (cacti_sizeof($report_recipients)) {
						foreach($report_recipients as $recipient) {
							$recipient['id'] = 0;
							$recipient['report_id']=$new_id;
							sql_save($recipient, 'plugin_reportit_recipients');
						}
					}

					/* reset the new report configuration */
					reset_report($new_id);
				}
			}

			header('Location: reportit.php');
			exit;
		}

		//Set preconditions
		$limit_state = (get_filter_request_var('drp_action') == 1 ? ' LIMIT 1' : '');

		$report_ids = array();
		foreach($_POST as $key => $value) {
			if (strstr($key, 'chk_')) {
				//Fetch report id
				$id = substr($key, 4);
				// ================= input validation =================
				input_validate_input_number($id);
				// ====================================================
				$report_ids[] = $id;
			}
		}

		//Fetch report details
		if (cacti_sizeof($report_ids)) {
			$reports_sql = "SELECT id, description, state
				FROM plugin_reportit_reports
				WHERE id IN (" . implode(',',$report_ids) . ")
				AND state <> 1
				$limit_state";
			$reports = db_fetch_assoc($reports_sql);
		} else {
			$reports_sql = '';
			$reports = array();
		}
	} else {
		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

			if (get_request_var('drp_action') == '1') { // Remove Data Source from the Report
				$rrdlist_datas = db_fetch_assoc_prepared('SELECT id
					FROM plugin_reportit_data_items
					WHERE report_id = ?
					AND ' . array_to_sql_or($selected_items, 'id'),
					array(get_request_var('id')));

				if (cacti_sizeof($rrdlist_datas)) {
					foreach ($rrdlist_datas as $rrdlist_data) {
						db_execute_prepared('DELETE FROM plugin_reportit_data_items
							WHERE report_id = ?
							AND id = ?', array(get_request_var('id'), $rrdlist_data['id']));

						//Reset report
						reset_report(get_request_var('id'));
					}
				}
			} elseif (get_request_var('drp_action') == '3') { // Add Data Source to the Report
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

				foreach($selected_items as $rd) {
					$rrd .= "($rd $values),";
				}

				$rrd = substr($rrd, 0, strlen($rrd)-1);
				$columns = substr($columns, 1);

				/* save */
				db_execute("REPLACE INTO plugin_reportit_data_items ($columns) VALUES $rrd");

				/* reset report */
				reset_report(get_request_var('id'));
			} elseif (get_request_var('drp_action') == '2') { //Copy RRD's reference settings to all other RRDs
				$reference_items = unserialize(stripslashes(get_request_var('reference_items')), array('allowed_classes' => false));

				db_execute_prepared("UPDATE plugin_reportit_data_items
					SET `start_day` = ?, `end_day` = ?, `start_time` = ?,
					 `end_time` = ?, `timezone` = ? WHERE `report_id` = ?",
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

			header('Location: reportit.php?action=report_edit&tab=items&id=' . get_request_var('id'));
			exit;
		}
	}

	top_header();

	form_start('reportit.php?tab=' . get_request_var('tab') . '&id=' . get_filter_request_var('id'));

	if (get_request_var('tab') != 'items') {
		html_start_box($report_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

		if (cacti_sizeof($reports)) {
			if (get_request_var('drp_action') == '1') {
				$section = '<p>' . __('Click \'Continue\' to Run the following Report:', 'reportit') . '</p>';
			} elseif (get_request_var('drp_action') == '2') { //DELETE REPORT
				$section = '<p>' . __('Click \'Continue\' to Delete the following Reports:', 'reportit') . '</p>';
			} elseif (get_request_var('drp_action') == '3') { // DUPLICATE REPORT
				$section = '<p>' . __('Click \'Continue\' to duplicate the following Report configurations.  You may also change the title format during this operation.', 'reportit') . '</p>';
				$section .= '<p>' . __('Title Format:', 'reportit') . '</p>';
				$section .= '<p>' . form_text_box('report_addition', __('<report_title> (1)', 'reportit'), '', '255', '30', 'text') .'</p>';
			}
		}

		$report_ids = array();
		if ($reports === false || empty($reports)) {
			print "<tr><td class='textArea'><span class='textError'>" . __('You must select at least one unlocked, not running, report.', 'reportit') . "</span>$reports_sql</td></tr>";
			$save_html = "<input type='button' value='" . __('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";
		} else {
			print "<tr><td class='textArea'>$section</td></tr><tr><td>";
			print '<div class="itemlist"><ul>';

			foreach($reports as $report) {
				print '<li>' . $report['name'] . '</li>';
				$report_ids[] = $report['id'];
			}

			print '</ul></div>';
			print '</td></tr>';
			$save_html = "<input type='button' value='" . __('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue', 'reportit') . "'>";
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='tab' value='" . get_request_var('tab') . "'>
				<input type='hidden' name='selected_items' value='" . (isset($report_ids) ? serialize($report_ids) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";
	} else {
		html_start_box($rrdlist_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

		//Set preconditions
		$ds_list = array();
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
				$rrd_description = db_fetch_cell_prepared('SELECT dtd.name_cache
					FROM data_template_data AS dtd
					WHERE dtd.local_data_id = ?',
					array($id));

				$ds_list[] = $rrd_description;
			}
		}

		if (get_request_var('drp_action') == '1') { // Remove Data Source from Report
			print "<tr><td class='textArea'>
				<p>" . __('Click \'Continue\' to Remove the following Data Sources from the Report.', 'reportit') . '</p>';

			if (is_array($ds_list)) {
				print	'<p>' . __('List of selected Data Sources below.', 'reportit') . '<p>';

				print '<div class="itemlist"><ul>';

				foreach($ds_list as $key => $value) {
					print '<li>' . __esc('Data Source: %s', $value, 'reportit') . '</li>';
				}

				print '</ul></div>';
			}

			print '</td></tr>';
		} elseif (get_request_var('drp_action') == '3') { // Add Data Source to Report
			print "<tr><td class='textArea'>
				<p>" . __('Click \'Continue\' to Add the following Data Sources to the Report.', 'reportit') . '</p>';

			if (is_array($ds_list)) {
				print	'<p>' . __('List of selected Data Sources below.', 'reportit') . '<p>';

				print '<div class="itemlist"><ul>';

				foreach($ds_list as $key => $value) {
					print '<li>' . __esc('Data Source: %s', $value, 'reportit') . '</li>';
				}

				print '</ul></div>';
			}

			print '</td></tr>';
		} elseif (get_request_var('drp_action') == '2') { // Copy Report Settings
			// Copy the settings from selected RRD to all
			//Select the first selected checkbox as reference. The others will be ignored.
			//Fetch first's settings

			if (isset($rrd_ids[0])) {
				$rrd_settings = db_fetch_assoc_prepared('SELECT b.name_cache, a.*
					FROM plugin_reportit_data_items AS a
					LEFT JOIN data_template_data AS b
					ON b.local_data_id = a.id
					WHERE a.id = ?
					AND a.report_id = ?',
					array($rrd_ids[0], get_request_var('id')));

				print "<tr><td class='textArea'>
					<p>" . __('Click \'Continue\' to Copy the Settings to the other Data Sources.', 'reportit') . '</p>';

				print __('Selected Data Source as Reference:', 'reportit');

				print '<b><br>&#160' . $rrd_settings[0]['name_cache'] . '</b><p></p>';
				print __('Time Zone:', 'reportit') . '<br>&#160 <b>' . $rrd_settings[0]['timezone'] . '</b><p></p>';
				print __('Weekdays:', 'reportit')  . '<br>&#160 <b>' . ($rrd_settings[0]['start_day'] . '-' . $rrd_settings[0]['end_day'])   . '</b><p></p>';
				print __('Shifttime:', 'reportit') . '<br>&#160 <b>' . ($rrd_settings[0]['start_time'] . '-' . $rrd_settings[0]['end_time']) . '</b><p></p>';

				print '</td></tr>';
			}
		}

		if ($ds_list === false || !is_array($ds_list) || empty($ds_list)) {
			print "<tr><td class='odd''><span class='textError'>" . __('You must select at least one Report.', 'reportit') . '</span></td></tr>';

			$save_html = "<input type='button' value='" . __('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";
		} else {
			$save_html = "<input type='button' value='" . __('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;
				<input type='submit' value='" . __('Continue', 'reportit') . "'>";
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($rrd_ids) ? serialize($rrd_ids) : '') . "'>
				<input type='hidden' name='tab' value='items'>
				<input type='hidden' name='reference_items' value='" . (isset($rrd_settings) ? serialize($rrd_settings) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";
	}

	html_end_box();

	form_end();

	bottom_footer();
}
