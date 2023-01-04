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
include_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_runtime.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_measurands.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_calculate.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_runtime.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/runtime.php');

//Set default action
set_default_action();

//Redirection
if (isset($_SESSION['run']) && ($_SESSION['run'] == '0')) {
	header('Location: reports.php');
	exit;
}

switch (get_request_var('action')) {
	case 'calculation':
		#top_header();
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

	if (stat_process($id) > 0) {
		html_error_box(__('Report is alrady in progess.', 'reportit'), 'run.php', '', 'reports.php');
		exit;
	}

	/* run the report */
	$result = runtime($id);

	/* load report informations */
	$report_informations = db_fetch_row_prepared('SELECT a.description, a.last_run, a.runtime
		FROM plugin_reportit_reports AS a
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
		#html_custom_header_box($report_informations['description'], __('Report calculation failed'), 'rrdlist.php?&id=' . get_request_var('id'), __('List Data Items'));
		html_custom_header_box($report_informations['description'], '100%', '', '2', 'center','rrdlist.php?&id=' . get_request_var('id'), __('List Data Items', 'reportit'));
		html_end_box(false);
	} else {
		$runtime = $result['runtime'];
		#html_custom_header_box($report_informations['description'], __('Report statistics'), 'reports.php', __('Report configurations'));
		html_custom_header_box($report_informations['description'], '100%', '', '2', 'center','reports.php', __('Report configurations', 'reportit'));
		html_end_box(false);

		form_start('view.php?action=show_report&id=' . get_request_var('id'));

		#html_graph_start_box();
		html_start_box("test", '100%', '', '1', 'center', '');
		?>
		<tr>
			<td><b> Runtime:&nbsp; <font color='0000FF'> <?php print $runtime; ?>s</font> </b></td>
		</tr>
		<?php
		#html_graph_end_box();
		html_end_box();
	}

	if ($number_of_errors > 0) {
		#html_graph_start_box();
		print "<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center'>\n";

		?>
		<tr>
			<td style='vertical-align:top;'><b><?php print __('Number of errors <font class="deviceDown"> (%s)</font>', $number_of_errors, 'reportit');?></b></td>
			<td class='left'><b> <font color='FF0000'><ul><?php
			foreach($result as $error) {
				if (substr_count($error, 'ERROR')) print "<li>$error</li>";
			}
			?></ul></font></b></td>
		</tr>
		<?php
		print "</table>";
		#html_graph_end_box();
	}

	if ($number_of_warnings > 0) {
		#html_graph_start_box();
		print "<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center'>\n";

		?>
		<tr>
			<td style='vertical-align:top;'><b><?php print __('Number of warnings <font class="deviceDown"> (%s)</font>', $number_of_warnings, 'reportit');?></b></td>
			<td class='left'><b><ul><?php
			foreach($result as $warning) {
				if (substr_count($warning, 'WARNING')) {
					print "<li>$warning</li>";
				}
			}
			?></ul></b></td>
		</tr>
		<?php

		#html_graph_end_box();
		print "</table>";
	}

	if ($number_of_errors == 0) {
		?>
		<table width='100%' class='center'>
			<tr>
				<td class='left'>
					<img type='image' src='../../images/arrow.gif'>
				</td>
				<td class='right'>
					<input type='submit' value='<?php print __('View Report', 'reportit');?>'>
				</td>
			</tr>

		<?php
		form_end();
	}
}

