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
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function create_result_table($report_id) {
	// Create the sql syntax
	db_execute("CREATE TABLE IF NOT EXISTS plugin_reportit_results_$report_id (
		`id` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`))
		ENGINE=InnoDB");

	// Copy all actual ids from rrdlist
	db_execute_prepared("INSERT INTO plugin_reportit_results_$report_id
		SELECT `id`
		FROM plugin_reportit_data_items
		WHERE report_id = ?",
		array($report_id));
}

function get_report_definitions($report_id) {
	global $consolidation_functions;

	$report_definition = array();

	// Fetch report's definition
	$report = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_reports
		WHERE id = ?',
		array($report_id));

	// Fetch all RRD definitions
	$data_items = db_fetch_assoc_prepared('SELECT
		MAX(IF(c.field_name = "ifSpeed", c.field_value, c.field_value * 1000000)) AS `maxValue`, a.*
		FROM plugin_reportit_data_items AS a
		LEFT JOIN data_local AS b
		ON b.id = a.id
		LEFT JOIN host_snmp_cache AS c
		ON c.host_id = b.host_id
		AND c.snmp_index = b.snmp_index
		AND c.snmp_query_id = b.snmp_query_id
		AND c.field_name IN ("ifSpeed", "ifHighSpeed")
		WHERE a.report_id = ?
		GROUP BY a.id
		ORDER BY a.id',
		array($report_id));

	// Fetch all high counters
	$high_counters = db_fetch_assoc_prepared('SELECT c.field_value as maxHighValue, a.id
		FROM plugin_reportit_data_items AS a
		LEFT JOIN data_local AS b
		ON b.id=a.id
		LEFT JOIN host_snmp_cache AS c
		ON c.host_id=b.host_id
		AND c.snmp_index=b.snmp_index
		AND c.snmp_query_id=b.snmp_query_id
		AND c.field_name="ifHighSpeed"
		WHERE a.report_id = ?
		ORDER BY a.id',
		array($report_id));

	// Fetch all template informations
	$template = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_templates
		WHERE id = ?',
		array($report['template_id']));

	// Fetch all all data source items
	$sql = 'SELECT data_source_name
		FROM plugin_reportit_data_source_items
		WHERE template_id = ' . $report['template_id'] . '
		AND id != 0
		ORDER BY id';

	$ds_items = db_custom_fetch_flat_array($sql);

	foreach ($ds_items as $key => $data_source_name) {
		/**
		 * We need to process this list as the maxRRDValue can contain |query_ifSpeed|
		 * and |query_ifHighSpeed|.
		 */
		$temp_results = db_fetch_assoc_prepared('SELECT rdi.id, dtr.rrd_maximum AS maxRRDValue,
			dl.host_id, dl.snmp_query_id, dl.snmp_index
			FROM plugin_reportit_data_items AS rdi
			LEFT JOIN data_template_rrd AS dtr
			ON dtr.local_data_id = rdi.id
			LEFT JOIN data_local AS dl
			ON dl.id = dtr.local_data_id
			WHERE dtr.data_template_id = ?
			AND rdi.report_id = ?
			AND dtr.data_source_name = ?
			ORDER BY rdi.id',
			array($template['data_template_id'], $report_id, $data_source_name));

		$fin_results = array();

		if (cacti_sizeof($temp_results)) {
			foreach($temp_results as $r) {
				$pre = $r['maxRRDValue'];

				if (strpos($pre, '|') !== false) {
					$post = substitute_snmp_query_data($pre, $r['host_id'], $r['snmp_query_id'], $r['snmp_index']);
				} else {
					$post = $pre;
				}

				$fin_results[] = array('id' => $r['id'], 'maxRRDValue' => $post);
			}
		}

		$maxRRDValues[$key] = $fin_results;
	}

	// Fetch all measurands
	$measurands = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_measurands
		WHERE template_id = ?
		ORDER BY id',
		array($report['template_id']));

	// filter out all used consolidation function
	$cf = array();
	if (cacti_sizeof($measurands)) {
	    foreach ($measurands as $measurand) {
			$cf[$measurand['cf']] = $consolidation_functions[$measurand['cf']];
		}
	}

	// Fetch all variables
	$rvars = db_fetch_assoc_prepared('SELECT variable_id AS id, value
		FROM plugin_reportit_rvars
		WHERE report_id = ?',
		array($report_id));

	// Fetch the data_source_type
	$tmp = db_fetch_row_prepared('SELECT DISTINCT data_source_type_id AS ds_type, rrd_maximum AS maximum
		FROM data_template_rrd
		WHERE data_template_id = ?
		AND local_data_id = 0',
		array($template['data_template_id']));

	$template['ds_type'] = $tmp['ds_type'];
	$template['maximum'] = $tmp['maximum'];

	// Fetch the standard rrd_step
	$template['step'] = db_fetch_cell_prepared('SELECT DISTINCT rrd_step
		FROM data_template_data
		WHERE data_template_id = ?
		AND local_data_id = 0',
		array($template['data_template_id']));

	// Fetch RRA definitions
	$template['RRA'] = db_fetch_assoc('SELECT steps, timespan
		FROM data_source_profiles_rra
		WHERE data_source_profile_id=1
		ORDER BY timespan');

	// Rebuild the variables
	$variables = array();
	foreach ($rvars as $key => $value) {
		$name = 'c' . $value['id'] .'v';
		$variables[$name] = $value['value'];
	}

	//Construct the return-array 'report_definitions'
	$report_definitions['report']        = $report;
	$report_definitions['data_items']    = $data_items;
	$report_definitions['high_counters'] = $high_counters;
	$report_definitions['maxRRDValues']  = $maxRRDValues;
	$report_definitions['template']      = $template;
	$report_definitions['measurands']    = $measurands;
	$report_definitions['variables']     = $variables;
	$report_definitions['cf']            = $cf;
	$report_definitions['ds_items']      = $ds_items;

	return $report_definitions;
}

function day_to_number($day) {
	switch($day) {
	case __('Monday', 'reportit'):
	    return 1;
		break;
	case __('Tuesday', 'reportit'):
	    return 2;
		break;
	case __('Wednesday', 'reportit'):
	    return 3;
		break;
	case __('Thursday', 'reportit'):
	    return 4;
		break;
	case __('Friday', 'reportit'):
	    return 5;
		break;
	case __('Saturday', 'reportit'):
	    return 6;
		break;
	case __('Sunday', 'reportit'):
	    return 7;
		break;
	}
}

function get_type_of_request($startday, $endday, $f_sp, $l_sp, $e_hour, $shift_duration,
	$rrd_sp, $rrd_ep, $rrd_step, $rrd_ds_cnt, $dst_support) {

	/**
	 * -----------------------------------------------------------------------------------------------------------
	 * Calculate all included weekdays
	 * For a great report duration it's more efficient to use time vectors instead of 'get weekday function'

	 * Variables:
	 *    $wdays        => includes all ids of weekdays which are include
	 *    $dis          => means the distance vector for all included days
	 *    $off          => means the offset vector for all not included days
	 *    $rrd_ad_data  => Return value of this function. An array that includes all important
	 *                     information to grep the adapted data out of the rrd_data array.
	 * -----------------------------------------------------------------------------------------------------------
	 */

	// ----- Calculate all included weekdays -----
	switch ($startday) {
		case $startday == $endday:         // e.g. 'Monday till Monday => includes only Monday!'
			$wdays[] = ($startday == 7) ? 0 : $startday;
			$dis = 0;
			$off = 7;

			break;
		case $startday < $endday:         // e.g. 'Monday till Friday => includes Mo, Tu, Wed, Thu and Fr
			$dis  = $endday - $startday;
			$off  = 7 - $dis;

			for ($startday; $startday <= $endday; $startday++) {
				$wdays[] = $startday;
			}

			if ($endday == 7) {
				$wdays[] = 0;
			}

			break;
		case $startday > $endday:          // e.g. 'Friday till Monday => includes Fr, Sat, Sun, Mo'
			$dis = 7 - $startday + $endday;
			$off = 7 - $dis;

			for ($startday; $startday <= 7; $startday++) {
				$wdays[] = ($startday == 7) ? 0 : $startday;
			}

			for ($offset = 1; $offset <= $endday; $offset++) {
				$wdays[] = $offset;
			}

			break;
	}

	if ($endday == 7) {
		$endday = 0;
	}

	// boost the calculation if all weekdays are required and step or shift are covering the whole day
	if (($dis == 6 && $off == 1 && $shift_duration == 86400) || ($dis == 6 && $off == 1 && $rrd_step == 86400)) {
		$rrd_ad_data['index'][0] = abs(($rrd_ep-($rrd_sp-$rrd_step))/$rrd_step);

		return $rrd_ad_data;
	}

	// ----- Calculate number of rrd_steps for enclosing a 'normal' shift -----
	$rrd_ad_data['steps'] = abs(ceil($shift_duration/$rrd_step));
	// ------------------------------------------------------------------------

	// ----- Calculate all starting points which will be included in report duration -----
	// Using "classic" way until first endday is found.
	// Set preconditions
	$date   = getdate($f_sp);
	$index  = 0;

	for ($f_sp; $f_sp <= $l_sp; $f_sp += 86400, $date = getdate($f_sp)) {
		// Number of steps
		$steps = $rrd_ad_data['steps'];
		$tmz_change = false;

		// If the timezone changes between the current and the following day than...
		if ($dst_support) {
			$nextday = getdate($f_sp + 86400);

			if ($date['hours'] != $nextday['hours']) {
				$tmz_change = $date['hours']-$nextday['hours'];

				if ($tmz_change < -1) {
					$tmz_change += 24;
				}

				// ...check if there is a change during the shift
				$shift_ep  = $f_sp + $shift_duration;
				$shift_end = getdate($shift_ep);
				if ($shift_end['hours'] != $e_hour) {
					// ...than modify its endpoint
					$shift_ep += $tmz_change*3600;
				}
			}
		}

		// Memorize the correct index number if the current wday matches and ...
		if (in_array($date['wday'], $wdays)) {
			// ...calculate start point's index
			$index = floor(($f_sp - $rrd_sp)/$rrd_step+1);

			// ...if the tmz has been changed calculate the new number of rrd_steps
			if ($tmz_change) {
				$steps = floor(($shift_ep - $rrd_sp)/$rrd_step+1) - $index;
			}

			// ...check if the number of steps is to high (Option: "Down to present day")
			if ($rrd_ep < $f_sp + $steps*$rrd_step) {
				$steps = floor(($rrd_ep - $rrd_sp)/$rrd_step+1) - $index;
			}

			// ...save the index and the number of rrd_steps for enclosing the current shift
			$rrd_ad_data['index'][$index] = $steps;
		}

		// If the first endday is reached switch over to use time vectors instead
		if ($date['wday'] == $endday) {
			break;
		}

		// ...correct the start point if we found one change of tmz
		if ($tmz_change) {
			$f_sp += $tmz_change*3600;
		}
	}

	// -----------------------------------------------------------------------------------
	// ----- Calculate all starting points which will be included in report duration -----
	// Using time vectors until end is reached.

	// Set preconditions
	$offs = 0;
	$ldis = 0;
	$tmz_change = false;

	// Information about the last connection point
	$date = getdate($f_sp);

	while ($f_sp < $l_sp) {
		// Reset last distance vector
		$ldis = 0;

		// Offset: Set starting point to the next duration
		$f_sp += ($off * 86400);

		// Count number of offsets
		$offs++;

		// Distance: Start searching important timestamps
		for ($f_sp, $i=$dis; $f_sp <= $l_sp AND $i>=0; $f_sp+=86400, $i--) {
			$date = getdate($f_sp);

			// Number of steps
			$steps      = $rrd_ad_data['steps'];
			$tmz_change = false;

			if ($dst_support) {
				// If the timezone changes between the current and the following day than...
				$nextday = getdate($f_sp + 86400);

				if ($date['hours'] != $nextday['hours']) {
					$tmz_change = $date['hours']-$nextday['hours'];

					if ($tmz_change < -1) {
						$tmz_change += 24;
					}

					// ...check if there is a change during the shift
					$shift_ep   = $f_sp + $shift_duration;
					$shift_end  = getdate($shift_ep);

					if ($shift_end['hours'] != $e_hour) {
						// ...than modify its endpoint
						$shift_ep += $tmz_change*3600;
					}
				}
			}

			// Memorize the correct index number:
			// ...calculate start point's index
			$index = floor(($f_sp - $rrd_sp)/$rrd_step+1);

			// ...if the tmz has been changed calculate the new number of rrd_steps
			if ($tmz_change) {
				$steps = floor(($shift_ep - $rrd_sp)/$rrd_step+1) - $index;
			}

			// ...check if the number of steps is to high (Option: "Down to present day")
			if ($rrd_ep < $f_sp + $steps*$rrd_step) {
				$steps = floor(($rrd_ep - $rrd_sp)/$rrd_step+1) - $index;
			}

			// ...correct the start point if we found one change of tmz
			if ($tmz_change) {
				$f_sp += $tmz_change*3600;
			}

			// ...update $date
			$date = getdate($f_sp);

			// ...save the index and the number of rrd_steps for enclosing the current shift
			$rrd_ad_data['index'][$index] = $steps;

			// Update last distance vector
			$ldis++;

			// Break out if $l_sp has been exceeded
			if ($f_sp > $l_sp){
				if ($ldis == 0) {
					$offs--;
				}

				break 2;
			}
		}

		// For loop requires a correction of the timespamp
		$f_sp -= 86400;

		// Prevent a loop after change of tmz
		if ($l_sp - $f_sp < 86400) {
			break;
		}
	}
	// -----------------------------------------------------------------------------------

	// Check whether a valid startpoint has been found
	if (!isset($rrd_ad_data['index'])) {
		$status = false;

		return $status;
	}

	// ----- Finish -----
	return $rrd_ad_data;
}

function get_prepared_data(&$rrd_data, &$rrd_ad_data, $rrd_ds_cnt, $ds_type, $corr_factor_start, $corr_factor_end, &$ds_namv, &$rrd_nan) {
	for ($i = 0; $i<$rrd_ds_cnt; $i++) {
		if (!array_key_exists($i, $ds_namv)) {
			continue;
		}

		//Create the indexes and read the values of all startpoints
		foreach ($rrd_ad_data['index'] as $key => $steps) {
			$index = $key * $rrd_ds_cnt + $i;

			//Correct the value automatically if it's needfully (Type 'Counter' only)
			$data[$i][$index]  = $rrd_data[$index];
			$multi[$i][$index] = ($ds_type == 2 && !is_nan($rrd_data[$index])) ? $corr_factor_start : 1;

			//If value stands for one day it has to be the last one, too
			$number	= $index;

			//Create the indizes of steps which defines the following shift
			for ($k = 1; $k < $steps; $k++) {
				$number = $index + $k * $rrd_ds_cnt;

				if (isset($rrd_data[$number])) {
					$data[$i][$number]  = $rrd_data[$number];
					$multi[$i][$number] = 1;
				}
			}

			//Correct the latest shift value if needfully (Type 'Counter' only)
			if ($ds_type == 2 && !is_nan($rrd_data[$index])) {
				//are measured values for start and end the same one?
				$x = ($index == $number)? $corr_factor_start + $corr_factor_end -1 : $corr_factor_end;
				$multi[$i][$number]	= $x;
			} else {
				$multi[$i][$number] = 1;
			}
		}

		//Remove all NAN's
		foreach ($data[$i] as $key => $value) {
			if (is_nan($value) || is_null($value)) {
				unset($data[$i][$key]);
				unset($multi[$i][$key]);
				$rrd_nan++;
			}
		}
	}

	/* add $multi to return data */
	//$data['x'] = $multi;
	return $data;
}

function strtoNaN(&$value) {
	$value = str_replace(',', '.', $value);
	$value = (is_numeric($value)) ? doubleval($value) : REPORTIT_NAN;
}

function transform(&$data, &$rrd_data, &$template) {
	//Transform into the 'normal' form:
	$ds_names = substr($data, 0, strpos($data, PHP_EOL));
	$ds_names = str_replace('timestamp', '', $ds_names);
	debug($ds_names, "Data sources");

	$data = substr($data, strpos($data, PHP_EOL));

	preg_match_all('/\S+/', $ds_names, $rrd_data);
	debug($rrd_data, "Preg_match_all");

	$rrd_data['ds_namv'] = array_shift($rrd_data);
	$rrd_data['ds_cnt']  = count($rrd_data['ds_namv']);
	debug($rrd_data, "Preg_match_all - Result");

	preg_match_all('/\S+/', $data, $data);
	$zahl              = count($data[0]);
	$last_timestamp    = $zahl - $rrd_data['ds_cnt'] - 1;

	/* catch a cases with a missing data source */
	if (!isset($data[0]) || !cacti_sizeof($data[0])) {
		return false;
	}

	//cacti_log('Data Size:' . cacti_sizeof($data[0]) . ', RRD Size:' . cacti_sizeof($rrd_data));

	$rrd_data['start'] = substr($data[0][0], 0, -1);
	$rrd_data['end']   = substr($data[0][$last_timestamp], 0, -1);

	//The step is needed, so if we've only one timespan then do this:
	if ($rrd_data['start'] == $rrd_data['end']) {
		$diff = time()-$rrd_data['start'];

		$i = 0;
		foreach ($template['RRA'] as $key => $array) {
			if ($diff > $array['timespan']) {
				$i++;
			}
		}

		if ($diff > $array['timespan']) {
			$i--;
		}

		$step = $template['RRA'][$i]['steps'] * $template['step'];
	}

	$b = intval($rrd_data['ds_cnt']);
	$a = 0;

	$step_value = 0;
	if (isset($step)) {
		$step_value = $step;
	} else {
		$step_value = $data[0][$b+1];
	}

	if (!is_numeric($step_value)) {
		$step_value = intval($step_value);
	}

	$rrd_data['step']   = $step_value - $rrd_data['start'];
	$rrd_data['start'] -= $rrd_data['step'];

	//Delete all timestamps
	while($a < $zahl) {
		unset($data[0][$a]);
		$a += $b + 1;
	}

	$a -= $b + 1;

	$rrd_data['data'] = array_values($data[0]);
	array_walk($rrd_data['data'], 'strtoNaN');
}

function check_DST_support() {
	$tmz = date('T');
	$return = ($tmz == 'UTC' || $tmz == 'GMT' || $tmz == 'UCT') ? false : true;

	return $return;
}

function check_rra_header(& $rra_data){

}

function send_scheduled_email($id, $report_id) {
	$start_time = microtime(true);

	/* load report based email settings */
	$report_settings  = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_reports
		WHERE id = ?',
		array($report_id));

	$data 	 = '';
	$search  = array('|title|', '|period|');
	$replace = array($report_settings['description'], $report_settings['start_date'] . '-' . $report_settings['end_date']);
	$subject = ($report_settings['email_subject'] != '') ? $report_settings['email_subject'] : 'Scheduled report - |title| - |period|';
	$subject = str_replace($search, $replace, $subject);

	$body    = ($report_settings['email_body'] != '')   ? $report_settings['email_body'] : 'This is a scheduled report generated from Cacti.';
	$format  = ($report_settings['email_format'] != '') ? $report_settings['email_format'] : 'CSV';

	/* load list of recipients */
	$file_type = ($format != 'SML') ? strtolower($format) : 'xml';
	$mime_type = ($format != 'SML') ? 'application/' . strtolower($format) : 'application/vnd-ms-excel';

	$from   = array();
	$from[] = read_config_option('settings_from_email');
	$from[] = read_config_option('settings_from_name');

	$to = db_fetch_assoc_prepared('SELECT email, name
		FROM plugin_reportit_recipients
		WHERE report_id = ?',
		array($report_id));

	$attachment = array();
	$data = '';

	if ($format != 'None') {
		/* define additional attachment settings */
		$filebase = read_config_option('reportit_exp_filename');

		if (empty($filebase)) {
			$filebase = 'report_<report_id>';
		}

		$dirbase = dirname($filebase);
		if (empty($dirbase) || $dirbase == '.') {
			$dirbase = sys_get_temp_dir();
		}

		$filebase         = $dirbase . '/' . $filebase . ".$file_type";
		$filename         = str_replace('<report_id>', $report_id, $filebase);
		$export_function  = 'export_to_' . $format;

		print "Attachment: $filename\n";

		/* load export data and define the attachment file */
		if (function_exists($export_function)) {
			$data = get_prepared_report_data($report_id, 'export');
			if ($data == '') {
				return('Export failed');
			}

			$data = $export_function($data);

			$attachment = array(
				'attachment' => $filename,
				'mime_type'  => $mime_type,
				'inline'     => 'attachment',
			);

			file_put_contents($filename, $data);
		} else {
			cacti_log("WARNING: Missing function '$export_function'", true, 'REPORTIT');
		}
	}

	$return = mailer($from, $to, '', '', '', $subject, $body, '', array($attachment), '', true);

//	reports_log_and_notify($id, $start_time, 'html', 'reportit', $report_id, $subject, $data['data'], $body, $body_html, $body_text, $attachment, $headers);

	unlink($filename);

	return $return;
}

function reportit_report_log_and_notify($id, $start_time, $report_type, $source, $source_id,
	$subject, &$raw_data, &$oput_raw, &$oput_html, &$oput_text, $attachments = array(), $headers = false) {
	$report = db_fetch_row_prepared('SELECT *
		FROM reports_queued
		WHERE id = ?',
		array($id));

	if ($oput_text == null) {
		$oput_text = '';
	}

	$fromemail = read_config_option('settings_from_email');
	if ($fromemail == '') {
		$fromemail = 'cacti@cacti.net';
	}

	$fromname = read_config_option('settings_from_name');
	if ($fromname == '') {
		$fromname = __('Cacti %s', ucfirst($source), 'flowview');
	}

	$from[0] = $fromemail;
	$from[1] = $fromname;

	if (cacti_sizeof($report)) {
		if ($report['notification'] != '') {
			$notifications = json_decode($report['notification'], true);

			foreach($notifications as $type => $data) {
				switch($type) {
					case 'email':
						if (!isset($data['to_email'])) {
							cacti_log(sprintf("WARNING: Email Report '%s' not sent!  Missing 'to_email' attribute in request", $report['name']), false, 'REPORTS');
							break;
						} else {
							$to_email = $data['to_email'];
						}

						if (isset($data['cc_email'])) {
							$cc_email = $data['cc_email'];
						} else {
							$cc_email = '';
						}

						if (isset($data['bcc_email'])) {
							$bcc_email = $data['bcc_email'];
						} else {
							$bcc_email = '';
						}

						if (isset($data['reply_to'])) {
							$reply_to = $data['reply_to'];
						} else {
							$reply_to = '';
						}

						mailer($from, $to_email, $cc_email, $bcc_email, $reply_to, $subject, $oput_html, $oput_text, $attachments, $headers);

						break;
					case 'notification_list':
						if (!isset($data['id'])) {
							cacti_log(sprintf("WARNING: Email Report '%s' not sent!  Missing notification list 'id' attribute in request", $report['name']), false, 'REPORTS');
							break;
						} else {
							$list = db_fetch_row_prepared('SELECT *
								FROM plugin_notify_list
								WHERE id = ?',
								array($data['id']));

							if (cacti_sizeof($list)) {
								/* process the format file */
								$report_tag  = '';
								$theme       = 'modern';
								$output_html = '';
								$format      = $list['format_file'] != '' ? $list['format_file']:'default';

								$format_ok = reports_load_format_file($format, $output, $report_tag, $theme);

								flowview_debug('Format File Loaded, Format is ' . ($format_ok ? 'Ok':'Not Ok') . ', Report Tag is ' . $report_tag);

								if ($format_ok) {
									if ($report_tag) {
										$oput_html = str_replace('<REPORT>', $oput_raw, $output);
									} else {
										$oput_html = $output . PHP_EOL . $oput_raw;
									}
								} else {
									$oput_html = $oput_raw;
								}

								$to_email   = $list['emails'];
								$cc_emails  = isset($list['cc_emails']) ? $list['cc_emails']:'';
								$bcc_emails = $list['bcc_emails'];
								$reply_to   = isset($list['reply_to'])  ? $list['reply_to']:'';

								mailer($from, $to_email, $cc_emails, $bcc_emails, $reply_to, $subject, $oput_html, $oput_text, $attachments, $headers);
							} else {
								cacti_log(sprintf("WARNING: Email Report '%s' not sent!  Unable to locate notification list '%s'", $report['name'], $id), false, 'REPORTS');
							}
						}

						break;
					default:
						cacti_log(sprintf("WARNING: Email Report '%s' not sent!  Unknown notification type '%s' attribute in request", $report['name'], $type), false, 'REPORTS');
						break;
				}
			}
		}

		$end_time = microtime(true);

		$save = array();

		$save['id']                 = 0;
		$save['name']               = $report['name'];
		$save['source']             = $source;
		$save['source_id']          = $source_id;
		$save['report_output_type'] = $report_type;
		$save['report_raw_data']    = json_encode($raw_data);
		$save['report_raw_output']  = $oput_raw;
		$save['report_html_output'] = $oput_html;
		$save['report_txt_output']  = $oput_text;
		$save['send_type']          = $report['request_type'];
		$save['send_time']          = date('Y-m-d H:i:s');
		$save['run_time']           = $end_time - $start_time;
		$save['sent_by']            = $report['requested_by'];
		$save['sent_id']            = $report['requested_id'];
		$save['notification']       = $report['notification'];

		sql_save($save, 'reports_log');
	}
}

