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
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/const_runtime.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/const_measurands.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_calculate.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_runtime.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/runtime.php');

//Set default action
set_default_action();

//Redirection
if (isset($_SESSION['run']) && ($_SESSION['run'] == '0')) {
	header('Location: cc_reports.php');
	exit;
}

switch (get_request_var('action')) {
	case 'calculation':
		top_header();
		calculation();
		bottom_footer();
		break;
	default:
		break;
}

function calculation() {
	$number_of_warnings	= 0;
	$number_of_errors	= 0;
	$runtime			= '';

	locked(my_template(get_request_var('id')));

	$id = get_request_var('id');
	$_SESSION['run'] = '0';

	if (stat_process($id)) {
		html_error_box(__('Report is just in process.'), 'cc_run.php', '', 'cc_reports.php');
		exit;
	}

	/* run the report */
	$result = runtime($id);

	/* load report informations */
	$report_informations = db_fetch_row_prepared('SELECT a.description, a.last_run, a.runtime
		FROM reportit_reports AS a
		WHERE a.id = ?', array($id));

	foreach($result as $notice) {
		if (substr_count($notice, 'WARNING')) {
			$number_of_warnings++;
			continue;
		}
		if (substr_count($notice, 'ERROR')) {
			$number_of_errors++;
		}
	}

	if (!isset($result['runtime'])) {
		html_custom_header_box($report_informations['description'], __('Report calculation failed'), 'cc_rrdlist.php?&id=' . get_request_var('id'), __('List Data Items'));
		html_end_box(false);
	}else {
		$runtime = $result['runtime'];
		html_custom_header_box($report_informations['description'], __('Report statistics'), 'cc_reports.php', __('Report configurations'));
		html_end_box(false);

		form_start('cc_view.php?action=show_report&id=' . get_request_var('id'));

		html_graph_start_box();
		?>
		<tr>
			<td><b> Runtime:&nbsp; <font color='0000FF'> <?php print $runtime; ?>s</font> </b></td>
		</tr>
		<?php
		html_graph_end_box();
	}

	if ($number_of_errors > 0) {
		html_graph_start_box();

		?>
		<tr>
			<td style='vertical-align:top;'><b><?php print __('Number of errors <font class="deviceDown"> (%s)</font>', $number_of_errors);?></b></td>
			<td class='left'><b> <font color='FF0000'><ul><?php
			foreach($result as $error) {
				if (substr_count($error, 'ERROR')) print "<li>$error</li>";
			}
			?></ul></font></b></td>
		</tr>
		<?php

		html_graph_end_box();
	}

	if ($number_of_warnings > 0) {
		html_graph_start_box();

		?>
		<tr>
			<td style='vertical-align:top;'><b><?php print__('Number of warnings <font class="deviceDown"> (%s)</font>', $number_of_warnings);?></b></td>
			<td class='left'><b><ul><?php
			foreach($result as $warning) {
				if (substr_count($warning, 'WARNING')) {
					print "<li>$warning</li>";
				}
			}
			?></ul></b></td>
		</tr>
		<?php

		html_graph_end_box();
	}

	if ($number_of_errors == 0) {
		?>
		<table width='100%' class='center'>
			<tr>
				<td class='left'>
					<img type='image' src='../../images/arrow.gif'>
				</td>
				<td class='right'>
					<input type='button' value='<?php print __('View Report');?>'>
				</td>
			</tr>
		</form>
		<?php
	}
}

