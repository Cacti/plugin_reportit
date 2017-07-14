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

function owner($report_id) {
	$tmp = db_fetch_row_prepared('SELECT b.username, b.full_name
		FROM reportit_reports as a
		INNER JOIN user_auth as b
		ON b.id = a.user_id
		WHERE a.id = ?' ,
		array($report_id));

	if (sizeof($tmp)) {
		return $tmp['full_name'] . ' (' . $tmp['username'] . ')';
	} else {
		return __('Unknown Owner');
	}
}

function get_prepared_report_data($report_id, $type, $sql_where = '') {
	$report_measurands = array();
	$report_variables  = array();

	/* load report configuration + template description */
	$report_data = db_fetch_row_prepared('SELECT a.*, b.description AS template_name
		FROM reportit_reports AS a
		INNER JOIN reportit_templates AS b
		ON a.template_id = b.id
		WHERE a.id = ?',
		array($report_id));

	if (!sizeof($report_data)) {
		return false;
	}

	/* get the owner of this report */
	$report_data['owner']	= owner($report_id);

	/* load measurand configurations */
	$tmps = db_fetch_assoc_prepared("SELECT *
		FROM reportit_measurands
		WHERE template_id = ?",
		array($report_data['template_id']));

	foreach ($tmps as $tmp) {
		$report_measurands[$tmp['id']] = $tmp;
	}

	/* load configurations of variables */
	$report_variables = db_fetch_assoc_prepared('SELECT a.id, a.name,
		a.description, b.value, a.min_value, a.max_value
		FROM reportit_variables AS a
		INNER JOIN reportit_rvars AS b
		ON a.id = b.variable_id
		AND b.report_id = ?
		WHERE a.template_id = ?',
		array($report_id, $report_data['template_id']));

	/* load data source alias */
	$sql = "SELECT data_source_name, data_source_alias
		FROM reportit_data_source_items
		WHERE template_id = " . $report_data['template_id'];
    $report_ds_alias = db_custom_fetch_assoc($sql, 'data_source_name', false, false);

	switch ($type) {
	case 'export':
		$sql = "SELECT c.name_cache, b.*, a.*
			FROM reportit_results_$report_id AS a
			INNER JOIN reportit_data_items AS b
			ON a.id = b.id AND b.report_id = $report_id
			INNER JOIN data_template_data AS c
			ON c.local_data_id = a.id
			$sql_where";

		break;
	case 'graidle':
		$sql = $sql_where;

		break;
	case 'graph':
		return array(
			'report_data'       => $report_data,
			'report_measurands' => $report_measurands
		);

		break;
	case 'view':
		$sql = "SELECT a.*, b.*, c.name_cache
			FROM reportit_results_$report_id AS a
			INNER JOIN reportit_data_items AS b
			ON (a.id = b.id
			AND b.report_id = $report_id)
			INNER JOIN data_template_data AS c
			ON c.local_data_id = a.id
			$sql_where";

		break;
	}

	$report_results = db_fetch_assoc($sql);

	/* build data package for return */
	$data = array(
		'report_data'       => $report_data,
		'report_results'    => $report_results,
		'report_measurands' => $report_measurands,
		'report_variables'  => $report_variables,
		'report_ds_alias'   => $report_ds_alias
	);

	return $data;
}

function get_prepared_archive_data($cache_id, $type, $sql_where = '') {
	$report_measurands = array();
	$report_variables  = array();

	/* load report configuration */
	$report_data = db_fetch_row_prepared('SELECT *
		FROM reportit_cache_reports
		WHERE cache_id = ?',
		array($cache_id));

	/* save serialized data source alias separately */
	$report_ds_alias = unserialize($report_data['data_template_alias']);
	unset($report_data['data_template_alias']);

	/* load configured measurands */
	$tmps = db_fetch_assoc_prepared('SELECT *
		FROM reportit_cache_measurands
		WHERE cache_id = ?',
		array($cache_id));

	if (sizeof($tmps)) {
		foreach ($tmps as $tmp) {
			$report_measurands[$tmp['id']] = $tmp;
		}
	}

	/* load configured variables */
	$report_variables = db_fetch_assoc_prepared('SELECT *
		FROM reportit_cache_variables
		WHERE cache_id = ?',
		array($cache_id));

	switch ($type) {
	case 'export':
		$sql = 'SELECT * FROM reportit_tmp_' . $cache_id . ' as a ' . $sql_where;

		break;
	case 'graidle':
		$sql = $sql_where;

		break;
	case 'graph':
		return array(
			'report_data'       => $report_data,
			'report_measurands' => $report_measurands
		);

		break;
	case 'view':
		$sql = 'SELECT * FROM reportit_tmp_' . $cache_id . ' AS a ' . $sql_where;

		break;
	}

	$report_results = db_fetch_assoc($sql);

	/* build data package for return */
	$data = array(
		'report_data'       => $report_data,
		'report_results'    => $report_results,
		'report_measurands' => $report_measurands,
		'report_variables'  => $report_variables,
		'report_ds_alias'   => $report_ds_alias
	);

	return $data;
}

/**
 * db_custom_fetch_assoc()
 *
 * @param string 	$sql		contains the SQL call
 * @param string 	$index		contains the name of the coloumn which should be used as index
 *                              if false (default) the index will numerical beginning from zero.
 * @param binary	$multi		save the columns multidimensional. if false then you will get only one result per index
 * @param binary	$assoc		if true (default) then save the $values associated ($key => $value) into the index array
 * 								requires that $multi is true.
 * @return binary				returns an array or false if the SQL command failed
 */
function db_custom_fetch_assoc($sql, $index = false, $multi = true, $assoc = true){
	$raw_data = array();
	$srt_data = array();

	$raw_data = db_fetch_assoc($sql);

	if (sizeof($raw_data)) {
		foreach ($raw_data as $row_key => $row) {
			if ($index !== false && !array_key_exists($index, $row)) {
				return false;
			}

			$index_key = ($index === false) ? $row_key : $row[$index];

			foreach ($row as $key => $value){
				if ($key != $index){
					if ($multi) {
						if ($assoc) {
							$srt_data[$index_key][$key] = $value;
						} else {
							$srt_data[$index_key][] = $value;
						}
					} else {
						$srt_data[$index_key]=$value;
					}
				}
			}
		}

		return $srt_data;
	} else {
		return false;
	}
}

/**
 * db_custom_fetch_flat()
 * returns an numerical array with only one dimension. Every row will be saved column for column.
 * @param string    $sql    contains the SQL call
 * @return
 */
function db_custom_fetch_flat_array($sql){
    $raw_data = array();
    $srt_data = array();

    $raw_data = db_fetch_assoc($sql);

    if (sizeof($raw_data)> 0) {
        foreach ($raw_data as $row) {
            foreach ($row as $value) {
				 $srt_data[] = $value;
			}
        }

        return $srt_data;
    } else {
        return false;
    }
}

/**
 * db_custom_fetch_string()
 * returns an string. Every row will be saved column for column and separated by a given delimiter.
 * @param string    $sql            contains the SQL call
 * @param string    $delimiter      character for separating the columns. Default is ","
 * @return
 */
function db_custom_fetch_flat_string($sql, $delimiter = ','){
    $raw_data = array();
    $srt_data = '';

    $raw_data = db_fetch_assoc($sql);

    if (sizeof($raw_data)> 0) {
        foreach ($raw_data as $row) {
            foreach ($row as $value) {
				$srt_data .= $value . $delimiter;
			}
        }

        return substr($srt_data,0,-strlen($delimiter));
    } else {
        return false;
    }
}


function rp_get_timespan($preset_timespan, $present, $enable_tmz = false) {
    //Set preconditions
    $today = ($enable_tmz) ? gmdate('Y-m-d') : date('Y-m-d');
    list($ys, $ms, $ds) = explode('-', $today);
    list($ye, $me, $de) = explode('-', $today);

    //Set report start date
    switch ($preset_timespan)  {
	case 'Today':

	    break;
	case 'Last 1 Day':
	    $ds-=1;$de-=1;

	    break;
	case 'Last 2 Days':
	    $ds-=2;$de-=1;

	    break;
	case 'Last 3 Days':
	    $ds-=3;$de-=1;

	    break;
	case 'Last 4 Days':
	    $ds-=4;$de-=1;

	    break;
	case 'Last 5 Days':
	    $ds-=5;$de-=1;

	    break;
	case 'Last 6 Days':
	    $ds-=6;$de-=1;

	    break;
	case 'Last 7 Days':
	    $ds-=7;$de-=1;

	    break;
	case 'Last Week (Sun - Sat)':
	    $ds -= ($enable_tmz) ? 7 + gmdate('w') : 7 + date('w');
	    $de = $ds + 6;

	    break;
	case 'Last Week (Mon - Sun)':
	    $ds -= ($enable_tmz) ? 6 + gmdate('w') : 6 + date('w');
	    $de = $ds + 6;

	    break;
	case 'Last 14 Days':
	    $ds-=14;$de-=1;

	    break;
	case 'Last 21 Days':
	    $ds-=21;$de-=1;

	    break;
	case 'Last 28 Days':
	    $ds-=28;$de-=1;

	    break;
	case 'Current Month':
		$de = ($ds == 1)? $ds : $de-1;
	    $ds=1;

	    break;
	case 'Last Month':
	    $ms-=1;$ds=1;$de=0;

	    break;
	case 'Last 2 Months':
	    $ms-=2;$ds=1;$de=0;

	    break;
	case 'Last 3 Months':
	    $ms-=3;$ds=1;$de=0;

	    break;
	case 'Last 4 Months':
	    $ms-=4;$ds=1;$de=0;

	    break;
	case 'Last 5 Months':
	    $ms-=5;$ds=1;$de=0;

	    break;
	case 'Last 6 Months':
	    $ms-=6;$ds=1;$de=0;

	    break;
	case 'Current Year':
		$de = ($ds == 1 & $ms ==1 )? $ds : $de-1;
	    $ms=1;$ds=1;

	    break;
	case 'Last Year':
	    $ms=1;$ds=1;$ys-=1;$me=1;$de=0;

	    break;
	case 'Last 2 Years':
	    $ms=1;$ds=1;$ys-=2;$me=1;$de=0;

	    break;
	default:
	    break;
    }

    $dates = array();

    $dates['start_date'] = ($enable_tmz) ? gmdate('Y-m-d', gmmktime(0,0,0, $ms, $ds, $ys)) : date('Y-m-d', mktime(0,0,0, $ms, $ds, $ys));

	if ($present) {
		$dates['end_date'] = $today;
	} else {
		$dates['end_date'] = ($enable_tmz) ? gmdate('Y-m-d', gmmktime(0,0,0, $me, $de, $ye)) : date('Y-m-d', mktime(0,0,0, $me, $de, $ye));
	}

	return $dates;
}

function get_unit($value, $prefixes, $data_type, $data_precision) {
    global $threshold, $binary, $decimal, $IEC;

	if (!$threshold) {
		$threshold 	= 0.5;

		$decimal = array(
			'Y' => pow(1000,8),
			'Z' => pow(1000,7),
			'E' => pow(1000,6),
			'P' => pow(1000,5),
			'T' => pow(1000,4),
			'G' => pow(1000,3),
			'M' => pow(1000,2),
			'K' => 1000
		);

		$binary = array(
			'Y' => pow(1024,8),
			'Z' => pow(1024,7),
			'E' => pow(1024,6),
			'P' => pow(1024,5),
			'T' => pow(1024,4),
			'G' => pow(1024,3),
			'M' => pow(1024,2),
			'K' => 1024
		);

		$IEC = read_config_option('reportit_use_IEC');
	}

	$type_specifiers = array('b', 'f', 'd', 'u', 'x', 'X', 'o', 'e');

	$data_type = $type_specifiers[$data_type];

	/* use precision for type FLOAT and SCIENTIFIC NOTIFICATION only*/
	if (is_numeric($data_precision) && in_array($data_type, array('f', 'e'))) {
		$data_precision = '.' . $data_precision;
	} else {
		$data_precision = '';
	}

	if ($value === 0) {
		return 0;
	} elseif ($value == NULL) {
		return "NA";
	} elseif ($prefixes == 0) {
		return sprintf("%" . $data_precision . $data_type, $value);
	} elseif ($prefixes == 0 || $value == 0) {
		return $value;
	}

	if ($prefixes == 1) {
		$k   = ($IEC) ? 'K' : 'k';
		$i   = ($IEC) ? 'i' : '';
		$pre = &$binary;
    } else {
		$k   = 'k';
		$i   = '';
		$pre = &$decimal;
    }

    $absolute = abs($value);

    switch($value) {
	case ($absolute >= $pre['Y']): //YOTTA
	    $value /= $pre['Y'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " Y$i");

	    break;
	case ($absolute >= $pre['Y']*$threshold):
	    $value /= $pre['Y'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " Y$i");

	    break;
	case ($absolute >= $pre['Z']): //ZETTA
	    $value /= $pre['Z'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " Z$i");

	    break;
	case ($absolute >= $pre['Z']*$threshold):
	    $value /= $pre['Z'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " Z$i");

	    break;
	case ($absolute >= $pre['E']): //EXA
	    $value /= $pre['E'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " E$i");

	    break;
	case ($absolute >= $pre['E']*$threshold):
	    $value /= $pre['E'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " E$i");

	    break;
	case ($absolute >= $pre['P']): //PETA
	    $value /= $pre['P'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " P$i");

	    break;
	case ($absolute >= $pre['P']*$threshold):
	    $value /= $pre['P'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " P$i");

	    break;
	case ($absolute >= $pre['T']): //TERA
	    $value /= $pre['T'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " T$i");

	    break;
	case ($absolute >= $pre['T']*$threshold):
	    $value /= $pre['T'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " T$i");

	    break;
	case ($absolute >= $pre['G']): //GIGA
	    $value /= $pre['G'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " G$i");

	    break;
	case ($absolute >= $pre['G']*$threshold):
	    $value /= $pre['G'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " G$i");

	    break;
	case ($absolute >= $pre['M']): //MEGA
	    $value /= $pre['M'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " M$i");

	    break;
	case ($absolute >= $pre['M']*$threshold):
	    $value /= $pre['M'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " M$i");

	    break;
	case ($absolute >= $pre['K']): //KILO
	    $value /= $pre['K'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " $k$i");

	    break;
	case ($absolute >= $pre['K']*$threshold):
	    $value /= $pre['K'];
	    return (sprintf("%" . $data_precision . $data_type, $value) . " $k$i");

	    break;
	default:
	    return sprintf("%" . $data_precision . $data_type, $value);

	    break;
    }
}

function create_rvars_entries($variable_id, $template_id, $default) {
	$ids = db_fetch_assoc_prepared('SELECT id
		FROM reportit_reports
		WHERE template_id = ?',
		array($template_id));

	if (sizeof($ids)) {
		$list = '';

		foreach ($ids as $id) {
			$list .= "($template_id, {$id['id']}, $variable_id, $default),";
		}
		//Remove last comma
		$list = substr($list, 0, strlen($list)-1);

		db_execute("INSERT INTO reportit_rvars
			(template_id, report_id, variable_id, value)
			VALUES $list");
	}
}

function reset_report($report_id) {
    // Set report values last_run and runtime to zero
    db_execute_prepared("UPDATE reportit_reports
		SET last_run = '0000-00-00 00:00:00', runtime = '0'
		WHERE id = ?",
		array($report_id));
}

/**
 * get_possible_rra_names()
 * returns an array with all possible names of the Round Robbin Archives for this report template
 * @param int $template_id contains the id of the current report template
 * @return an array with all possible round robin archives
 */
function get_possible_rra_names($template_id) {
    //Get all possible names of the RRAs for this type of template
    $names = array();
    $array = array();

    $names = db_fetch_assoc_prepared("SELECT b.data_source_name
		FROM reportit_data_source_items AS a
		LEFT JOIN data_template_rrd AS b
		ON a.id = b.id
		WHERE a.template_id = ?
		AND a.id != 0", array($template_id));

    foreach ($names as $name) {
		$array[] = $name['data_source_name'];
	}

    return $array;
}

/**
 * get_interim_results()
 *
 * @param int $measurand_id contains the id of the current measurand
 * @param int $template_id contains the id of the template the measurand belongs to
 * @param boolean $ln returns a line break after every interim result
 * @return array with the syntax of possible interim results
 */
function get_interim_results($measurand_id, $template_id, $ln = false) {
	$array           = array();
	$names           = array();
	$interim_results = array();

	$names = get_possible_rra_names($template_id);
	$sql = "SELECT abbreviation, spanned
		FROM reportit_measurands
		WHERE template_id=$template_id ";

	if ($measurand_id != 0) {
		$sql .= "AND id<$measurand_id";
	}

	$array = db_fetch_assoc($sql);

	if (sizeof($array)) {
	    foreach ($array as $interim_result) {
			$interim_results[] = $interim_result['abbreviation'];
			if ($interim_result['spanned'] == 0) {
				foreach ($names as $name) {
					$interim_results[] = $interim_result['abbreviation'] . ':' . $name;
				}
			}
		}
	}

	return $interim_results;
}

function get_possible_variables($template_id) {
	global $calc_var_names;

	// Fetch all variables which has been defined for this template
	$names = array();
	$array = array();

	// Check whether maxValue is valid
	$maximum = db_fetch_cell_prepared('SELECT DISTINCT a.rrd_maximum
		FROM data_template_rrd as a
		INNER JOIN reportit_templates as b
		ON a.data_template_id = b.data_template_id
		AND b.id = ?
		WHERE a.local_data_id = 0',
		array($template_id));

    if (!is_numeric($maximum) || $maximum == 0) {
		unset($calc_var_names[0]);
	}

    $names = db_fetch_assoc_prepared('SELECT abbreviation
		FROM reportit_variables
		WHERE template_id = ?',
		array($template_id));

	foreach ($calc_var_names as $name) {
		$array[] = $name;
	}

	foreach ($names as $name) {
		$array[] = $name['abbreviation'];
	}

	return array_flip($array);
}

/**
 * get all available data query variables for given data template id
 * @param int $template_id	- the data template id
 * @return array			- array of data query cache variables
 */
function get_possible_data_query_variables($template_id) {
	// any data query associated with this data template?
	$sql = "SELECT DISTINCT(`data_local`.`snmp_query_id`) " .
			"FROM `data_local` " .
			"INNER JOIN `reportit_templates` " .
			"ON `data_local`.`data_template_id` = `reportit_templates`.`data_template_id` " .
			"WHERE `reportit_templates`.`id`=$template_id";
	$available_data_queries = db_fetch_assoc($sql);

	// in case there is no data query, have $names initialized
	$names = array();

	if (sizeof($available_data_queries)) {
		$data_query_list = 'snmp_query_id IN (';
		foreach($available_data_queries as $data_queries) {
			$data_query_list .= $data_queries['snmp_query_id'] . ',';
		}
		$data_query_list = substr($data_query_list, 0, -1) . ")";

		// get all host_snmp_cache variables for those list of data queries
		$sql = "SELECT DISTINCT(`host_snmp_cache`.`field_name`) " .
				"FROM `host_snmp_cache` " .
				"WHERE $data_query_list";

		$names = db_fetch_assoc($sql);
	}

	$array = array();
    foreach ($names as $name) {
            $array[] = $name['field_name'];
        }
	debug($array, 'Array of possible Data Query Variables');
	return $array;
}

function get_template_status($template_id) {
    //Returns '1' if the template has been locked.
    $sql = "SELECT locked FROM reportit_templates WHERE id=$template_id";
    $status = db_fetch_cell($sql);
    return $status;
}


function in_process($report_id, $status = 1) {
    $sql = "UPDATE reportit_reports SET in_process=$status WHERE id=$report_id";
    db_execute($sql);
}


function stat_process($report_id) {
    $sql = "SELECT in_process FROM reportit_reports WHERE id=$report_id";
    return db_fetch_cell($sql);
}

function config_date_format($no_time=TRUE) {
	$date_fmt = read_graph_config_option("default_date_format");
	$datechar = read_graph_config_option("default_datechar");

	if (!isset($date_fmt)) return("Y-m-d H:i:s");

	if ($datechar == GDC_HYPHEN) {
		$datechar = "-";
	} else {
		$datechar = "/";
	}

	switch ($date_fmt) {
		case GD_MO_D_Y:
			$dd = ("m" . $datechar . "d" . $datechar . "Y");
			break;
		case GD_MN_D_Y:
			$dd = ("M" . $datechar . "d" . $datechar . "Y");
			break;
		case GD_D_MO_Y:
			$dd = ("d" . $datechar . "m" . $datechar . "Y");
			break;
		case GD_D_MN_Y:
			$dd = ("d" . $datechar . "M" . $datechar . "Y");
			break;
		case GD_Y_MO_D:
			$dd = ("Y" . $datechar . "m" . $datechar . "d");
			break;
		case GD_Y_MN_D:
			$dd = ("Y" . $datechar . "M" . $datechar . "d");
			break;
		default:
			return ("Y-m-d H:i:s");
	}

	if (!$no_time)
		return ($dd . " -- H:i:s");
	else
		return $dd;
}



/* ********************* New functions ********************************* */

function debug(&$value, $msg = '', $fmsg = '') {

    if (!defined('REPORTIT_DEBUG')) return;
    get_mem_usage();

	if ($msg != '') print "\n\t\t******* $msg *******\n";

	if (is_array($value)) {
		if ($fmsg == '') {
			print_r($value);
			print "\n";
		} else {
			print "\t\t$fmsg: "; print_r($value);
		}
	} else {
		if ($fmsg != '') {
			print "\t\t$fmsg: \t$value\n";
		} else {
			print "\t\t$value\n";
		}
	}
}


function get_report_setting($report_id, $column){
	$sql = "SELECT $column FROM reportit_reports WHERE id = $report_id";
	return db_fetch_cell($sql);
}

function get_graph_config_option($config_name, $user_id){
    $sql = "SELECT value FROM settings_graphs WHERE name='$config_name' and user_id='$user_id'";
    $db_setting = db_fetch_row($sql);

    if (isset($db_setting["value"])) {
        return $db_setting["value"];
    } else{
        return read_default_graph_config_option($config_name);
    }
}

function auto_rounding(&$values, $rounding, $order){

    $threshold = 0.5;
    $base = ($rounding == 2) ? 1000 : 1024;

    $highest = ($order == 'DESC') ? reset($values) : end($values);
    if (reset($values) == 0 & end($values) == 0) return 0;
    if ($highest < 0) $highest*=(-1);

    $x = 0;
    for ($exp=1; $x<$highest; $exp++){
        $x = pow($base, $exp);
        if ($x*$threshold < $highest) continue;
        else break;
   }

    /* workaround to avoid issues Graidle has with scaling the Y-Axis if highest values is under 1 */
    if ($highest/pow($base, $exp-1)<1) $exp--;

    $devisor = pow($base, $exp-1);
    foreach ($values as $key => $value){
        $values[$key] = sprintf("%01.2f", ($value/=$devisor));
    }
    return $exp-1;
}




function load_external_libs($name){
	global $config;

	switch ($name) {
		case 'pclzip':
			if (!defined('PCLZIP_TEMPORARY_DIR')) define( 'PCLZIP_TEMPORARY_DIR', REPORTIT_TMP_FD);
			include_once(CACTI_BASE_PATH . '/plugins/reportit/lib_ext/pclzip/pclzip.lib.php');
		break;
		case 'graidle':

		break;
		case 'cleanXML':
			include_once(CACTI_BASE_PATH . '/plugins/reportit/lib_ext/xml/xml.lib.php');
		break;
	}
}


function clean_for_sql(&$str){
	$str = substr($str, 0, strlen($str)-1);
}



/* ********************* Archive Functions ***************************** */

function rename_xml_file($p_event, &$p_header) {
	$p_header['stored_filename'] = $p_header['mtime'] . '.xml';
	return 1;
}



function update_xml_archive($report_id) {
	global $config;

	$eol       = "\r\n";
	$arc_path  = read_config_option('reportit_arc_folder');
	$arc_path .= (substr($arc_path, -1) == '/') ? '' : '/';
	$tmp_path  = REPORTIT_TMP_FD;
	$arc_file  = (($arc_path == '') ? REPORTIT_ARC_FD : $arc_path) . $report_id . '.zip';

	/* maximum number of files the archive should contain */
	$max = get_report_setting($report_id, 'autoarchive');

	/* load report data */
	$data = get_prepared_report_data($report_id, 'view');

    /* transform the data source aliases to the old style */
    $data['report_data']['data_template_alias'] = serialize($data['report_ds_alias']);

	/* use an output puffer for flushing */
	ob_start();
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>$eol";
	print "<cacti>$eol<report>$eol<settings>$eol";

	foreach ($data['report_data'] as $key => $value) print "<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
	print "</settings>$eol<measurands>$eol";

	foreach ($data['report_measurands'] as $measurand){
		print "<measurand>$eol";
		foreach ($measurand as $key => $value) print "<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
		print "</measurand>$eol";
	}
	print "</measurands>$eol<data_items>$eol";

	foreach ($data['report_results'] as $results){
		print "<item>$eol";
		foreach ($results as $key => $value) print "<_di__$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</_di__$key>$eol";
		print "</item>$eol";
	}
	print "</data_items>$eol<variables>$eol";

	foreach ($data['report_variables'] as $variable) {
		print "<variable>$eol";
		foreach ($variable as $key => $value) print "<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
		print "</variable>$eol";
	}

	print "</variables>$eol</report>$eol</cacti>$eol";
	$content = ob_get_clean();
	$content = utf8_encode($content);

	/* create a tempary file and save XML output*/
	$cfg = $data['report_data'];
	$tmpfile = REPORTIT_TMP_FD .  strtotime($cfg['start_date']) . "_" . strtotime($cfg['end_date']) . ".xml";
	$filehandle = fopen($tmpfile, "w");
	fwrite($filehandle, $content);
	fclose($filehandle);

	/* load zip file support */
	load_external_libs('pclzip');

	/* set handle for archiving */
	$archive = new PclZip($arc_file);

	/* use file rotation */
	if (($stat = $archive->properties()) != 0) {
		$cnt = ($max == 0) ? 0 : $stat['nb'];
		if ($cnt > $max+1) {
			$end = $cnt - $max-1;
			$archive->delete(PCLZIP_OPT_BY_INDEX, '0-'.$end);
		} else if ($cnt == $max+1){
			$archive->delete(PCLZIP_OPT_BY_INDEX, '0');
		}
	};

	/* add XML output to the archive */
	$v_list = $archive->add($tmpfile, PCLZIP_OPT_REMOVE_ALL_PATH);
	if ($v_list == 0) {
    	die("Error : ".$archive->errorInfo(true));
  	}

	/* change mode */
	chmod($arc_file, 0644);

	/* clean up TMP */
	unlink($tmpfile);
}



function cache_xml_file($report_id, $mtime){

	$cache_id	= $report_id . '_' . $mtime;
	$columns	= '';
	$values		= '';
	$cols		= array();
	$index		= false;

	$arc_path	= read_config_option('reportit_arc_folder');
	$arc_path  .= (substr($arc_path, -1) == '/') ? '' : '/';
	$arc_file	= (($arc_path == '') ? REPORTIT_ARC_FD : $arc_path) . $report_id . '.zip';

	/* check if cache is up to date */
	$sql = "SHOW TABLES LIKE 'reportit_tmp_" . $cache_id . "'";
	if (db_fetch_cell($sql) !== null) return;

	/* load zip file support */
	load_external_libs('pclzip');

	/* set handle for archiving */
	$archive = new PclZip($arc_file);

	/* unzip xml archive and load xml file */
	$info = $archive->listContent();
	foreach ($info as $key => $array) {
		if ($array['mtime'] == $mtime) {
			$index = $array['index'];
			break;
		}
	}
	if ($index === false) die_html_custom_error("Report not found in archive.", true);
	$data = $archive->extractByIndex($index, PCLZIP_OPT_EXTRACT_AS_STRING);

	/* use external XML class to be compatible with PHP4 */
	load_external_libs('cleanXML');
	$xml = new Xml();
	$archive = $xml->parse($data[0]['content'], NULL, 'ISO-8859-1');

	/* transform data and fill up the cache tables */
	trans_array2sql($archive['report']['settings'], $columns, $values, $cache_id);
	$sql = "REPLACE INTO reportit_cache_reports $columns VALUES $values;";
	db_execute($sql);

	trans_array2sql($archive['report']['measurands'], $columns, $values, $cache_id);
	$sql = "REPLACE INTO reportit_cache_measurands $columns VALUES $values;";
	db_execute($sql);

	if (trans_array2sql($archive['report']['variables'], $columns, $values, $cache_id)) {
	$sql = "REPLACE INTO reportit_cache_variables $columns VALUES $values;";
	db_execute($sql);
	}

	trans_array2sql($archive['report']['data_items'], $columns, $values, false);
	$columns = str_replace('_di__', '', $columns);

	$cols = explode(",", substr($columns, 2, -1));
	$sql = "CREATE TABLE IF NOT EXISTS reportit_tmp_" . $cache_id . " (";

	foreach ($cols as $name){
		if ($name == '`id`') $sql .= $name . " int(11) NOT NULL DEFAULT 0,";
		elseif (strpos($name, '__') !== false) $sql .= $name . " DOUBLE,";
		else $sql .= $name . " VARCHAR(255) NOT NULL DEFAULT '',";
	}
	$sql .= "PRIMARY KEY (`id`)) ENGINE=MyISAM;";
	db_execute($sql);

	$sql = "REPLACE INTO reportit_tmp_" . $cache_id . " $columns VALUES $values;";
	db_execute($sql);
}

function trans_array2sql(&$array, &$columns, &$values, $cache_id = false) {
	$keys  = false;
	$multi = false;
	$sub_values = '';

	/* reset */
	$columns = $cache_id ? '`cache_id`' : '';
	$values = '';

	if (!is_array($array)) {
		return false;
	}

	if (sizeof($array)) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if (isset($value[0])) {
					foreach ($value as $sub_array) {
						$sub_values = '';

						foreach ($sub_array as $sub_key => $sub_value) {
							if (!$keys) {
								$columns .= ", `$sub_key`";
							}

							$sub_values .= (strpos($sub_value, '{{') !== false) ? ", ''" : ', ' . db_qstr($sub_value);
						}

						$keys = true;

						$values .= $cache_id ? ",('$cache_id' $sub_values)" : ',(' . substr($sub_values, 1) .')';

						$multi = true;
				     }
				} else {
					foreach ($value as $sub_key => $sub_value) {
						$columns .= ", `$sub_key`";
						$values  .= (strpos($sub_value, '{{') !== false) ? ", ''" : ', ' . db_qstr($sub_value);
					}
				}
			} else {
				$columns .= ", `$key`";
				$values	 .= (strpos($value, '{{') !== false) ? ", ''" : ', ' . db_qstr($value);
			}
		}
	} else {
		return false;
	}

	$columns = $cache_id ? "($columns)" : '(' . substr($columns, 1) . ')';
	$values  = ($multi == true) ? substr($values, 1) : (($cache_id !== false)? "('$cache_id' $values)" : "(" . substr($values, 1) . ")" );

	return true;
}



function info_xml_archive($report_id) {
	$content  = array();
	$arc_path = read_config_option('reportit_arc_folder');
	$arc_file = (($arc_path == '') ? REPORTIT_ARC_FD : $arc_path) . "/$report_id" . '.zip';
	$format   = config_date_format();

	/* load zip file support */
	load_external_libs('pclzip');

	/* set handle for archiving */
	$archive = new PclZip($arc_file);

	/* collect some informations about this archive */
	if (($list = $archive->listContent()) != 0) {
		foreach ($list as $key => $file) {
			if ($file['status'] == 'ok') {
				list($from, $to) = explode("_", str_replace('.xml', '', $file['filename']));
				$content[$file['mtime']] = date($format, $from) . " -> " . date($format, $to);
			}
		}

		/* show the newest ones first */
		krsort($content, SORT_NUMERIC);
		return $content;
	} else {
		return false;
	}
}


function average($array) {
	if (sizeof($array)== 0) {
		return '';
	}

	return (array_sum($array)/count($array));
}

function transform_htmlspecialchars(&$data){
	if (!is_array($data)) {
		htmlspecialchars($data);
	} else {
		foreach ($data as $key_1 => $value_1) {
			if (is_array($value_1)) {
				foreach ($value_1 as $key_2 => $value_2) {
					if (is_array($value_2)) {
						foreach ($value_2 as $key_3 => $value_3) {
							$value_2[$key_3] = htmlspecialchars($value_3);

						}

						$value_1[$key_2] = $value_2;
					} else {
						$value_1[$key_2] = htmlspecialchars($value_2);
					}
				}

				$data[$key_1] = $value_1;
			} else {
				$data[$key_1] = htmlspecialchars($value_1);
			}
		}
	}
}

function get_mem_usage() {
	if (version_compare(phpversion(), '5.2.1') == -1) return;

	$memory_system  = ini_get('memory_limit') .'B ';
	$memory_used    = round(memory_get_usage()/pow(1024,2),2);
	$memory_peak    = round(memory_get_peak_usage()/pow(1024,2),2);

	if ($memory_system == -1) {
		$memory_system = 'unlimited';
		$memory_used   .= 'MB';
		$memory_peak   .= 'MB';
	} else {
		$memory_used   .= 'MB(' . round($memory_used/$memory_system*100,2) . '%)';
		$memory_peak   .= 'MB(' . round($memory_peak/$memory_system*100,2) . '%)';
	}

	print " Memory:  System: $memory_system   Used: $memory_used   Peak: $memory_peak\n";
}

/* Basical Email Functions */
function check_email_support(){
	if (function_exists('settings_version')) {
		return true;
	} else {
		return false;
	}
}

function send_scheduled_email($report_id){
	global $config;

	$data 	= '';
	$search = array('|title|', '|period|');

	include_once(CACTI_BASE_PATH . '/plugins/settings/include/mailer.php');

	/* load report based email settings */
	$report_settings  = db_fetch_row_prepared('SELECT *
		FROM reportit_reports
		WHERE id = ?',
		array($report_id));

	$replace          = array($report_settings['description'], $report_settings['start_date'] . '-' . $report_settings['end_date']);

	$email_subject    = ($report_settings['email_subject'] != '') ? $report_settings['email_subject'] : 'Scheduled report - |title| - |period|';
	$email_subject    = str_replace($search, $replace, $email_subject);
	$email_body       = ($report_settings['email_body'] != '') ? $report_settings['email_body'] : 'This is a scheduled report generated from Cacti.';
	$email_format     = ($report_settings['email_format'] != '') ? $report_settings['email_format'] : 'CSV';

	/* load list of recipients */
	$file_type        = ($email_format != 'SML') ? strtolower($email_format) : 'xml';
	$mine_type        = ($email_format != 'SML') ? 'application/' . strtolower($email_format) : 'application/' . 'vnd-ms-excel';

	$email_recipients = db_fetch_assoc_prepared('SELECT email
		FROM reportit_recipients
		WHERE report_id = ?',
		array($report_id));

	/* define additional attachment settings */
	$filename         = str_replace('<report_id>', $report_id, read_config_option('reportit_exp_filename') . ".$file_type");
	$export_function  = "export_to_" . $email_format;

	/* load global settings provided by Jimmy Connor */
	$how = read_config_option('settings_how');

	switch($how){
		case '1':
			/* use Sendmail */
			$sendmail = read_config_option('settings_sendmail_path');

			$Mailer = new Mailer(
				array(
					'Type'              => 'DirectInject',
					'DirectInject_Path' => $sendmail
				)
			);

			break;
		case '2':
			/* use SNMP */
			$smtp_host 		= read_config_option('settings_smtp_host');
			$smtp_port 		= read_config_option('settings_smtp_port');
			$smtp_username 	= read_config_option('settings_smtp_username');
			$smtp_password 	= read_config_option('settings_smtp_password');

			$Mailer = new Mailer(
				array(
					'Type'          => 'SMTP',
					'SMTP_Host'     => $smtp_host,
					'SMTP_Port'     => $smtp_port,
					'SMTP_Username' => $smtp_username,
					'SMTP_Password' => $smtp_password
				)
			);

			break;
		default:
			/* use PHPmail as default */
			$Mailer = new Mailer(array('Type' => 'PHP'));
	}

	$from 		= read_config_option('settings_from_email');
	$fromname 	= read_config_option('settings_from_name');

	if ($from == '' && isset($_SERVER['HOSTNAME'])) $from = 'Cacti@' . $_SERVER['HOSTNAME'];
	if ($fromname == '') $fromname = 'Cacti';
	$from = $Mailer->email_format($fromname, $from);

	/* setup email header */
	if ($Mailer->header_set('From', $from) === false) return $Mailer->error();
	if (!$Mailer->header_set('Subject', $email_subject)) return $Mailer->error();

	foreach ($email_recipients as $to) {
		if (!$Mailer->header_set('To', $to)) return $Mailer->error();
	}

	/* load export data and define the attachment file */
	if (function_exists($export_function)) {
	    $data = get_prepared_report_data($report_id,'export');
	    if ($data == '') return('Export failed');
	    $data = $export_function($data);
	    if ($Mailer->attach($data, $filename, $mine_type) == false) return $Mailer->error();
	}

	if ($Mailer->send($email_body) == false) return $Mailer->error();
	return false;
}

function export_report_template($template_id, $info=false) {
    $eol      = "\r\n";
    $checksum = '';

    /* load template data */
    $template_data = db_fetch_row_prepared('SELECT *
		FROM reportit_templates
		WHERE id = ?',
		array($template_id));

    /* exit if no result has been returned */
    if ($template_data == false) {
		return false;
	}

    /* export folder should not be shared */
    $template_data['export_folder'] = '';

    /* add global template definition to checksum */
    $checksum = convert_array2string($template_data);

    /* load definitions of variables */
    $variables_data = db_fetch_assoc_prepared('SELECT *
		FROM reportit_variables
		WHERE template_id = ?
		ORDER BY id',
		array($template_id));

    $checksum .= convert_array2string($variables_data);

    /* load definitions of measurands */
    $measurands_data = db_fetch_assoc_prepared('SELECT *
		FROM reportit_measurands
		WHERE template_id = ?
		ORDER BY id',
		array($template_id));

    $checksum .= convert_array2string($measurands_data);

    /* load definitions of data source items */
    $data_source_items_data = db_fetch_assoc_prepared('SELECT *
		FROM reportit_data_source_items
		WHERE template_id = ?
		ORDER BY id',
		array($template_id));

    $checksum .= convert_array2string($data_source_items_data);

    /* add template version and hash the checksum */
	$reportit_info = plugin_reportit_version();
	$reportit = array('version' => $reportit_info['version'], 'type' => 1);
    $checksum .= convert_array2string($reportit);
    $reportit['hash'] = md5($checksum);

    /* use an output puffer for flushing */
    ob_start();

    print "<cacti>$eol\t<report_template>$eol\t\t<reportit>$eol";

    foreach ($reportit as $key => $value) print "\t\t\t<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
    print "\t\t</reportit>$eol\t\t<general>$eol";

    if (is_array($info)) {
        foreach ($info as $key => $value)  print "\t\t\t<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
    }
    print "\t\t</general>$eol\t\t<settings>$eol";

    foreach ($template_data as $key => $value) print "\t\t\t<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
    print "\t\t</settings>$eol\t\t<measurands>$eol";

    foreach ($measurands_data as $measurand){
            print "\t\t\t<measurand>$eol";
            foreach ($measurand as $key => $value) print "\t\t\t\t<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
            print "\t\t\t</measurand>$eol";
    }
    print "\t\t</measurands>$eol\t\t<variables>$eol";

    foreach ($variables_data as $variable) {
            print "\t\t\t<variable>$eol";
            foreach ($variable as $key => $value) print "\t\t\t\t<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
            print "\t\t\t</variable>$eol";
    }
    print "\t\t</variables>$eol\t\t<data_source_items>$eol";

    foreach ($data_source_items_data as $data_source_item) {
            print "\t\t\t<data_source_item>$eol";
            foreach ($data_source_item as $key => $value) print "\t\t\t\t<$key>" . htmlspecialchars($value, ENT_NOQUOTES) . "</$key>$eol";
            print "\t\t\t</data_source_item>$eol";
    }
    print "\t\t</data_source_items>$eol\t</report_template>$eol</cacti>$eol";

    $content = ob_get_clean();
    $content = utf8_encode($content);

    return $content;
}

function convert_array2string($data) {
    $str = '';

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $subkey => $subvalue) {
                if (preg_match('/(^[\{]{2}([0-9]*)[\}]{2}$)/', $subvalue)) {
					$subvalue = '';
				}

                $str .= $subkey . $subvalue;
            }
        } else {
            if (preg_match('/(^[\{]{2}([0-9]*)[\}]{2}$)/', $value)) {
				$value = '';
			}

            $str .= $key . $value;
        }
    }

    return $str;
}

function clean_xml_waste(&$array, $replace = '') {
    foreach ($array as $key => $value) {
        $array[$key] = preg_replace('/(^[\{]{2}([0-9]*)[\}]{2}$)/', $replace, $value);
    }
}
