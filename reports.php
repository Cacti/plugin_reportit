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
include_once(REPORTIT_BASE_PATH . '/lib_int/const_runtime.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/const_reports.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_html.php');

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
	case 'add':
		top_header();
		report_wizard();
		bottom_footer();
		break;
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
	global $config;

	$templates_list = array();
	$templates      = array();

	$templates_list = db_fetch_assoc('SELECT id, description
		FROM reportit_templates
		WHERE locked=0');

	top_header();

	if (isset($_SESSION['reportit'])) unset($_SESSION['reportit']);

	html_start_box(__('New Report'), '60%', '', '3', 'center', '');

	form_start('cc_reports.php');

	if (sizeof($templates_list) == 0) {
		print "<tr class='even'>
			<td>
				<span class='textError'>" . __('There are no report templates available.') . "</span>
			</td>
		</tr>";

		$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>";
	} else {
		$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue') . "' title='" . __('Create a new report') . "'>";

		foreach($templates_list as $tmp) {
			$templates[$tmp['id']] = $tmp['description'];
		}

		print "<tr class='even'>
			<td>
				<p>" . __('Choose a template this report should depend on.') . "</p>
			</td>
		<td>";

		form_dropdown('template', $templates, '', '', '', '', '');

		print '</td>
		</tr>';
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='report_edit'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	bottom_footer();
}

function report_filter() {
	global $item_rows;

	html_start_box( __('Report Filters'), '100%', '', '3', 'center', 'cc_reports.php?action=report_edit');
	?>
	<tr class='even'>
		<td>
			<form id='form_reports' action='cc_reports.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
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
						<input type='button' value='<?php print __esc_x('Button: use filter settings', 'Go');?>' id='refresh'>
					</td>
					<td>
						<input type='button' value='<?php print __esc_x('Button: reset filter settings', 'Clear');?>' id='clear'>
					</td>
					<td>
						<input type='button' value='<?php print __esc_x('Button: import reports', 'Import');?>' id='import'>
					</td>
					<td>
						<input type='button' value='<?php print __esc_x('Button: export reports', 'Export');?>' id='export'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_filter_request_var('page');?>'>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL = 'cc_reports.php?filter='+
					escape($('#filter').val())+
					'&rows='+$('#rows').val()+
					'&page='+$('#page').val()+
					'&header=false';
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = 'cc_reports.php?clear=1&header=false';
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

function standard() {
	global $config, $report_actions, $minutes, $link_array, $link_array_admin;

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
			'default' => 'description',
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

	validate_store_request_vars($filters, 'sess_cc_reports');
	/* ================= Input validation ================= */

	if ($reportAdmin) {
		/* fetch user names */
		$ownerlist = db_fetch_assoc('SELECT DISTINCT a.user_id as id, c.username
			FROM reportit_reports AS a
			LEFT JOIN reportit_templates AS b
			ON b.id = a.template_id
			LEFT JOIN user_auth AS c
			ON c.id = a.user_id
			ORDER BY c.username');

		/* fetch template list */
		$sql = 'SELECT DISTINCT b.id, b.description
			FROM reportit_reports AS a
			INNER JOIN reportit_templates AS b
			ON b.id = a.template_id';

		if (get_request_var('owner') !== '-1' & !isempty_request_var('owner')) {
			$sql .= ' WHERE a.user_id = ' . get_request_var('owner') . ' ORDER BY b.description';
			$templatelist = db_fetch_assoc($sql);

			if (sizeof($templatelist)>0) {
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
	if (strlen(get_request_var('filter'))) {
		$affix .= " WHERE a.description LIKE '%" . get_request_var('filter') . "%'";
	} else {
		/* filter nothing, but also use 'where' clause */
		$affix .= " WHERE a.description LIKE '%'";
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

	$sql = "SELECT COUNT(a.id) FROM reportit_reports AS a $affix";

	$total_rows = db_fetch_cell($sql);

    $sql = 'SELECT a.*, b.description AS template_description, c.ds_cnt, d.username, b.locked
		FROM reportit_reports AS a
		LEFT JOIN reportit_templates AS b
		ON b.id = a.template_id
		LEFT JOIN
		(SELECT report_id, count(*) as ds_cnt FROM `reportit_data_items` GROUP BY report_id) AS c
		ON c.report_id = a.id
		LEFT JOIN user_auth AS d
		ON d.id = a.user_id' . $affix .
		' ORDER BY ' . get_request_var('sort_column') . ' ' . get_request_var('sort_direction') .
		' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

    $report_list = db_fetch_assoc($sql);

	$columns = ($reportAdmin)? 9 : 7;

	/* start with HTML output */
	report_filter();

	$nav = html_nav_bar('cc_reports.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('Reports'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	if ($reportAdmin) {
		$desc_array = array(
			'description'          => array('display' => __('Description'), 'sort' => 'ASC',  'align' => 'left'),
			'username'             => array('display' => __('Owner'),       'sort' => 'ASC',  'align' => 'left'),
			'template_description' => array('display' => __('Template'),    'sort' => 'ASC',  'align' => 'left'),
			'nosort0'              => array('display' => __("Period %s from - to", $tmz)),
			'nosort1'              => array('display' => __("Last run %s/ Runtime [s]", $tmz)),
			'public'               => array('display' => __('Public'),      'sort' => 'ASC',  'align' => 'left'),
			'scheduled'            => array('display' => __('Scheduled'),   'sort' => 'ASC',  'align' => 'left'),
			'ds_cnt'               => array('display' => __('Data Items'),  'sort' => 'DESC', 'align' => 'right'),
		);

		html_header_sort_checkbox($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'cc_reports.php');
	} else {
		$desc_array = array(
			'description'          => array('display' => __('Description'),  'sort' => 'ASC',  'align' => 'left'),
			'template_description' => array('display' => __('Template'),     'sort' => 'ASC',  'align' => 'left'),
			'nosort0'              => array('display' => __("Period %s from - to", $tmz)),
			'nosort1'              => array('display' => __("Last run %s/ Runtime [s]", $tmz)),
			'public'               => array('display' => __('Public'),       'sort' => 'ASC',  'align' => 'left'),
			'ds_cnt'               => array('display' => __('Data Objects'), 'sort' => 'DESC', 'align' => 'right'),
		);

		html_header_sort_checkbox($desc_array, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'cc_reports.php');
	}

	if (sizeof($report_list)) {
		foreach($report_list as $report) {
			form_alternate_row('line' . $report['id'], true);
			?>
			<td>
				<a class='linkEditMain' href='cc_reports.php?action=report_edit&id=<?php print $report['id'];?>'>
				<?php print $report['description'];
				if ($report['in_process']) print "<b style='color: #FF0000'>&nbsp;" . __('*In process*') . "</b>";
				?>
				</a>
			</td>
			<?php if ($reportAdmin) print '<td>' . $report['username'] . '</td>'; ?>
			<td><?php print $report['template_description']; ?></td>
			<td>
			<?php
		    if ($report['sliding']== true && $report['last_run'] == 0) {
				$dates = rp_get_timespan($report['preset_timespan'], $report['present'], $enable_tmz);
				print (date(config_date_format(), strtotime($dates['start_date'])) . " - " . date(config_date_format(), strtotime($dates['end_date'])));
		    } else {
				print (date(config_date_format(), strtotime($report['start_date'])) . " - " . date(config_date_format(), strtotime($report['end_date'])));
		    }
			?>
			</td>
			<td>
			<?php
		    if ($report['last_run'] == '0000-00-00 00:00:00') {
		      	print __("- not available -");
		    } else {
				list($date, $time) = explode(' ', $report['last_run']);
		        print (date(config_date_format(), strtotime($date)) . '&nbsp;' . $time . '&nbsp;&nbsp;/&nbsp;' . $report['runtime']);
		    }
			?>
			</td>
			<td><?php html_checked_with_arrow($report['public']);?></td>
			<?php
			if ($reportAdmin) {
				print "<td>";
				html_checked_with_arrow($report['scheduled']);
				print "</td>";
			}

			if ($report['ds_cnt'] != NULL) {
				$link = "cc_rrdlist.php?&id={$report['id']}";
				$msg  = "edit ({$report['ds_cnt']})";
			} else {
				$link = "cc_items.php?&id={$report['id']}";
				$msg  = "add";
			}

			print "<td><a class='linkEditMain' href='$link'>$msg</a></td>";

			if (!$report['locked'] && !$report['in_process']) {
				?>
				<td style="<?php print get_checkbox_style();?>" width="1%" align="right">
				<input type='checkbox' style='margin: 0px;' name='chk_<?php print $report["id"];?>' title="Select">
				</td>
				<?php
			} else {
                print "<td align='center'>";
                html_checked_with_icon(true, 'lock.gif', __('Template has been locked temporarily'));
                print "</td>";
			}

			?>
			</tr>
			<?php
		}
	} else {
		print "<tr><td colspan='9'><em>" . __('No reports') . "</em></td></tr>\n";
	}

	html_end_box(true);

	if ($total_rows > $rows) print $nav;

	draw_actions_dropdown($report_actions);
}

function remove_recipient() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('rec');
	/* ==================================================== */

	/* ==================== Checkpoint ==================== */
	my_report(get_request_var('id'));
	/* ==================================================== */

	db_execute_prepared('DELETE FROM reportit_recipients
		WHERE id = ?
		AND report_id = ?',
		array(get_request_var('rec'), get_request_var('id')));

	header('Location: cc_reports.php?action=report_edit&id=' . get_request_var('id') . '&tab=email');
	exit;
}

function form_save() {
	global 	$templates, $timespans, $frequency, $timezone, $shifttime, $shifttime2, $weekday, $format;

	$owner 	= array();

	$sql = "SELECT DISTINCT a.id, a.username as name FROM user_auth AS a
		INNER JOIN user_auth_realm AS b
		ON a.id = b.user_id WHERE (b.realm_id = " . REPORTIT_USER_OWNER . " OR b.realm_id = " . REPORTIT_USER_VIEWER . ")
		ORDER BY username";
	$owner = db_custom_fetch_assoc($sql, 'id', false);

	/* ================= Input Validation ================= */
	input_validate_input_whitelist(get_request_var('tab'), array('general', 'presets', 'admin', 'email'));
	input_validate_input_number(get_request_var('id'));

	/* stop if user is not authorised to save a report config */
	if (get_request_var('id')!=0) my_report(get_request_var('id'));
	if (!re_owner()) die_html_custom_error(__('Not authorised'), true); //this should normally done by Cacti itself

	/* check for the type of saving if it was sent through the email tab */
	$add_recipients = (array_key_exists('add_recipients_x', $_REQUEST)) ? true : false;

	switch(get_request_var('tab')) {
	case 'presets':
	 	input_validate_input_blacklist(get_request_var('id'),array(0));
		input_validate_input_key(get_request_var('rrdlist_timezone'), $timezone, true);
		input_validate_input_key(get_request_var('rrdlist_shifttime_start'), $shifttime);
		input_validate_input_key(get_request_var('rrdlist_shifttime_end'), $shifttime2);
		input_validate_input_key(get_request_var('rrdlist_weekday_start'), $weekday);
		input_validate_input_key(get_request_var('rrdlist_weekday_end'), $weekday);

		form_input_validate(get_request_var('rrdlist_subhead'), 'rrdlist_subhead', '' ,true,3);

		input_validate_input_number(get_request_var('host_template_id'));
		form_input_validate(get_request_var('data_source_filter'), 'data_source_filter'	, '', true, 3);

		break;
	case 'admin':
		input_validate_input_blacklist(get_request_var('id'),array(0));
		input_validate_input_key(get_request_var('report_owner'), $owner);

		if (read_config_option('reportit_operator')) {
			input_validate_input_key(get_request_var('report_schedule_frequency'), $frequency, true);
			input_validate_input_limits(get_request_var('report_autoarchive'),0,1000);

			if (read_config_option('reportit_auto_export')) {
				input_validate_input_limits(get_request_var('report_autoexport_max_records'),0,1000);
				input_validate_input_key(get_request_var('report_autoexport'), $format, true);
			}
		}

		break;
	case 'email':
		if (!$add_recipients) {
			form_input_validate(get_request_var('report_email_subject'), 'report_email_subject', '' ,false,3);
			form_input_validate(get_request_var('report_email_body'), 'report_email_body', '', false, 3);
			input_validate_input_key(get_request_var('report_email_format'), $format);
		} else {
			/* if javascript is disabled */
			form_input_validate(get_request_var('report_email_address'), 'report_email_address', '', false, 3);
		}

		break;
	default:
		input_validate_input_number(get_request_var('template_id'));
		input_validate_input_key(get_request_var('preset_timespan'), $timespans, true);

		/* if template is locked we don't know if the variables have been changed */
		locked(get_request_var('template_id'));

		form_input_validate(get_request_var('report_description'), 'report_description', '' ,false,3);

		/* validate start- and end date if sliding time should not be used */
		if (!isset_request_var('report_dynamic')) {
			if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', get_request_var('report_start_date'))) {
				session_custom_error_message('report_start_date', 'Invalid date');
			}

			if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', get_request_var('report_end_date'))) {
				session_custom_error_message('report_end_date', 'Invalid date');
			}

			if (!is_error_message()) {
				list($ys, $ms, $ds) = explode('-', get_request_var('report_start_date'));
				list($ye, $me, $de) = explode('-', get_request_var('report_end_date'));

				if (!checkdate($ms, $ds, $ys)) session_custom_error_message('report_start_date', 'Invalid date');
				if (!checkdate($me, $de, $ye)) session_custom_error_message('report_end_date', 'Invalid date');

				if (($start_date = mktime(0,0,0,$ms,$ds,$ys)) > ($end_date = mktime(0,0,0,$me,$de,$ye)) || $ys > $ye || $ys > date('Y')) {
					session_custom_error_message('report_start_date', 'Start date lies ahead');
				}
				if (($end_date = mktime(0,0,0,$me,$de,$ye)) > mktime() || $ye > date('Y')) {
					session_custom_error_message('report_start_date', 'End date lies ahead');
				}
			}
		}

		if (!read_config_option('reportit_operator')) {
			input_validate_input_key(get_request_var('report_schedule_frequency'), $frequency, true);
		    input_validate_input_limits(get_request_var('report_autoarchive'),0,1000);

		    if (read_config_option('reportit_auto_export')) {
				input_validate_input_limits(get_request_var('report_autoexport_max_records'),0,1000);
				input_validate_input_key(get_request_var('report_autoexport'), $format, true);
		    }
		}
	}
	/* ==================================================== */

	/* return if validation failed */
	if (is_error_message()) {
		header('Location: cc_reports.php?action=report_edit&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
		exit;
	}

	switch(get_request_var('tab')) {
	case 'presets':
		$rrdlist_data['id']         = get_request_var('id');
		$rrdlist_data['start_day']  = $weekday[get_request_var('rrdlist_weekday_start')];
		$rrdlist_data['end_day']    = $weekday[get_request_var('rrdlist_weekday_end')];
		$rrdlist_data['start_time'] = $shifttime[get_request_var('rrdlist_shifttime_start')];
		$rrdlist_data['end_time']   = $shifttime2[get_request_var('rrdlist_shifttime_end')];

		if (isset_request_var('rrdlist_timezone')) {
			$rrdlist_data['timezone'] = $timezone[get_request_var('rrdlist_timezone')];
		}

		if (isset_request_var('rrdlist_subhead')) {
			$rrdlist_data['description'] = get_request_var('rrdlist_subhead');
		}

		$report_data['id']                 = get_request_var('id');
		$report_data['host_template_id']   = get_request_var('host_template_id');
		$report_data['data_source_filter'] = get_request_var('data_source_filter');

		/* save settings */
		sql_save($report_data, 'reportit_reports');
		sql_save($rrdlist_data, 'reportit_presets', 'id', false);

		break;
	case 'admin':
		$report_data['id']               = get_request_var('id');
		$report_data['user_id']          = get_request_var('report_owner');
		$report_data['graph_permission'] = isset_request_var('report_graph_permission') ? 1 : 0;

		/* save the settings for scheduled reporting if the admin is configured to do this job */
		if (read_config_option('reportit_operator')) {
			$report_data['scheduled']   = isset_request_var('report_schedule') ? 1 : 0;
			$report_data['autorrdlist'] = isset_request_var('report_autorrdlist') ? 1 : 0;
			$report_data['frequency']   = isset_request_var('report_schedule_frequency') ? $frequency[get_request_var('report_schedule_frequency')] : '';
			$report_data['autoarchive'] = isset_request_var('report_autoarchive') ? get_request_var('report_autoarchive') : 0;
			$report_data['auto_email']  = isset_request_var('report_email') ? 1 : 0;
			$report_data['autoexport']  = isset_request_var('report_autoexport') ? get_request_var('report_autoexport') : '';
			$report_data['autoexport_max_records']   = isset_request_var('report_autoexport_max_records') ? get_request_var('report_autoexport_max_records') : 0;
			$report_data['autoexport_no_formatting'] = isset_request_var('report_autoexport_no_formatting') ? 1 : 0;
		}

		/* save settings */
		sql_save($report_data, 'reportit_reports');

		break;
	case 'email':
		if (!$add_recipients) {
			$report_data['id']            = get_request_var('id');
			$report_data['email_subject'] = get_request_var('report_email_subject');
			$report_data['email_body']    = get_request_var('report_email_body');
			$report_data['email_format']  = get_request_var('report_email_format');

			/* save settings */
			sql_save($report_data, 'reportit_reports');
		} else {
			$id      = get_request_var('id');
			$columns = '(report_id, email, name)';
			$values  = '';

			if (strpos(get_request_var('report_email_address'),';')) {
				$addresses = explode(';',get_request_var('report_email_address') );
			} elseif (strpos(get_request_var('report_email_address'),',')) {
				$addresses = explode(',',get_request_var('report_email_address') );
			} else {
				$addresses[] = get_request_var('report_email_address');
			}

			if (strpos(get_request_var('report_email_recipient'),';')) {
				$recipients = explode(';',get_request_var('report_email_recipient') );
			} elseif (strpos(get_request_var('report_email_recipient'),',')) {
				$recipients = explode(',',get_request_var('report_email_recipient') );
			} else {
				$recipients[] = get_request_var('report_email_recipient');
			}

			if (sizeof($addresses)>0) {
				foreach($addresses as $key => $value) {
					$value = trim($value);

					if (!preg_match("/(^[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*@[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*\\.[a-zA-Z]{2,3}$)/", $value)) {
						session_custom_error_message('report_email_address', 'Invalid email address');
					}

					if (array_key_exists($key, $recipients) && $recipients[$key] != '[OPTIONAL] - Name of a recipient (or list of names) -') {
						$name = db_qstr($recipients[$key]);
					} else {
						$name = '';
					}

					$values .= "('$id', '$value', '$name'),";
				}

				$values = substr($values, 0, strlen($values)-1);

				if (!is_error_message()) {
					db_execute("INSERT INTO reportit_recipients $columns VALUES $values");
				}
			}
		}

		break;
	default:
		$report_data['id']              = get_request_var('id');
		$report_data['description']     = get_request_var('report_description');
		$report_data['template_id']     = get_request_var('template_id');
		$report_data['public']          = isset_request_var('report_public') ? 1 : 0;

		$report_data['preset_timespan'] = isset_request_var('report_timespan') ? $timespans[get_request_var('report_timespan')] : '';
		$report_data['last_run']        = '0000-00-00 00:00:00';

		$report_data['start_date']      = isset_request_var('report_start_date') ? get_request_var('report_start_date') : '0000-00-0';
		$report_data['end_date']        = isset_request_var('report_end_date') ? get_request_var('report_end_date') : '0000-00-0';

		$report_data['sliding']         = isset_request_var('report_dynamic') ? 1 : 0;
		$report_data['present']         = isset_request_var('report_present') ? 1 : 0;

		/* define the owner if it's a new configuration */
		if (get_request_var('id') == 0) $report_data['user_id'] = my_id();

		/* save the settings for scheduled reporting if owner has the rights to do this */
		if (!read_config_option('reportit_operator')) {
			$report_data['scheduled']   = isset_request_var('report_schedule') ? 1 : 0;
			$report_data['autorrdlist'] = isset_request_var('report_autorrdlist') ? 1 : 0;
			$report_data['frequency']   = isset_request_var('report_schedule_frequency') ? $frequency[get_request_var('report_schedule_frequency')] : '';
			$report_data['autoarchive'] = isset_request_var('report_autoarchive') ? get_request_var('report_autoarchive') : 0;
			$report_data['auto_email']  = isset_request_var('report_email') ? 1 : 0;
			$report_data['autoexport']  = isset_request_var('report_autoexport') ? get_request_var('report_autoexport') : '';

			$report_data['autoexport_max_records']   = isset_request_var('report_autoexport_max_records') ? get_request_var('report_autoexport_max_records') : 0;
			$report_data['autoexport_no_formatting'] = isset_request_var('report_autoexport_no_formatting') ? 1 : 0;
		}

		//Now we've to keep our variables
		$vars     = array();
		$rvars    = array();
		$var_data = array();

		foreach($_POST as $key => $value) {
			if (strstr($key, 'var_')) {
				$id = substr($key, 4);
				$vars[$id] = $value;
			}
		}

		$rvars = db_fetch_assoc_prepared('SELECT a.*, b.id AS b_id, b.value
			FROM reportit_variables AS a
			LEFT JOIN reportit_rvars AS b
			ON a.id = b.variable_id
			AND report_id = ?
			WHERE a.template_id = ?',
			array(get_request_var('id'), get_request_var('template_id')));

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
				'template_id' => get_request_var('template_id'),
				'report_id'   => get_request_var('id'),
				'variable_id' => $v['id'],
				'value'       => $value
			);
		}

		/* start saving process or return */
		if (is_error_message()) {
			header('Location: cc_reports.php?action=report_edit&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
		} else {
			/* save report config */
			$report_id = sql_save($report_data, 'reportit_reports');

			/* save addtional report variables */
			foreach($var_data as $data) {
				if (get_request_var('id') == 0) $data['report_id'] = $report_id;
				sql_save($data, 'reportit_rvars');
			}
		}
	}

	header('Location: cc_reports.php?action=report_edit&id=' . (isset($report_id)? $report_id : get_request_var('id')) . '&tab=' . get_request_var('tab'));

	raise_message(1);
}

function report_edit() {
	global $config, $templates, $timespans, $graph_timespans, $frequency, $archive, $tabs,
		$weekday, $timezone, $shifttime, $shifttime2, $format,
		$form_array_admin, $form_array_presets, $form_array_general, $form_array_email;

	if (!isset_request_var('tab')) {
		set_request_var('tab', 'general');
	}

	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('template');
	/* ==================================================== */

	/* ==================== Checkpoint ==================== */
	my_report(get_request_var('id'));
	/* ==================================================== */

	/* load config settings if it's not a new one */
	if (!isempty_request_var('id')) {
		$report_data = db_fetch_row_prepared('SELECT *
			FROM reportit_reports
			WHERE id = ?',
			array(get_request_var('id')));

		$rrdlist_data = db_fetch_row_prepared('SELECT *
			FROM reportit_presets
			WHERE id = ?',
			array(get_request_var('id')));

		$report_recipients = db_fetch_assoc_prepared('SELECT *
			FROM reportit_recipients
			WHERE report_id = ?',
			array(get_request_var('id')));

		$header_label = '[edit: ' . $report_data['description'] . ']';

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
		$report_data['frequency']       = array_search($report_data['frequency'], $frequency);

		/* replace all binary settings to get compatible with Cacti's draw functions */
		$rpm = array(
			'public',
			'sliding',
			'present',
			'scheduled',
			'autorrdlist',
			'subhead',
			'graph_permission',
			'auto_email',
			'email_compression',
			'autoexport_no_formatting'
		);

		foreach($report_data as $key => $value) {
			if (in_array($key,$rpm)) {
				if ($value == 1) {
					$report_data[$key] = 'on';
				}
			}
		}

		/* setup blue link */
		$href 	= 'cc_items.php?id=' . get_request_var('id');
		$text 	= __('Add data items');

		$link[] = array(
			'href' => $href,
			'text' => $text
		);

		/* load values for host_template_filter */
		$filter = db_fetch_cell_prepared('SELECT pre_filter
			FROM reportit_templates
			WHERE id = ?',
			array($report_data['template_id']));

		$tmp = db_fetch_assoc_prepared('SELECT id, description
			FROM reportit_templates
			WHERE pre_filter = ?',
			array($filter));
	} else {
		$header_label	= '[new]';
		$report_data = array();
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
		FROM reportit_templates
		WHERE id = ?',
		array($template_id));

	if (!array_key_exists('auto_email',$report_data)) $report_data['auto_email'] = false;

	/* start with HTML output */
	if ($id != 0) {
		html_blue_link($link, false);

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

	if (sizeof($tabs)) {
		$i = 0;

		/* draw the tabs */
		print "<div class='tabs'><nav><ul role='tablist'>\n";

		foreach (array_keys($tabs) as $tab_short_name) {
			print "<li class='subTab'><a class='tab" . (($tab_short_name == $current_tab) ? " selected'" : "'") .
				" href='" . htmlspecialchars($config['url_path'] .  '/plugins/reportit/cc_reports.php?action=report_edit&id=' . $id .
				'&tab=' . $tab_short_name) .
				"'>" . $tabs[$tab_short_name] . "</a></li>\n";

            $i++;
		}

        print "</ul></nav></div>\n";
	}

	html_start_box(__('Report Configuration (%s) %s', $tabs[$current_tab], $header_label), '100%', '', '2', 'center', '');

	switch(get_request_var('tab')) {
	case 'presets':
		draw_edit_form(
			array(
				'config' => array(),
				'fields' => inject_form_variables($form_array_presets, $rrdlist_data, $report_data)
			)
		);

		break;
	case 'admin':
		draw_edit_form(
			array(
				'config' => array(),
				'fields' => inject_form_variables($form_array_admin, $report_data)
			)
		);

		break;
	case 'email':
		draw_edit_form(
			array(
				'config' => array(),
				'fields' => inject_form_variables($form_array_email, $report_data)
			)
		);

		html_end_box();

		html_start_box('', '100%', '', '2', 'center', '');

		$display_text = array('Name', 'Email', '');

		html_header($display_text);

		if (sizeof($report_recipients)) {
			foreach ($report_recipients as $recipient) {
				form_alternate_row();
				print '<td>' . $recipient['name'] . '</td>';
				print '<td>' . $recipient['email'] . '</td>';
				print "<td class='right'><a class='pic fa fa-delete' href='cc_reports.php?action=remove&id=" . get_request_var('id') . '&rec=' . $recipient['id'] . '></a></td>';

				form_end_row();
			}
		} else {
			print '<tr><td colspan="3"><em>' . __('No recipients found') . '</em></td></tr>';
		}

		break;
	default:
		draw_edit_form(
			array(
				'config' => array(),
				'fields' => inject_form_variables($form_array_general, $report_data)
			)
		);

		$template_variables = html_report_variables($id, $template_id );

		/* draw input fields for variables */
		if ($template_variables !== false) {
			draw_edit_form(
				array(
					'config' => array(),
					'fields' => $template_variables
				)
			);
		}
	}

	html_end_box();
	form_save_button('cc_reports.php');

	?>
	<script type='text/javascript'>

	if ($('#report_dynamic').length > 0) {
		dyn_general_tab();
		$('#report_dynamic').click(function() {
			dyn_general_tab();
		});
	}

	if ($('#report_schedule').length > 0) {
		dyn_admin_tab();
		$('#report_schedule').click(function() {
			dyn_admin_tab();
		});
	}

	function start_input(name) {
		if (name == 'report_email_address') {
			text = '<?php print __('- Email address of a recipient (or list of names) -');?>';
		} else {
			text = '<?php print __('[OPTIONAL] - Name of a recipient (or list of names) -');?>';
		}

		if ($('#'+name).val() == text) {
			$('#'+name).val('');
			$('#'+name).css('text-align', 'left');
		}
	}

	function leave_input(name) {
		if (name == 'report_email_address') {
			text = '<?php print __('- Email address of a recipient (or list of names) -');?>';
		} else {
			text = '<?php print __('[OPTIONAL] - Name of a recipient (or list of names) -');?>';
		}

		if ($('#'+name).val() == '') {
			$('#'+name).val(test);
			$('#'+name).css('text-align', 'center');
		}
	}

	function dyn_general_tab() {
		if ($('#report_dynamic').is(':checked')) {
			$('#report_start_date').val('yyyy-mm-dd');
			$('#report_start_date').prop('disabled', true);
			$('#report_end_date').val('yyyy-mm-dd');
			$('#report_end_date').prop('disabled', true);
			$('#report_present').prop('disabled', false);
			$('#report_timespan').prop('disabled', false);
		} else {
			$('#report_start_date').prop('disabled', false);
			$('#report_end_date').prop('disabled', false);
			$('#report_present').prop('disabled', true);
			$('#report_timespan').prop('disabled', true);
		}
	}

	function dyn_admin_tab() {
		if ($('#report_schedule').is(':checked')) {
			$('#report_schedule_frequency').prop('disabled', false);
			$('#report_autorrdlist').prop('disabled', false);

			if ($('#report_autoarchive').length) {
				$('#report_autoarchive').prop('disabled', false);
			}

			if ($('#report_email').length) {
				$('#report_email').prop('disabled', false);
			}

			if ($('#report_autoexport').length) {
				$('#report_autoexport').prop('disabled', false);
			}
		} else {
			$('#report_schedule_frequency').prop('disabled', true);
			$('#report_autorrdlist').prop('disabled', true);

			if ($('#report_autoarchive').length) {
				$('#report_autoarchive').prop('disabled', true);
			}

			if ($('#report_email').length) {
				$('#report_email').prop('disabled', true);
			}

			if ($('#report_autoexport').length) {
				$('#report_autoexport').prop('disabled', true);
			}
		}
	}
	</script>
	<?php
}

function form_actions() {
	global $report_actions, $config;

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));

		if (get_request_var('drp_action') == '2') { // DELETE REPORT
			$report_datas = db_fetch_assoc('SELECT id
				FROM reportit_reports
				WHERE ' . array_to_sql_or($selected_items, 'id'));

			if (sizeof($report_datas) > 0) {
				$counter_data_items = 0;
				foreach ($report_datas as $report_data) {
					$counter_data_items += db_fetch_cell_prepared('SELECT COUNT(*)
						FROM reportit_data_items
						WHERE report_id = ?',
						array($report_data['id']));

					db_execute_prepared('DELETE FROM reportit_reports WHERE id = ?', array($report_data['id']));
					db_execute_prepared('DELETE FROM reportit_presets WHERE id = ?', array($report_data['id']));
					db_execute_prepared('DELETE FROM reportit_rvars WHERE report_id = ?', array($report_data['id']));
					db_execute_prepared('DELETE FROM reportit_recipients WHERE report_id = ?', array($report_data['id']));
					db_execute_prepared('DELETE FROM reportit_data_items WHERE report_id = ?', array($report_data['id']));
					db_execute('DROP TABLE IF EXISTS reportit_results_' . $report_data['id']);
				}

				if ($counter_data_items > 200) {
					db_execute('OPTIMIZE TABLE `reportit_data_items`');
				}
			}
		} elseif (get_request_var('drp_action') == '3') { //DUPLICATE REPORT CONFIGURATION
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				$report_data = db_fetch_row_prepared('SELECT *
					FROM reportit_reports
					WHERE id = ?', array($selected_items[$i]));

				$report_data['id'] = 0;
				$report_data['description'] = str_replace("<report_title>", $report_data['description'], get_request_var('report_addition'));
				$new_id = sql_save($report_data, 'reportit_reports');

				//Copy original rrdlist table  to new rrdlist table
				$data_items = db_fetch_assoc_prepared('SELECT *
					FROM reportit_data_items
					WHERE report_id = ?',
					array($selected_items[$i]));

				if (sizeof($data_items)) {
					foreach($data_items as $data_item) {
						$data_item['report_id']=$new_id;
						sql_save($data_item, 'reportit_data_items', array('id', 'report_id'), false);
					}
				}

				/* duplicate the presets settings */
				$report_presets = db_fetch_row_prepared('SELECT *
					FROM reportit_presets
					WHERE id = ?',
					array($selected_items[$i]));

				$report_presets['id'] = $new_id;
				sql_save($report_presets, 'reportit_presets', 'id', false);

				/* duplicate list of recipients */
				$report_recipients = db_fetch_assoc_prepared('SELECT *
					FROM reportit_recipients
					WHERE report_id = ?',
					array($selected_items[$i]));

				if (sizeof($report_recipients)) {
					foreach($report_recipients as $recipient) {
						$recipient['id'] = 0;
						$recipient['report_id']=$new_id;
						sql_save($recipient, 'reportit_recipients');
					}
				}

				/* reset the new report configuration */
				reset_report($new_id);
			}
		}

		header('Location: cc_reports.php');
		exit;
	}

	//Set preconditions
	$ds_list = ''; $i = 0;

	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			//Fetch report id
			$id = substr($key, 4);
			$report_ids[] = $id;
			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			//Fetch report description
			$report_description = db_fetch_cell_prepared('SELECT description
				FROM reportit_reports
				WHERE id = ?',
				array($id));

			$ds_list[] = $report_description;
		}
	}

	//For running report jump to cc_run.php!
	if (get_request_var('drp_action') == '1') { // RUNNING REPORT
		//Only one report is allowed to run at the same time, so select the first one:
		if (isset($report_ids)) {
			$report_id = $report_ids[0];

			//Update $_SESSION
			$_SESSION['run'] = '1';

			//Jump to cc_run.php
			header('Location: cc_run.php?action=calculation&id=' . $report_id);
			exit;
		}
	}

	top_header();

	html_start_box($report_actions[get_request_var('drp_action')], '60%', '', '2', 'center', '');

	form_start('cc_reports.php');

	if (get_request_var('drp_action') == '2') { //DELETE REPORT
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete the following Reports.') . '</p>';

		if (is_array($ds_list)) {
			print '<p>' . __('List of selected reports:') . '</p>';
			print '<ul>';
			foreach($ds_list as $key => $value) {
				print '<li>' . __('|_Report: %s', $value) . '</li>';
			}
			print '</ul>';
		}
		print '</td>
		</tr>';
	} elseif (get_request_var('drp_action') == '3') { // DUPLICATE REPORT
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to duplicate the following Report configurations.  You may also change the title format during this operation.') . '</p>';

		if (is_array($ds_list)) {
			print '<p>' . __('List of selected Reports:') . '</p>';
			print '<ul>';
			foreach($ds_list as $key => $value) {
				print '<li>' . __('|_Report: %s', $value) . '</li>';
			}
			print '</ul>';
		}
		print '<p>' . __('Title Format:') . '</p>';
		print '<p>' . form_text_box('report_addition', __('<report_title> (1)'), '', '255', '30', 'text');
		print '</p>
		    </td>
		</tr>';
	}


	if (!is_array($ds_list)) {
		print "<tr><td class='textArea'><span class='textError'>" . __('You must select at least one report.') . "</span></td></tr>\n";
		$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>";
	} else {
		$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue') . "'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($report_ids) ? serialize($report_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	bottom_footer();
}
