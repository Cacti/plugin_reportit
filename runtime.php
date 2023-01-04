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
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

//----- Define some variables -----
$PATH_RID_LOG   = "<a href='./plugins/reportit/reports.php?action=report_edit&id=<RID>'><RID></a>";
$PATH_DID_LOG   = "<a href='./plugins/reportit/rrdlist.php?action=rrdlist_edit&id=<DID>&report_id=<RID>'><DID></a>";
$PATH_RID_VIEW  = "<a href='reports.php?action=report_edit&id=<RID>'><RID></a>";
$PATH_DID_VIEW  = "<a href='rrdlist.php?action=rrdlist_edit&id=<DID>&report_id=<RID>'><DID></a>";

$run_return     = array();
$run_freq       = '';
$run_id         = false;
$run_verb       = false;
$run_scheduled  = false;
$run_search     = array('<NOTICE>','<RID>','<DID>');
$socket_handle  = '';
$email_counter  = 0;
$export_counter = 0;

//----- Running on CLI? -----
if (isset($_SERVER['argv']['0']) && realpath($_SERVER['argv']['0']) == __FILE__) {

	$path = dirname(__FILE__);
	chdir($path);
	chdir('../../');

	$no_http_headers = true;
	require_once('include/global.php');
	include_once($config['base_path'] . '/lib/rrd.php');
	@define('REPORTIT_BASE_PATH', $path);
	@define('CACTI_BASE_PATH', __DIR__);
	include_once(REPORTIT_BASE_PATH . '/setup.php');
	include_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
	include_once(REPORTIT_BASE_PATH . '/lib/const_runtime.php');
	include_once(REPORTIT_BASE_PATH . '/lib/const_measurands.php');
	include_once(REPORTIT_BASE_PATH . '/lib/funct_calculate.php');
	include_once(REPORTIT_BASE_PATH . '/lib/funct_runtime.php');
	include_once(REPORTIT_BASE_PATH . '/lib/funct_validate.php');
	include_once(REPORTIT_BASE_PATH . '/lib/funct_export.php');

	$run_scheduled    = true;

	if (($_SERVER['argc'] > '4' || $_SERVER['argc'] < '2')) help();

	foreach($_SERVER['argv'] as $option) {
		if (is_numeric($option)) {
			$run_id = $option;
			continue;
		}

		switch($option) {
			case "-d":
				$run_freq = 'daily';
				break;
			case "-w":
				$run_freq = 'weekly';
				break;
			case "-m":
				$run_freq = 'monthly';
				break;
			case "-q":
				$run_freq = 'quarterly';
				break;
			case "-y":
				$run_freq = 'yearly';
				break;
			case "-v":
				$run_verb = true;
				break;
			case "--debug":
				@define('REPORTIT_DEBUG',1);
				break;

		}
	}

	if (($run_freq == '' & $run_id === false) || ($run_freq != '' & $run_id !== false)) help();
	if ($run_id) run($run_id);
	else run($run_freq);
} else {
	if (!defined('REPORTIT_BASE_PATH')) {
		include_once(__DIR__ . '/setup.php');
		reportit_define_constants();
	}

	include_once(CACTI_BASE_PATH . '/lib/rrd.php');
}

function help() {
	$info = plugin_reportit_version();

	print "\n---------------------------------------------------------------------------------------------------\n";
	print " Copyright (C) 2004-2023 The Cacti Group\n";
	print " Project:         ReportIt\n";
	print " Project site:    {$info['homepage']}\n";
	print " Version:         v{$info['version']}\n";
	print " Authors:         {$info['author']}\n";
	print "---------------------------------------------------------------------------------------------------\n\n";
	print " Usage: runtime.php [OPTIONS] <Report Config ID>\n";
	print "  e.g.: runtime.php 12                            run report 12 only\n";
	print "      : runtime.php -d -v                         run all daily reports + CLI feedback\n";
	print "      : runtime.php --debug 12                    debug report 12\n";
	print "      : runtime.php --debug -v 12 > log.txt       redirect debugging output\n";
	print "      : runtime.php --debug -d                    debug all daily reports (NOT RECOMMENDED)\n\n\n";
	print "     -d:          daily\n";
	print "     -w:          weekly\n";
	print "     -m:          monthly\n";
	print "     -q:          quarterly\n";
	print "     -y:          yearly\n\n";
	print "     -v:          verbose\n";
	print "     --debug:     DEBUG MODE\n\n";
	print "---------------------------------------------------------------------------------------------------\n\n";
	exit;
}

function run($frequency) {
	global $run_verb, $email_counter, $export_counter;

	$start = microtime();
	if (is_numeric($frequency)) {
		$sql = "SELECT a.id, a.template_id FROM plugin_reportit_reports as a
				INNER JOIN plugin_reportit_templates as b
				ON b.locked = '' and a.template_id = b.id
				WHERE a.id = $frequency";
	} else {
		$sql = "SELECT a.id, a.template_id FROM plugin_reportit_reports as a
				INNER JOIN plugin_reportit_templates as b
				ON b.locked = '' AND a.template_id = b.id
				WHERE a.scheduled = 'on' AND a.frequency = '$frequency'";
	}

	$reports = db_fetch_assoc($sql);
	$number = count($reports);
	if (is_numeric($frequency) & $number == 0) {
		print "\n\n ERROR: Invalid report ID !\n";
		help();
	}

	if ($number > 0) {
		foreach($reports as $report) {
			if (!get_template_status($report['template_id'])) {
				$report_id = $report['id'];
				runtime($report_id);
			} else {
				$report_id = $report['id'];
				run_error(10, $report_id);
				continue;
			}
		}
	}

	$end = microtime();
	$time = get_runtime($start, $end);

	if (read_config_option('log_verbosity', true)>POLLER_VERBOSITY_NONE) {
		if (!is_numeric($frequency)) {
			cacti_log( "REPORTIT STATS: Frequency:$frequency Time:$time Reports:$number  Emails:$email_counter  Exports:$export_counter", $run_verb, 'PLUGIN');
		} else {
			cacti_log( "REPORTIT STATS: ID:$report_id Time:$time Reports:$number  Emails:$email_counter  Exports:$export_counter", $run_verb, 'PLUGIN');
		}
	}
	exit;
}

function run_error($code, $RID = 0, $DID = 0, $notice='') {
	global $run_verb, $run_scheduled, $run_return, $run_search, $runtime_messages,
	$PATH_RID_LOG, $PATH_DID_LOG, $PATH_RID_VIEW, $PATH_DID_VIEW;

	$run_output     = '';
	$run_logging    = '';

	$run_repl_log   = array( $notice, $PATH_RID_LOG,  $PATH_DID_LOG);
	$run_repl_view  = array( $notice, $PATH_RID_VIEW, $PATH_DID_VIEW);
	$run_repl_fin   = array( $notice, $RID, $DID);

	$run_logging = str_replace($run_search, $run_repl_fin, $runtime_messages[$code]);

	$log_level = POLLER_VERBOSITY_NONE;
	if (strpos($run_logging, 'ERROR:') !== false) {
		$log_level = POLLER_VERBOSITY_LOW;
	} elseif (strpos($run_logging, 'WARNING:') !== false) {
		$log_level = POLLER_VERBOSITY_MEDIUM;
	} elseif (strpos($run_logging, 'NOTICE:') !== false) {
		$log_level = POLLER_VERBOSITY_MEDIUM;
	}

	cacti_log($run_logging, $run_verb, 'PLUGIN', $log_level);

	if (!$run_scheduled) {
		$run_output      = str_replace($run_search, $run_repl_view, $runtime_messages[$code]);
		$run_output      = str_replace($run_search, $run_repl_fin, $run_output);
		$run_return[]    = $run_output;
	}
}

function runtime($report_id) {
	global	$timezones, $run_scheduled, $run_return, $consolidation_functions,
	$rrdtool_api, $socket_handle, $calc_fct_names, $calc_fct_names_params, $calc_fct_aliases, $error, $email_counter, $export_counter;

	//This report is in_process, so flag it!
	in_process($report_id);

	if (!$run_scheduled) {
		ini_set("max_execution_time", read_config_option('reportit_met'));
	}

	//----- Define variables for Datasource and RRA descriptions -----
	$ds_description 	= '';
	$result_description = '';

	//----- Define variable for monitoring a valid process -----
	$valid_report = false;

	//----- Make a note of our startpoint -----
	$runtime_sp = microtime();

	//----- Reset report -----
	reset_report($report_id);

	//----- load default settings -----
	$report_settings = db_fetch_row("SELECT * FROM plugin_reportit_reports WHERE id = '$report_id'");

	//----- auto clean-up RRDlist -----
	autocleanup($report_id);

	//----- check if BOOST is active -----
	$boost_enabled = (function_exists("boost_process_poller_output") && db_fetch_cell("SELECT 1 FROM `settings` WHERE name = 'boost_rrd_update_enable' and value = 'on'"))? true : false;
	$debug_value = ($boost_enabled ? "enabled" : "disabled");
	debug($debug_value, "Boost Plugin Status");
	$boost_server_enabled = (db_fetch_cell("SELECT 1 FROM `settings` WHERE name = 'boost_server_enable' and value = 'on'"))? true : false;
	$debug_value = ($boost_server_enabled ? "enabled" : "disabled");
	debug($debug_value, "Boost Server Status");

	//----- automatic RRDList Generation -----
	if ($report_settings['autorrdlist']) autorrdlist($report_id);

	//----- Fetch all necessary data for building a report -----
	$report_definitions = &get_report_definitions($report_id);
	debug($report_definitions, "Definitions");

	//----- Define variable for dynamic time frame -----
	$dynamic 		= $report_definitions['report']['sliding'];
	$enable_tmz		= read_config_option('reportit_use_tmz');
	$dst_support	= check_DST_support();

	//----- Update start and enddate by using presets -----
	if ($dynamic) {
		$dates 	= rp_get_timespan( $report_definitions['report']['preset_timespan'],
								$report_definitions['report']['present'],
								$enable_tmz);
		$report_definitions['report']['start_date']	= $dates['start_date'];
		$report_definitions['report']['end_date'] 	= $dates['end_date'];
	}

	//----- Get number of RRD datasources -----
	$number_of_rrds = count($report_definitions['data_items']);


	//----- ERROR CHECK (2) -----
	//Check number of defined RRDs
	if (!$number_of_rrds > 0) {
		run_error(2,$report_id);
		in_process($report_id, 0);
		return $run_return;
	}
	//---------------------------

	//----- Prepare result table -----
	// First destroy old result table if exists
	db_execute("DROP TABLE IF EXISTS plugin_reportit_results_$report_id");

	// Create new table for saving our results
	create_result_table($report_id);

	// Set variable to control creation of new columns
	$create_result_table_columns = false;

	// We need a key identifier to create columns with a unique name
	// and an array with the used cf indexes.
	$keys          = array();
	$rra_indexes   = array();
	foreach($report_definitions['measurands'] as $measurand) {
		$keys[$measurand['abbreviation']]         = $measurand['id'];
		$rra_indexes[$measurand['abbreviation']]  = $measurand['cf'];
	}

	//----- Report Settings -----
	$s_date	= $report_definitions['report']['start_date'];
	$e_date	= $report_definitions['report']['end_date'];

	//----- Template Settings -----
	$rra_types  = array_flip($report_definitions['cf']);
	$ds_type	= $report_definitions['template']['ds_type'];
	$ds_items   = $report_definitions['ds_items'];
	$maximum	= $report_definitions['template']['maximum'];

	//----- Variables -----
	$variables 	= $report_definitions['variables'];

	/************************************************************************************/
	//Define 5 caches to save the result of our system functions during the calculation.

	$cache		= array();
	$df_cache	= array();	//Functions -> Multi-dimensional
	$dm_cache	= array();	//Measurands
	$dr_cache 	= array();	//Interim results
	$dp_cache	= array();	//Functions with parameters ->Multi-dimensional
	$ds_cache	= array();	//Measurands with flag "spanned"

	foreach($rra_types as $rra_type) {
		foreach($calc_fct_names as $value) $df_cache[$rra_type][$value] = false;
		foreach($calc_fct_names_params as $value) $dp_cache[$rra_type][$value] = false;
		foreach($calc_fct_aliases as $value) $dp_cache[$rra_type][$value] = false;
	}
	debug($df_cache, "Defined Cache -> Functions");
	debug($dp_cache, "Defined Cache -> Functions with Parameters");

	foreach($report_definitions['measurands'] as $array) {
		$dm_cache[$array['abbreviation']] = $array['calc_formula'];
	}
	debug($dm_cache, "Defined Cache -> Measurands");

	$cache	= get_possible_rra_names($report_definitions['report']['template_id']);
	foreach($cache as $rra) {
		foreach($dm_cache as $key => $value) {
			$name = $key . ':' . $rra;
			$dr_cache[$name] = false;
		}
	}
	debug($cache, "Data sources - Definition by Cacti");
	debug($dr_cache, "Defined Cache -> Interim results");

	$cache	= $report_definitions['measurands'];
	foreach($cache as $mm) {
		if ($mm['spanned'] == 'on') $ds_cache[$mm['abbreviation']] = false;
	}
	debug($ds_cache, "Defined Cache -> Measurands (spanned)");
	/************************************************************************************/

	//----- Start analysing each RRD -----
	for($i = 0; $i < $number_of_rrds; $i++) {

		//----- Interface Settings -----
		$local_data_id	= $report_definitions['data_items'][$i]['id'];

		//----- Get the local path of the RRD-file that belongs to the local_data_id -----
		$data_source_path 	= get_data_source_path($local_data_id, true);
		if ($data_source_path == ''){
			run_error(5, $report_id, $local_data_id, 'Not existing RRD file.');
			continue;
		}

		$maxValue 		= $report_definitions['data_items'][$i]['maxValue'];
		$maxHighValue	= $report_definitions['high_counters'][$i]['maxHighValue'];


		$s_time   		= $report_definitions['data_items'][$i]['start_time'];
		$e_time	 		= $report_definitions['data_items'][$i]['end_time'];
		$timezone  		= $report_definitions['data_items'][$i]['timezone'];

		//----- Convert weekdays -----
		$shift_startday		= day_to_number($report_definitions['data_items'][$i]['start_day']);
		$shift_endday		= day_to_number($report_definitions['data_items'][$i]['end_day']);

		//----- Participate reporting times -----
		list($s_hour, $s_min)	= explode(":",$s_time);
		list($e_hour, $e_min)	= explode(":",$e_time);
		if ($enable_tmz) {
			if (!isset($timezones[$timezone])) {
				run_error(11, $report_id, $local_data_id, $timezone);
				continue;
			}
			$offset_hour	= $timezones[$timezone]['hour'];
			$offset_min		= $timezones[$timezone]['min'];
		}
		//----- Participate reporting start- and enddate -----
		list($s_year, $s_month, $s_day) = explode("-",$s_date);
		list($e_year, $e_month, $e_day) = explode("-",$e_date);


		//----- Calculate correct timestamps -----
		$f_sp   = ($enable_tmz) ?  gmmktime($s_hour-$offset_hour,$s_min-$offset_min,0,$s_month,$s_day,$s_year) : mktime($s_hour,$s_min,0,$s_month,$s_day,$s_year);
		$l_sp	= ($enable_tmz) ?  gmmktime($s_hour-$offset_hour,$s_min-$offset_min,0,$e_month,$e_day,$e_year) : mktime($s_hour,$s_min,0,$e_month,$e_day,$e_year);

		//----- Check start and endtime -----
		If($s_time > $e_time) {
			// Endtime is a part of next day
			$f_ep 	= ($enable_tmz) ? gmmktime($e_hour-$offset_hour,$e_min-$offset_min,0,$s_month,$s_day+1,$s_year) : mktime($e_hour,$e_min,0,$s_month,$s_day+1,$s_year);
			$l_ep	= ($enable_tmz) ? gmmktime($e_hour-$offset_hour,$e_min-$offset_min,0,$e_month,$e_day+1,$e_year) : mktime($e_hour,$e_min,0,$e_month,$e_day+1,$e_year);
		} else {

			// Endtime is a part of same day
			$f_ep 	= ($enable_tmz) ? gmmktime($e_hour-$offset_hour,$e_min-$offset_min,0,$s_month,$s_day,$s_year) : mktime($e_hour,$e_min,0,$s_month,$s_day,$s_year);
			$l_ep	= ($enable_tmz) ? gmmktime($e_hour-$offset_hour,$e_min-$offset_min,0,$e_month,$e_day,$e_year) : mktime($e_hour,$e_min,0,$e_month,$e_day,$e_year);
		}

		//----- ERROR CHECK (3) -----
		// Check whether start- and endpoint are part of future timestamps (Important for timespan "today")
		if ($f_sp > time()) {
			run_error(3, $report_id, $local_data_id);
			continue;
		}
		if ($f_ep > time()) {
			$f_ep	= time();
			run_error(6, $report_id, $local_data_id);
		}
		If($l_ep > time()) {
			$l_ep	= time();
			if (!$dynamic) run_error(6, $report_id, $local_data_id);
		}
		//---------------------------

		//----- Calculate shift duration -----
		$shift_duration = ($enable_tmz)
						? $f_ep - $f_sp
						: (($s_time > $e_time)  ? gmmktime($e_hour, $e_min, 0, 0, 1) - gmmktime($s_hour, $s_min, 0, 0, 0)
												: gmmktime($e_hour, $e_min, 0) - gmmktime($s_hour, $s_min, 0));

		//----- run on demand update if Boost is enabled and cached data is part of the report period -----
		if ($boost_enabled) {
			$boost_last_run_time = db_fetch_cell("SELECT UNIX_TIMESTAMP(value) FROM settings WHERE name = 'boost_last_run_time'");
			if ($l_ep > $boost_last_run_time) {
				$output = boost_process_poller_output($boost_server_enabled, $local_data_id);
				debug($output, "Boost on demand update for local_data_id $local_data_id");
			}
		}

		//----- Set options for rrd_fetch and run it! -----
		$rrd_data 		= array();
		$valid_rra_indexes	= array();

		foreach($rra_types as $rra_type => $rra_index) {
			$cmd_line = "fetch $data_source_path $rra_type -s $f_sp -e $l_ep";
			debug($cmd_line, "RRDfetch command");
			$rrd_data[$rra_index] = @rrdtool_execute($cmd_line, false, RRDTOOL_OUTPUT_STDOUT);
			if (strlen($rrd_data[$rra_index]) == 0){
				$cf = array_search($rra_index, $rra_types);
				run_error(5, $report_id, $local_data_id, "Can not open rrdfile or CF '$cf' does not match.");
			} else {
				$valid_rra_indexes[] = $rra_index;
			}
			debug($rrd_data[$rra_index], "RRDtool Cacti -> RRDfetch - Raw data");
		}

		// ----- Break up if we were not able to fetch any data -----
		if (cacti_sizeof($valid_rra_indexes) == 0) {
			run_error(5, $report_id, $local_data_id, "Can not open rrdfile or CFs do not match.");
			continue;
		} else {
			/* transform data that has not been fetch via the PHP based RRDtool API */
			foreach($rrd_data as $rra_index => $data) {
					if (in_array($rra_index, $valid_rra_indexes)) {
						transform( $data, $rrd_data[$rra_index], $report_definitions['template']);
						debug($rrd_data[$rra_index], "Transformed RAW data");
					}
				}
		}

		//----- Read header informations from rrd_data array -----
		$index          = $valid_rra_indexes[0];
		$rrd_f_mp       = $rrd_data[$index]['start'] + $rrd_data[$index]['step'];    //rrd_f_mp = first measured value
		$rrd_ep         = $rrd_data[$index]['end'];
		$rrd_p_mp       = $rrd_data[$index]['end'] - $rrd_data[$index]['step'];      //rrd_p_mp = penultimate measured value
		$rrd_step       = $rrd_data[$index]['step'];
		$rrd_ds_cnt     = $rrd_data[$index]['ds_cnt'];
		$rrd_ds_namv    = $rrd_data[$index]['ds_namv'];
		$rrd_nan        = 0;

		//----- Generate all required informations for calculating -----
		$rrd_ad_data     = &get_type_of_request($shift_startday, $shift_endday, $f_sp, $l_sp, $e_hour,
												$shift_duration, $rrd_f_mp, $rrd_ep, $rrd_step,
												$rrd_ds_cnt, $dst_support);
		debug($rrd_ad_data, "Determined mask for filtering");

		//----- ERROR CHECK (5) -----
		// Check if startpoints are available
		if ($rrd_ad_data == false) {
			run_error(7, $report_id, $local_data_id);
			continue;
		}
		//---------------------------

		//----- Calculate correction factor for data source type: 'COUNTER' -----
		$corr_factor_start= 1;
		$corr_factor_end  = 1;
		if ($ds_type == 2) {
			$corr_factor_start     = ($rrd_f_mp - $f_sp)/$rrd_step;
			$corr_factor_end       = ($l_ep - $rrd_p_mp)/$rrd_step;
		}

		/* intersect all used data source items get the correct index keys */
		$rrd_ds_namv = array_intersect ( $rrd_ds_namv , $ds_items);

		//----- Prepare data for normal calculating -----
		foreach($rrd_data as $rra_index => $data){
			$pre_data[$rra_index] = get_prepared_data($rrd_data[$rra_index]['data'], $rrd_ad_data,
														$rrd_ds_cnt, $ds_type, $corr_factor_start,
														$corr_factor_end, $rrd_ds_namv, $rrd_nan);
			unset($rrd_data[$rra_index]);
			debug($rrd_ad_data, "Filtered list (includes corr factor)");
		}

		debug($pre_data, "Data for calculation");

		/* update the data source counter */
		$rrd_ds_cnt = count($rrd_ds_namv);

		//----- Update variables and create calculating parameters -----
		if ($maxValue !== NULL && $maxValue != 0) {
			if ($maxValue > 0 & $maxValue < 4294967295) {
				foreach ($report_definitions['ds_items'] as $key => $ds_name) {
					$variables['maxValue:' . $ds_name] = $maxValue;
				}
			} elseif ($maxValue == 4294967295 & $maxHighValue !== Null) {
				foreach ($report_definitions['ds_items'] as $key => $ds_name) {
					$variables['maxValue:' . $ds_name] = $maxHighValue*1000000;
				}
			} elseif ($maxValue == 4294967295 & $maxHighValue === Null) {

				/* This is a 10G interface (or higher), but ifHighSpeed counter is not available.
				Individual configured maximum per data source item will be preferred if it is higher than the maximum of the 32 Bit counter
				If that it not the case, then assume we have a maximum of 10G */

				foreach ($report_definitions['maxRRDValues'] as $key => $array) {
					$variables['maxValue:' . $report_definitions['ds_items'][$key]] = ($array[$i]['maxRRDValue'] > 4294967295)
						? $array[$i]['maxRRDValue']
						: 10000000000;
				}
			}

		} else {
			foreach ($report_definitions['maxRRDValues'] as $key => $array) {
				$variables['maxValue:' . $report_definitions['ds_items'][$key]] = $array[$i]['maxRRDValue'];
			}
		}


		foreach ($report_definitions['maxRRDValues'] as $key => $array) {
			$variables['maxRRDValue:' . $report_definitions['ds_items'][$key]] = $array[$i]['maxRRDValue'];
		}

		$variables['step']      = $rrd_step;
		$variables['nan']       = $rrd_nan;

		// add data query variables as new $variables[]
		$data_query_variables = get_possible_data_query_variables($report_definitions['report']['template_id']);  # better put this into get_report_definitions???
		if (cacti_sizeof($data_query_variables)){
			// get all data for given local data id first
			$sql = "SELECT `data_local`.* " .
					"FROM `data_local` " .
					"WHERE `data_local`.`id`=$local_data_id";
			$data_local = db_fetch_row($sql);
			foreach($data_query_variables as $dq_variable) {
				if (isset($data_local['id'])) {
					// now fetch the cached data for given query variable
					$sql = "SELECT `host_snmp_cache`.`field_value` " .
							"FROM `host_snmp_cache` " .
							"WHERE `host_id`= ?" .
							" AND `snmp_query_id`=?" .
							" AND `field_name`=? " .
							" AND `snmp_index`=?" .
							" AND `present` > 0";
					// and update the value for the given data query cache variable
					$dq_variable_value = db_fetch_cell_prepared($sql,array($data_local['host_id'],$data_local['snmp_query_id'],$dq_variable,$data_local['snmp_index']));
					$variables[$dq_variable] = ($dq_variable_value === false) ? REPORTIT_NAN : $dq_variable_value;
				} else {
					$variables[$dq_variable] = REPORTIT_NAN;
				}
			}
		}

		$params['rrd_ds_cnt']   = $rrd_ds_cnt;
		$params['rras']         = $rrd_ds_namv;
		$params['rra_indexes']  = $rra_indexes;
		debug($variables, "Variables");

		/***************** Start calculation *****************/
		$results  = calculate( $pre_data, $params, $variables, $df_cache, $dm_cache, $dr_cache, $dp_cache, $ds_cache);
		debug($results, "Calculation results for saving");
		/***************** Start calculation *****************/

		//----- Create new columns for table 'rrd_results_$report_id' -----
		if ($create_result_table_columns == false) {
			// Create the sql string
 			$list = '';

			foreach($results as $key => $value) {
			    if ($key != '_spanned_') {
				foreach($value as $mea_key => $value) {
				    $list .= " ADD `{$key}__$keys[$mea_key]` DOUBLE,";
				}
			    } else {
				foreach($value as $mea_key => $value) {
				    $list .= " ADD `spanned__$keys[$mea_key]` DOUBLE,";
				}
			    }
			}

			// Remove last comma and complete the sql string
			$list = substr($list, 0, strlen($list)-1);
			$list = "ALTER TABLE plugin_reportit_results_$report_id $list";

			// Add columms
			db_execute($list);

			// Update output variable
			$create_result_table_columns = true;


			// Update variable 'Datasource description'
			foreach($rrd_ds_namv as $value) {
				$ds_description = $ds_description . "$value|";
			}
			// Remove last '|'
			$ds_description = substr($ds_description, 0, strlen($ds_description)-1);


			// Update variable 'Result Definition'
			$first_element = reset($results);
			foreach($first_element as $key => $value) {
				$result_description = $result_description . "$keys[$key]|";
			}
			// Remove last '|' and add the number of id
			$rs_def = substr($result_description, 0, strlen($result_description)-1) . '-' . count($first_element);


			// Update variable 'Spanned Definition'
			$spanned_description = '';
			foreach($results['_spanned_'] as $key => $value) {
				$spanned_description = $spanned_description . "$keys[$key]|";
			}
			// Remove last '|' and add the number of id
			$sp_def = substr($spanned_description, 0, strlen($spanned_description)-1) . '-' . count($results['_spanned_']);


			// Set report's state valid
			$valid_report = true;
		}

		//----- Save results into the MySQL database -----
		// Create the sql string
 		$list = '';

		foreach($results as $key => $value) {
		    if ($key != '_spanned_') {
			foreach($value as $mea_key => $value) {
			    $list .= " `{$key}__$keys[$mea_key]` = $value,";
			}
		    } else {
			foreach($value as $mea_key => $value) {
			    $list .= " `spanned__$keys[$mea_key]` = $value,";
			}
		    }
		}

		// Remove last comma
		$list = substr($list, 0, strlen($list)-1);
		// Save values
		db_execute("REPLACE plugin_reportit_results_$report_id SET id = $local_data_id, $list");
	}

	//----- Close socket connection if its open -----
	if ($socket_handle != '' && !$run_scheduled) disc_rrdtool_server();

	//----- Make a note of our endpoint -----
	$runtime_ep = microtime();

	//----- Calculate runtime -----
	$runtime = get_runtime($runtime_sp, $runtime_ep);


	//----- ERROR CHECK (7) -----
	if ($valid_report != true){
		run_error(4, $report_id);
		in_process($report_id, 0);
		return $run_return;
	}
	//---------------------------


	//----- Save/update report data -----
	$now = date("Y-m-d H:i:s");

	$sql = "UPDATE plugin_reportit_reports
		SET last_run	= '$now',
		runtime 		= '$runtime',
		start_date 		= '$s_date',
		end_date 		= '$e_date',
		ds_description 	= '$ds_description',
		rs_def 			= '$rs_def',
		sp_def			= '$sp_def'
		WHERE id 		= '$report_id'";
	db_execute($sql);

	//----- Archive / Email -----
	if ($run_scheduled) {
		/* update the XML Archive */
		if (read_config_option('reportit_archive') == 'on') {
			update_xml_archive($report_id);
		}

		/* export report to custom format */
		if (read_config_option('reportit_auto_export') == 'on'
			&& $report_definitions['report']['autoexport'] != 'None'
			&& $report_definitions['report']['autoexport'] != '') {
			$export = autoexport($report_id);
			if ($export) $export_counter++;
		}

		/* create and send out an email */
		if (read_config_option('reportit_email') == 'on') {
			if ($report_definitions['report']['auto_email'] == 'on') {
				$error = send_scheduled_email($report_id);
				if ($error) {
					run_error(13, $report_id, 0, "EMAIL: $error");
				} else {
					$email_counter++;
				}
			}
		}
	}

	//----- Return messages and runtime-----
	$run_return['runtime'] = $runtime;
	in_process($report_id, 0);
	return $run_return;
}



/**
 * function autorrdlist
 * deletes all rrdlist entries that are no longer existing
 * adds all items defined by Host Template Filter and Data Source Filter
 *
 * @param unknown_type $reportid
 */
function autorrdlist($reportid) {
	global $timezone, $shifttime, $shifttime2, $weekday;

	// fetch data for current report
	$report_data		= db_fetch_row('SELECT * FROM plugin_reportit_reports WHERE id=' . $reportid);
	$header_label 		= $report_data['description']  . ' ID: ' . $reportid;

	// if Host Template Id filter was set, show the Host Template Description in the header
	if ($report_data['host_template_id'] != 0) {
		$ht_desc = db_fetch_cell('SELECT name FROM host_template WHERE id=' . $report_data['host_template_id']);
		$header_label = $header_label . ", using Host Template Filter: " . $ht_desc;
	}
	if (read_config_option("log_verbosity", true) == POLLER_VERBOSITY_DEBUG) {
		cacti_log('Running AutoRRDList for Report: ' . $header_label, false, 'REPORTIT');
	}

	// how many rows are already there?
	$current_rows = db_fetch_cell("SELECT COUNT(*) FROM plugin_reportit_data_items WHERE report_id = $reportid");

	//Get the filter setting by template
	$sql = "SELECT
		b.pre_filter, b.data_template_id
	    FROM
		plugin_reportit_reports AS a
	    JOIN
	    	plugin_reportit_templates AS b
	    ON
	    	a.template_id = b.id
	    WHERE
	     	a.id={$reportid}";
	$template_filter = db_fetch_assoc($sql);

	//Get all RRDs which are not in RRD table and match with filter settings
	$sql = "SELECT
		a.local_data_id AS id,
		a.name_cache
	    FROM
	    	data_template_data AS a
	    LEFT JOIN
	       (SELECT * FROM plugin_reportit_data_items WHERE report_id = $reportid) as b
	    ON
	    	a.local_data_id = b.id";

	// apply Host Template Id filter, if any
	if ($report_data['host_template_id'] != 0) {
		$sql .= "
		LEFT JOIN data_local 	as c ON c.id = a.local_data_id
		LEFT JOIN host 			as d ON d.id = c.host_id
		LEFT JOIN host_template as e ON e.id = d.host_template_id";
	}

	$sql .= "
		WHERE
		b.id IS NULL
	    AND
		a.local_data_id != '0'
	    AND
		a.data_template_id = {$template_filter['0']['data_template_id']}";

	if ($template_filter['0']['pre_filter'] != '') {
		$sql = $sql . " AND a.name_cache LIKE '{$template_filter['0']['pre_filter']}'";
	}

	if (isset_request_var('host_filter') && get_request_var('host_filter') != 'Any') {
		$sql = $sql . " AND a.name_cache LIKE '%" . get_request_var('host_filter') . "%'";
	}
	if (isset_request_var('text_filter') && get_request_var('text_filter') != '') {
		$sql = $sql . " AND a.name_cache LIKE '%" . get_request_var('text_filter') . "%'";
	}

	// if Host Template Id filter is applied, check for the specific Host Template Id
	// defined for this very report
	if ($report_data['host_template_id'] != 0) {
		$sql .= "	AND e.id = " . $report_data['host_template_id'];
	}
	// if Data Source Filter per Report is set, check it
	if ($report_data['data_source_filter'] != '') {
		$sql = $sql . " AND a.name_cache LIKE '{$report_data['data_source_filter']}'";
	}

	$sql .= " ORDER BY a.name_cache";

	$rrdlist = db_fetch_assoc($sql);

	// how many inserts required?
	$number_of_matches = count($rrdlist);

	if ($number_of_matches == 0) {
		if (read_config_option("log_verbosity", true) == POLLER_VERBOSITY_DEBUG) {
			cacti_log('Current Rows: ' . $current_rows . ' New Rows: ' . $number_of_matches . '. No Change required for Report ' . $reportid, false, 'REPORTIT');
		}
	} else {
		// security check: do not change rrdlist by more than settings['reportit_maxrrdchg'] items a time
		$maxrrdchg = read_config_option('reportit_maxrrdchg');
		if ($number_of_matches > $maxrrdchg) {
			array_splice($rrdlist, $maxrrdchg);
			/* reduce the number of data items to defined limitation */
			if (read_config_option("log_verbosity", true) == POLLER_VERBOSITY_DEBUG) {
				cacti_log('Current Rows: ' . $current_rows . ' New Rows: ' . $number_of_matches . ' Max. Change: ' . $maxrrdchg . ' mismatch. Auto-Generate RRD List Processing limited for Report ' . $reportid, false, 'REPORTIT');
			}
		}

		$enable_tmz	= read_config_option('reportit_use_tmz');
		$tmz		= ($enable_tmz) ? "'GMT'" : "'".date('T')."'";
		$columns	= '';
		$values		= '';
		$rrd 		= '';

		/* load data item presets */
		$sql = "SELECT * FROM plugin_reportit_presets WHERE id = $reportid";
		$presets = db_fetch_row($sql);

		if (cacti_sizeof($presets)>0) {
			$presets['report_id'] = $reportid;
			foreach($presets as $key => $value) {
				$columns .= ', ' .$key;
				if ($key != 'id') $values .= (",\"" . $value . "\"");
			}
		} else {
			$columns = ' id, report_id';
			$values .= ", \"$reportid\"";
		}

		foreach($rrdlist as $rd) {
			$rrd .= "({$rd['id']} $values),";
			if (read_config_option("log_verbosity", true) == POLLER_VERBOSITY_DEBUG) {
				cacti_log('Adding Id: ' . $rd['id'] . ' to Report ' . $reportid, false, 'REPORTIT');
			}
		}

		$rrd = substr($rrd, 0, strlen($rrd)-1);
		$columns = substr($columns, 1);

		/* save */
		$sql = "INSERT INTO plugin_reportit_data_items ($columns) VALUES $rrd";
		db_execute($sql);

		// Reset report
		reset_report($reportid);
	}
}



/**
 * autocleanup()
 * removes automatically all data items which do not exist any longer
 * @param int $report_id    contains the report identifier
 * @return
 */
function autocleanup($report_id){

	$sql = "SELECT a.id FROM plugin_reportit_data_items AS a
			LEFT JOIN data_template_data AS b
			ON b.local_data_id = a.id
			WHERE a.report_id = $report_id
			AND b.name_cache IS NULL";

	$data_items = db_custom_fetch_flat_string($sql);

	if ($data_items) {
		$sql = "DELETE FROM `plugin_reportit_data_items`
				WHERE `plugin_reportit_data_items`.`report_id` = $report_id
				AND `plugin_reportit_data_items`.`id` in ($data_items)";
		db_execute($sql);
	}
}



function autoexport($report_id){

	/* load report settings */
	$report_settings = db_fetch_row("SELECT * FROM plugin_reportit_reports WHERE id = $report_id");

	/* main export folder */
	$main_folder = read_config_option('reportit_exp_folder');
	if ($main_folder != '') {
		$main_folder .= (substr($main_folder, -1) == '/') ? '' : '/';
	} else {
		$main_folder = REPORTIT_EXP_FD;
	}

	/* export folder per template definition */
	$template_folder = db_fetch_cell("SELECT b.export_folder
										FROM plugin_reportit_reports AS a
										INNER JOIN plugin_reportit_templates as b
										ON a.template_id = b.id
										WHERE a.id = $report_id");

	$template_id = $report_settings['template_id'];


	/* define the correct report folder */
	if ($template_folder != ''){

		$template_folder .= (substr($template_folder, -1) == '/') ? '' : '/';
		$report_folder = $template_folder . "$report_id/";
	} else {

		/* check if main export folder is available */
		if (!is_dir($main_folder)) {
			run_error(17, $report_id, 0, "Main export folder does not exist.");
			return false;
		}

		$template_folder = $main_folder . "$template_id/";
		$report_folder = $template_folder . "$report_id/";
	}

	/* check if the template folder is available or try to create it */
	if (!is_dir($template_folder)) {
		run_error(16, $report_id, 0, "Export folder '$template_folder' does not exist.");

		/* try to create that folder */
		if (@mkdir($template_folder,0755) == false) {
			run_error(17, $report_id, 0, "Unable to create export folder '$template_folder'.");
			return false;
		} else {
			run_error(16, $report_id, 0, "New export folder '$template_folder' created.");
		}
	}

	/* check if report folder is available or try to create it*/
	if (!is_dir($report_folder)) {
		run_error(16, $report_id, 0, "Export folder '$report_folder' does not exist.");

		/* try to create that folder */
		if (@mkdir($report_folder,0755) == false) {
			run_error(17, $report_id, 0, "Unable to create export folder '$report_folder'.");
			return false;
		} else {
			run_error(16, $report_id, 0, "New export folder '$report_folder' created.");
		}
	}

	/* try to create a new report export file */
	$file_format       = ($report_settings['autoexport'] != '') ? $report_settings['autoexport'] : 'CSV';
	$file_type         = ($file_format != 'SML') ? strtolower($file_format) : 'xml';
	$filename          = $report_settings['start_date'] . '_' . $report_settings['end_date'] . ".$file_type";
	$report_path       = $report_folder . $filename;
	$export_function   = "export_to_" . $file_format;

	/* clean up the export folder if necessary */
	if ($report_settings['autoexport_max_records']) {
		if ($path_handle = opendir($report_folder)) {

			$file_format_lenght = strlen($file_format);
			$files = array();
			while (false !== ($file = readdir($path_handle))) {
				if (substr($file, -$file_format_lenght) == $file_type) {
					list($start, $end) = explode("_", $file);
					list($year, $month, $day) = explode("-", $start);
					$files[mktime(0,0,0,$month, $day, $year)] = $file;
				}
			}
			ksort($files);
			closedir($path_handle);
			if (cacti_sizeof($files)> $report_settings['autoexport_max_records']) {
				/* define the number of files that has to be dropped */
				$num_of_drops = sizeof($files) - $report_settings['autoexport_max_records'] + 1;
				$files = array_slice($files, 0, $num_of_drops);
				foreach($files as $filename) {
					if (!unlink($report_folder . $filename)) {
						run_error(17, $report_id, 0, "Unable to delete old export file.");
					}
				}
			}
		} else {
			run_error(17, $report_id, 0, "Unable read export folder");
		}
	}

	if (file_exists($report_path)) {
		run_error(17, $report_id, 0, "Export $report_path still exists.");
		return false;
	} else {
		$file_handle = fopen($report_path, 'a');
		if (!$file_handle) {
			run_error(17, $report_id, 0, "Unable to create export file.");
			return false;
		}

		/* load export data and write it into the export file */
		if (function_exists($export_function)) {
			$data = get_prepared_report_data($report_id,'export');
			$data = $export_function($data);
			fwrite($file_handle, $data);
		}
		fclose($file_handle);
	}
	return true;
}
