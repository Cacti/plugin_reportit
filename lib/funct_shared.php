<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
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

/**
 * Resolves a report's owning user for display, formatted as
 * 'Full Name (username)'. Called throughout this plugin's UI wherever
 * a report's owner needs to be shown.
 *
 * @param int $report_id The plugin_reportit_reports id to look up the
 *                       owner for.
 *
 * @return string The owner's 'Full Name (username)', or a localized
 *               'Unknown Owner' string if the report/user is not
 *               found.
 */
function owner($report_id) {
	$tmp = db_fetch_row_prepared('SELECT b.username, b.full_name
		FROM plugin_reportit_reports as a
		INNER JOIN user_auth as b
		ON b.id = a.user_id
		WHERE a.id = ?' ,
		[$report_id]);

	if (cacti_sizeof($tmp)) {
		return $tmp['full_name'] . ' (' . $tmp['username'] . ')';
	} else {
		return __('Unknown Owner', 'reportit');
	}
}

/**
 * Gathers a live (non-archived) report's full display/export data
 * package: its configuration/owner/template, measurand definitions,
 * variable values, data-source aliases, and (for view/export/graidle
 * modes) its stored per-run results rows. Called from show_report(),
 * export(), and reportit_prepare_store_report_results() to obtain a
 * report's rendering/export data.
 *
 * @param int    $report_id The report id to gather data for.
 * @param string $type      The data-package mode: 'view', 'export',
 *                         'graidle' (raw SQL passthrough), or 'graph'
 *                         (short-circuit, results omitted).
 * @param string $sql_where Additional SQL WHERE clause (for
 *                         view/export) or the full query (for
 *                         'graidle') to apply to the results query.
 *
 * @return array|false The assembled data package (report_data,
 *                     report_results, report_measurands,
 *                     report_variables, report_ds_alias), or false if
 *                     the report doesn't exist.
 */
function get_prepared_report_data($report_id, $type, $sql_where = '') {
	$report_measurands = [];
	$report_variables  = [];

	// load report configuration + template description
	$report_data = db_fetch_row_prepared('SELECT a.*, b.description AS template_name
		FROM plugin_reportit_reports AS a
		INNER JOIN plugin_reportit_templates AS b
		ON a.template_id = b.id
		WHERE a.id = ?',
		[$report_id]);

	if (!sizeof($report_data)) {
		return false;
	}

	// get the owner of this report
	$report_data['owner'] = owner($report_id);

	// load measurand configurations
	$tmps = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_measurands
		WHERE template_id = ?',
		[$report_data['template_id']]);

	foreach ($tmps as $tmp) {
		$report_measurands[$tmp['id']] = $tmp;
	}

	// load configurations of variables
	$report_variables = db_fetch_assoc_prepared('SELECT a.id, a.name,
		a.description, b.value, a.min_value, a.max_value
		FROM plugin_reportit_variables AS a
		INNER JOIN plugin_reportit_rvars AS b
		ON a.id = b.variable_id
		AND b.report_id = ?
		WHERE a.template_id = ?',
		[$report_id, $report_data['template_id']]);

	// load data source alias
	$report_ds_alias = db_custom_fetch_assoc('SELECT data_source_name, data_source_alias
		FROM plugin_reportit_data_source_items
		WHERE template_id = ' . $report_data['template_id'], 'data_source_name', false, false);

	switch ($type) {
		case 'view':
		case 'export':
			$sql = "SELECT c.name_cache, b.*, a.*
				FROM plugin_reportit_results_$report_id AS a
				INNER JOIN plugin_reportit_data_items AS b
				ON a.id = b.id
				AND b.report_id = $report_id
				INNER JOIN data_template_data AS c
				ON c.local_data_id = a.id
				$sql_where";

			break;
		case 'graidle':
			$sql = $sql_where;

			break;
		case 'graph':
			return [
				'report_data'       => $report_data,
				'report_measurands' => $report_measurands
			];

			break;
	}

	$report_results = db_fetch_assoc($sql);

	// build data package for return
	$data = [
		'report_data'       => $report_data,
		'report_results'    => $report_results,
		'report_measurands' => $report_measurands,
		'report_variables'  => $report_variables,
		'report_ds_alias'   => $report_ds_alias
	];

	return $data;
}

/**
 * Gathers an archived report run's full display/export data package
 * (mirroring get_prepared_report_data()'s structure), reading from the
 * report's cache tables instead of its live results table. Called from
 * show_report(), export(), and show_graph_overview() when viewing/
 * exporting a specific archived report run.
 *
 * @param string $cache_id  The archive's cache id (typically
 *                          '{report_id}_{archive_id}').
 * @param string $type      The data-package mode: 'view', 'export',
 *                          'graidle', or 'graph' (see
 *                          get_prepared_report_data()).
 * @param string $sql_where Additional SQL WHERE clause (for
 *                          view/export) or the full query (for
 *                          'graidle') to apply to the results query.
 *
 * @return array|false The assembled data package, or false if the
 *                     archived report doesn't exist.
 */
function get_prepared_archive_data($cache_id, $type, $sql_where = '') {
	$report_measurands = [];
	$report_variables  = [];

	// load report configuration
	$report_data = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_cache_reports
		WHERE cache_id = ?',
		[$cache_id]);

	// save serialized data source alias separately
	if (isset($report_data['data_template_alias'])) {
		$report_ds_alias = json_decode(base64_decode($report_data['data_template_alias'], true), true);
		unset($report_data['data_template_alias']);
	}

	// load configured measurands
	$tmps = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_cache_measurands
		WHERE cache_id = ?',
		[$cache_id]);

	if (cacti_sizeof($tmps)) {
		foreach ($tmps as $tmp) {
			$report_measurands[$tmp['id']] = $tmp;
		}
	}

	// load configured variables
	$report_variables = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_cache_variables
		WHERE cache_id = ?',
		[$cache_id]);

	switch ($type) {
		case 'export':
			$sql = 'SELECT * FROM plugin_reportit_tmp_' . $cache_id . ' as a ' . $sql_where;

			break;
		case 'graidle':
			$sql = $sql_where;

			break;
		case 'graph':
			return [
				'report_data'       => $report_data,
				'report_measurands' => $report_measurands
			];

			break;
		case 'view':
			$sql = 'SELECT * FROM plugin_reportit_tmp_' . $cache_id . ' AS a ' . $sql_where;

			break;
	}

	$report_results = db_fetch_assoc($sql);

	// build data package for return
	$data = [
		'report_data'       => $report_data,
		'report_results'    => $report_results,
		'report_measurands' => $report_measurands,
		'report_variables'  => $report_variables,
		'report_ds_alias'   => $report_ds_alias
	];

	return $data;
}

/**
 * db_custom_fetch_assoc()
 *
 * @param  string $sql   contains the SQL call
 * @param  string $index contains the name of the column which should be used as index
 *                       if false (default) the index will numerical beginning from zero.
 * @param  binary $multi save the columns multidimensional. if false then you will get only one result per index
 * @param  binary $assoc if true (default) then save the $values associated ($key => $value) into the index array
 *                       requires that $multi is true.
 * @return binary returns an array or false if the SQL command failed
 */
function db_custom_fetch_assoc($sql, $index = false, $multi = true, $assoc = true) {
	$raw_data = [];
	$srt_data = [];

	$raw_data = db_fetch_assoc($sql);

	if (cacti_sizeof($raw_data)) {
		foreach ($raw_data as $row_key => $row) {
			if ($index !== false && !array_key_exists($index, $row)) {
				return false;
			}

			$index_key = ($index === false) ? $row_key : $row[$index];

			foreach ($row as $key => $value) {
				if ($key != $index) {
					if ($multi) {
						if ($assoc) {
							$srt_data[$index_key][$key] = $value;
						} else {
							$srt_data[$index_key][] = $value;
						}
					} else {
						$srt_data[$index_key] = $value;
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
 * @param string $sql contains the SQL call
 * @return
 */
function db_custom_fetch_flat_array($sql) {
	$raw_data = [];
	$srt_data = [];

	$raw_data = db_fetch_assoc($sql);

	if (cacti_sizeof($raw_data) > 0) {
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
 * @param string $sql       contains the SQL call
 * @param string $delimiter character for separating the columns. Default is ","
 * @return
 */
function db_custom_fetch_flat_string($sql, $delimiter = ',') {
	$raw_data = [];
	$srt_data = '';

	$raw_data = db_fetch_assoc($sql);

	if (cacti_sizeof($raw_data)) {
		foreach ($raw_data as $row) {
			foreach ($row as $value) {
				$srt_data .= $value . $delimiter;
			}
		}

		return substr($srt_data, 0, -strlen($delimiter));
	} else {
		return false;
	}
}

/**
 * Resolves a named preset timespan (e.g. 'Today', 'Last 7 Days', 'Last
 * Week (Sun - Sat)') plus a presentation mode into concrete start/end
 * dates relative to the current date, optionally computed in GMT.
 * Called from report scheduling/display code to translate a report's
 * configured preset timespan into actual dates.
 *
 * @param string $preset_timespan The named preset timespan to resolve.
 * @param mixed  $present         The presentation mode affecting how
 *                                the resolved range is further
 *                                adjusted/displayed.
 * @param bool   $enable_tmz      Whether to compute dates in GMT rather
 *                                than local server time.
 *
 * @return array The resolved timespan (start/end date components and
 *              related values).
 */
function rp_get_timespan($preset_timespan, $present, $enable_tmz = false) {
	// Set preconditions
	$today          = ($enable_tmz) ? gmdate('Y-m-d') : date('Y-m-d');
	[$ys, $ms, $ds] = explode('-', $today);
	[$ye, $me, $de] = explode('-', $today);

	// Set report start date
	switch ($preset_timespan) {
		case 'Today':
			break;
		case 'Last 1 Day':
			$ds -= 1;
			$de -= 1;

			break;
		case 'Last 2 Days':
			$ds -= 2;
			$de -= 1;

			break;
		case 'Last 3 Days':
			$ds -= 3;
			$de -= 1;

			break;
		case 'Last 4 Days':
			$ds -= 4;
			$de -= 1;

			break;
		case 'Last 5 Days':
			$ds -= 5;
			$de -= 1;

			break;
		case 'Last 6 Days':
			$ds -= 6;
			$de -= 1;

			break;
		case 'Last 7 Days':
			$ds -= 7;
			$de -= 1;

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
			$ds -= 14;
			$de -= 1;

			break;
		case 'Last 21 Days':
			$ds -= 21;
			$de -= 1;

			break;
		case 'Last 28 Days':
			$ds -= 28;
			$de -= 1;

			break;
		case 'Current Month':
			$de = ($ds == 1) ? $ds : $de - 1;
			$ds = 1;

			break;
		case 'Last Month':
			$ms -= 1;
			$ds = 1;
			$de = 0;

			break;
		case 'Last 2 Months':
			$ms -= 2;
			$ds = 1;
			$de = 0;

			break;
		case 'Last 3 Months':
			$ms -= 3;
			$ds = 1;
			$de = 0;

			break;
		case 'Last 4 Months':
			$ms -= 4;
			$ds = 1;
			$de = 0;

			break;
		case 'Last 5 Months':
			$ms -= 5;
			$ds = 1;
			$de = 0;

			break;
		case 'Last 6 Months':
			$ms -= 6;
			$ds = 1;
			$de = 0;

			break;
		case 'Current Year':
			$de = ($ds == 1 && $ms == 1) ? $ds : $de - 1;
			$ms = 1;
			$ds = 1;

			break;
		case 'Last Year':
			$ms = 1;
			$ds = 1;
			$ys -= 1;
			$me = 1;
			$de = 0;

			break;
		case 'Last 2 Years':
			$ms = 1;
			$ds = 1;
			$ys -= 2;
			$me = 1;
			$de = 0;

			break;
		default:
			break;
	}

	$dates = [];

	$dates['start_date'] = ($enable_tmz) ? gmdate('Y-m-d', gmmktime(0,0,0, $ms, $ds, $ys)) : date('Y-m-d', mktime(0,0,0, $ms, $ds, $ys));

	if ($present) {
		$dates['end_date'] = $today;
	} else {
		$dates['end_date'] = ($enable_tmz) ? gmdate('Y-m-d', gmmktime(0,0,0, $me, $de, $ye)) : date('Y-m-d', mktime(0,0,0, $me, $de, $ye));
	}

	return $dates;
}

/**
 * Formats a numeric value with an appropriate metric/binary/IEC unit
 * prefix (K/M/G/T/P/E/Z/Y) scaled to keep the displayed magnitude
 * reasonable, rounded to the requested precision. Called when
 * rendering measurand values in report table/graph views.
 *
 * @param float  $value          The value to format.
 * @param string $prefixes       Which prefix table to use ('decimal',
 *                               'binary', or 'IEC').
 * @param mixed  $data_type      The measurand's data type, affecting
 *                               formatting behavior.
 * @param int    $data_precision The number of decimal places to round
 *                               the displayed value to.
 *
 * @return string The formatted value with its unit prefix suffix.
 *
 * @global float $threshold Cached scaling threshold, initialized once
 *                         and reused across calls.
 * @global array $binary    Cached binary (1024-based) prefix magnitude
 *                         table, initialized once and reused across
 *                         calls.
 * @global array $decimal   Cached decimal (1000-based) prefix magnitude
 *                         table, initialized once and reused across
 *                         calls.
 * @global array $IEC       Cached IEC prefix label table, initialized
 *                         once and reused across calls.
 */
function get_unit($value, $prefixes, $data_type, $data_precision) {
	global $threshold, $binary, $decimal, $IEC;

	if (!$threshold) {
		$threshold 	 = 0.5;

		$decimal = [
			'Y' => pow(1000,8),
			'Z' => pow(1000,7),
			'E' => pow(1000,6),
			'P' => pow(1000,5),
			'T' => pow(1000,4),
			'G' => pow(1000,3),
			'M' => pow(1000,2),
			'K' => 1000
		];

		$binary = [
			'Y' => pow(1024,8),
			'Z' => pow(1024,7),
			'E' => pow(1024,6),
			'P' => pow(1024,5),
			'T' => pow(1024,4),
			'G' => pow(1024,3),
			'M' => pow(1024,2),
			'K' => 1024
		];

		$IEC = read_config_option('reportit_use_IEC');
	}

	$type_specifiers = ['b', 'f', 'd', 'u', 'x', 'X', 'o', 'e'];

	$data_type = $type_specifiers[$data_type];

	// use precision for type FLOAT and SCIENTIFIC NOTIFICATION only
	if (is_numeric($data_precision) && in_array($data_type, ['f', 'e'], true)) {
		$data_precision = '.' . $data_precision;
	} else {
		$data_precision = '';
	}

	if ($value === 0) {
		return 0;
	}

	if ($value == null) {
		return 'NA';
	}

	if ($prefixes == 0) {
		return sprintf('%' . $data_precision . $data_type, $value);
	}

	if ($prefixes == 0 || $value == 0) {
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
		case ($absolute >= $pre['Y']): // YOTTA
			$value /= $pre['Y'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " Y$i");

			break;
		case ($absolute >= $pre['Y'] * $threshold):
			$value /= $pre['Y'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " Y$i");

			break;
		case ($absolute >= $pre['Z']): // ZETTA
			$value /= $pre['Z'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " Z$i");

			break;
		case ($absolute >= $pre['Z'] * $threshold):
			$value /= $pre['Z'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " Z$i");

			break;
		case ($absolute >= $pre['E']): // EXA
			$value /= $pre['E'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " E$i");

			break;
		case ($absolute >= $pre['E'] * $threshold):
			$value /= $pre['E'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " E$i");

			break;
		case ($absolute >= $pre['P']): // PETA
			$value /= $pre['P'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " P$i");

			break;
		case ($absolute >= $pre['P'] * $threshold):
			$value /= $pre['P'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " P$i");

			break;
		case ($absolute >= $pre['T']): // TERA
			$value /= $pre['T'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " T$i");

			break;
		case ($absolute >= $pre['T'] * $threshold):
			$value /= $pre['T'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " T$i");

			break;
		case ($absolute >= $pre['G']): // GIGA
			$value /= $pre['G'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " G$i");

			break;
		case ($absolute >= $pre['G'] * $threshold):
			$value /= $pre['G'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " G$i");

			break;
		case ($absolute >= $pre['M']): // MEGA
			$value /= $pre['M'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " M$i");

			break;
		case ($absolute >= $pre['M'] * $threshold):
			$value /= $pre['M'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " M$i");

			break;
		case ($absolute >= $pre['K']): // KILO
			$value /= $pre['K'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " $k$i");

			break;
		case ($absolute >= $pre['K'] * $threshold):
			$value /= $pre['K'];

			return (sprintf('%' . $data_precision . $data_type, $value) . " $k$i");

			break;
		default:
			return sprintf('%' . $data_precision . $data_type, $value);

			break;
	}
}

/**
 * Bulk-inserts a default value entry for a newly created template
 * variable into every existing report based on that template. Called
 * when a new variable is added to a template that already has reports,
 * so those reports gain a usable default for the new variable.
 *
 * @param int   $variable_id The newly created variable's id.
 * @param int   $template_id The template id the variable belongs to.
 * @param mixed $default     The variable's default value to seed each
 *                           report with.
 *
 * @return void
 */
function create_rvars_entries($variable_id, $template_id, $default) {
	$ids = db_fetch_assoc_prepared('SELECT id
		FROM plugin_reportit_reports
		WHERE template_id = ?',
		[$template_id]);

	if (cacti_sizeof($ids)) {
		$list = '';

		$params = [];

		foreach ($ids as $id) {
			$list .= '(?, ?, ?, ?),';

			$params[] = $template_id;
			$params[] = $id['id'];
			$params[] = $variable_id;
			$params[] = $default;
		}

		// Remove last comma
		$list = rtrim($list, ',');

		db_execute_prepared("INSERT INTO plugin_reportit_rvars
			(template_id, report_id, variable_id, value)
			VALUES $list",
			$params);
	}
}

/**
 * get_possible_rra_names()
 * returns an array with all possible names of the Round Robbin Archives for this report template
 * @param  int $template_id contains the id of the current report template
 * @return an  array with all possible round robin archives
 */
function get_possible_rra_names($template_id) {
	// Get all possible names of the RRAs for this type of template
	$names = [];
	$array = [];

	$names = db_fetch_assoc_prepared('SELECT b.data_source_name
		FROM plugin_reportit_data_source_items AS a
		LEFT JOIN data_template_rrd AS b
		ON a.id = b.id
		WHERE a.template_id = ?
		AND a.id != 0',
		[$template_id]);

	foreach ($names as $name) {
		$array[] = $name['data_source_name'];
	}

	return $array;
}

/**
 * get_interim_results()
 *
 * @param  int     $measurand_id contains the id of the current measurand
 * @param  int     $template_id  contains the id of the template the measurand belongs to
 * @param  boolean $ln           returns a line break after every interim result
 * @return array   with the syntax of possible interim results
 */
function get_interim_results($measurand_id, $template_id, $ln = false) {
	$array           = [];
	$names           = [];
	$interim_results = [];

	$names = get_possible_rra_names($template_id);
	$sql   = 'SELECT abbreviation, spanned
		FROM plugin_reportit_measurands
		WHERE template_id = ?';

	$params[] = $template_id;

	if ($measurand_id != 0) {
		$sql .= ' AND id < ?';
		$params[] = $measurand_id;
	}

	$array = db_fetch_assoc_prepared($sql, $params);

	if (cacti_sizeof($array)) {
		foreach ($array as $interim_result) {
			if ($interim_result['spanned'] == '') {
				foreach ($names as $name) {
					$interim_results[] = $interim_result['abbreviation'] . ':' . $name;
				}
			}

			if ($ln) {
				$interim_result['abbreviation'] .= '<br>';
			}
			$interim_results[] = $interim_result['abbreviation'];
		}
	}

	return $interim_results;
}

/**
 * Builds the list of variable name tokens usable in a template's
 * calculation formulas: the template's own defined variables, plus
 * built-in tokens like 'maxValue' when the underlying data template
 * declares a valid RRD maximum. Called from the measurand editor and
 * validate_calc_formula() to determine which variable tokens a formula
 * may legally reference.
 *
 * @param int $template_id The template id to resolve variable names
 *                        for.
 *
 * @return array The list of valid variable name tokens for the
 *              template.
 *
 * @global array $calc_var_names Reserved/declared for parity with other
 *                              functions in this file; not used
 *                              directly here.
 */
function get_possible_variables($template_id) {
	global $calc_var_names;

	// Fetch all variables which has been defined for this template
	$names = [];
	$array = [];

	// Check whether maxValue is valid
	$maximum = db_fetch_cell_prepared('SELECT DISTINCT a.rrd_maximum
		FROM data_template_rrd as a
		INNER JOIN plugin_reportit_templates as b
		ON a.data_template_id = b.data_template_id
		AND b.id = ?
		WHERE a.local_data_id = 0',
		[$template_id]);

	if (!is_numeric($maximum) || $maximum == 0) {
		unset($calc_var_names[0]);
	}

	$names = db_fetch_assoc_prepared('SELECT abbreviation
		FROM plugin_reportit_variables
		WHERE template_id = ?',
		[$template_id]);

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
 * @param  int   $template_id - the data template id
 * @return array - array of data query cache variables
 */
function get_possible_data_query_variables($template_id) {
	// any data query associated with this data template?
	$available_data_queries = db_fetch_assoc_prepared('SELECT DISTINCT dl.snmp_query_id
		FROM data_local AS dl
		INNER JOIN plugin_reportit_templates AS rt
		ON dl.data_template_id = rt.data_template_id
		WHERE rt.id = ?',
		[$template_id]);

	// in case there is no data query, have $names initialized
	$names = [];

	if (cacti_sizeof($available_data_queries)) {
		$data_query_list = 'snmp_query_id IN (';

		foreach ($available_data_queries as $data_queries) {
			$data_query_list .= $data_queries['snmp_query_id'] . ',';
		}

		$data_query_list = substr($data_query_list, 0, -1) . ')';

		// get all host_snmp_cache variables for those list of data queries
		$names = db_fetch_assoc("SELECT DISTINCT field_name
			FROM host_snmp_cache
			WHERE $data_query_list");
	}

	$array = [];

	if (cacti_sizeof($names)) {
		foreach ($names as $name) {
			$array[] = $name['field_name'];
		}
	}

	debug($array, 'Array of possible Data Query Variables');

	return $array;
}

/**
 * Checks whether a template is locked. Called from run_report()/
 * runtime() before running a report, to abort if its template is
 * locked.
 *
 * @param int $template_id The template id to check.
 *
 * @return int '1' if locked, otherwise a falsy/empty value.
 */
function get_template_status($template_id) {
	// Returns '1' if the template has been locked.
	$status = db_fetch_cell_prepared('SELECT locked
		FROM plugin_reportit_templates
		WHERE id = ?',
		[$template_id]);

	return $status;
}

/**
 * Marks a report as currently running (or another state), recording
 * the state-change timestamp. Called from runtime() at the start of a
 * report run.
 *
 * @param int $report_id The report id to update.
 * @param int $status    The state code to set (default 1 = running).
 *
 * @return void
 */
function in_process($report_id, $status = 1) {
	$now = date('Y-m-d H:i:s');

	db_execute_prepared('UPDATE plugin_reportit_reports
		SET state = ?, last_state = ?
		WHERE id = ?',
		[$status, $now, $report_id]);
}

/**
 * Reads a report's current state code. Called wherever a report's
 * running/idle status needs to be checked (e.g. before auto-locking its
 * template).
 *
 * @param int $report_id The report id to check.
 *
 * @return int The report's current state code.
 */
function stat_process($report_id) {
	$sql = 'SELECT state FROM plugin_reportit_reports WHERE id = ?';

	return db_fetch_cell_prepared($sql, [$report_id]);
}

/**
 * Resolves Cacti's configured date display format/separator into a PHP
 * date() format string, optionally including a time component. Called
 * when rendering report dates/timestamps to match the user's configured
 * date display preferences.
 *
 * @param bool $no_time Whether to omit the time portion from the
 *                      returned format string.
 *
 * @return string The resolved PHP date() format string.
 */
function config_date_format($no_time = true) {
	$date_fmt = read_graph_config_option('default_date_format');
	$datechar = read_graph_config_option('default_datechar');

	if (!isset($date_fmt)) {
		return ('Y-m-d H:i:s');
	}

	if ($datechar == GDC_HYPHEN) {
		$datechar = '-';
	} else {
		$datechar = '/';
	}

	switch ($date_fmt) {
		case GD_MO_D_Y:
			$dd = ('m' . $datechar . 'd' . $datechar . 'Y');

			break;
		case GD_MN_D_Y:
			$dd = ('M' . $datechar . 'd' . $datechar . 'Y');

			break;
		case GD_D_MO_Y:
			$dd = ('d' . $datechar . 'm' . $datechar . 'Y');

			break;
		case GD_D_MN_Y:
			$dd = ('d' . $datechar . 'M' . $datechar . 'Y');

			break;
		case GD_Y_MO_D:
			$dd = ('Y' . $datechar . 'm' . $datechar . 'd');

			break;
		case GD_Y_MN_D:
			$dd = ('Y' . $datechar . 'M' . $datechar . 'd');

			break;
		default:
			return ('Y-m-d H:i:s');
	}

	if (!$no_time) {
		return ($dd . ' -- H:i:s');
	} else {
		return $dd;
	}
}

// New functions
/**
 * Prints a debug dump of a value (array or scalar) with an optional
 * section header and inline label, only when the REPORTIT_DEBUG
 * constant is defined. Called throughout runtime()/transform() and
 * other report-generation code to trace intermediate calculation
 * state.
 *
 * @param mixed  $value Reference, the value to dump.
 * @param string $msg   An optional section header printed before the
 *                      value.
 * @param string $fmsg  An optional inline label printed alongside the
 *                      value.
 *
 * @return void
 */
function debug(&$value, $msg = '', $fmsg = '') {
	if (!defined('REPORTIT_DEBUG')) {
		return;
	}

	if ($msg != '') {
		print "\n\t\t******* $msg *******\n";
	}

	if (is_array($value)) {
		if ($fmsg == '') {
			print_r($value);
			print "\n";
		} else {
			print "\t\t$fmsg: ";
			print_r($value);
		}
	} else {
		if ($fmsg != '') {
			print "\t\t$fmsg: \t$value\n";
		} else {
			print "\t\t$value\n";
		}
	}
}

/**
 * Reads a single column value from a report's row. Called wherever a
 * specific report field needs to be fetched directly.
 *
 * Note: due to using a single-quoted SQL string, the `$column`
 * placeholder is not interpolated by PHP; this always selects a
 * literal column named `$column` rather than the caller's requested
 * column.
 *
 * @param int    $report_id The report id to read from.
 * @param string $column    The intended column name to read (not
 *                          actually substituted into the query - see
 *                          note above).
 *
 * @return mixed The queried cell value.
 */
function get_report_setting($report_id, $column) {
	$sql = 'SELECT $column FROM plugin_reportit_reports WHERE id = ?';

	return db_fetch_cell_prepared($sql, [$report_id]);
}

/**
 * Reads a per-user graph-scoped Cacti setting, falling back to its
 * system default if the user has no override stored. Called wherever a
 * graph-related user preference (e.g. row limits, view filter
 * behavior) needs to be resolved for a specific user.
 *
 * @param string $config_name The settings_graphs option name to read.
 * @param int    $user_id     The user id to read the setting for.
 *
 * @return mixed The user's stored value, or the system default if none
 *              is stored.
 */
function get_graph_config_option($config_name, $user_id) {
	$sql = 'SELECT value FROM settings_graphs WHERE name = ? AND user_id = ?';

	$db_setting = db_fetch_row_prepared($sql, [$config_name, $user_id]);

	if (isset($db_setting['value'])) {
		return $db_setting['value'];
	} else {
		return read_default_graph_config_option($config_name);
	}
}

/**
 * Automatically rescales a series of values to a human-friendly
 * magnitude (choosing a power-of-1000/1024 divisor based on the
 * highest absolute value), rewriting the values in place and returning
 * the chosen scale exponent (for building an axis-unit label). Called
 * when preparing graph data series for display.
 *
 * @param array $values   Reference, the values to rescale in place.
 * @param int   $rounding The rounding mode (2 selects a 1000-based
 *                        scale, otherwise 1024-based).
 * @param string $order   'DESC' to use the first value as the
 *                        reference highest value, otherwise the last.
 *
 * @return int The chosen scale exponent (0 for no scaling), or 0 if all
 *            values are zero.
 */
function auto_rounding(&$values, $rounding, $order) {
	$threshold = 0.5;
	$base      = ($rounding == 2) ? 1000 : 1024;

	$highest = ($order == 'DESC') ? reset($values) : end($values);

	if (reset($values) == 0 && end($values) == 0) {
		return 0;
	}

	if ($highest < 0) {
		$highest *= (-1);
	}

	$x = 0;

	for ($exp = 1; $x < $highest; $exp++) {
		$x = pow($base, $exp);

		if ($x * $threshold < $highest) {
			continue;
		} else {
			break;
		}
	}

	// workaround to avoid issues Graidle has with scaling the Y-Axis if highest values is under 1
	if ($highest / pow($base, $exp - 1) < 1) {
		$exp--;
	}

	$devisor = pow($base, $exp - 1);

	foreach ($values as $key => $value) {
		$values[$key] = sprintf('%01.2f', ($value /= $devisor));
	}

	return $exp - 1;
}

/**
 * Loads an external/vendored library on demand by name, currently only
 * the vendored PclZip library (defining its required temp-directory
 * constant if not already set). Called from update_xml_archive() before
 * using PclZip to add/rotate report archives.
 *
 * @param string $name The library to load: 'pclzip', 'graidle', or
 *                     'cleanXML' (the latter two are currently no-ops).
 *
 * @return void
 */
function load_external_libs($name) {
	switch ($name) {
		case 'pclzip':
			if (!defined('PCLZIP_TEMPORARY_DIR')) {
				define('PCLZIP_TEMPORARY_DIR', REPORTIT_TMP_FD);
			}

			require_once(REPORTIT_BASE_PATH . '/include/vendor/pclzip/pclzip.lib.php');

			break;
		case 'graidle':
			break;
		case 'cleanXML':
			break;
	}
}

/**
 * Strips the trailing character from a string in place, typically used
 * to remove a trailing separator before using the string in a SQL
 * clause. Called wherever a trailing comma/delimiter needs trimming
 * before building a SQL fragment.
 *
 * @param string $str Reference, the string to trim in place.
 *
 * @return void
 */
function clean_for_sql(&$str) {
	$str = substr($str, 0, strlen($str) - 1);
}

// Archive Functions
/**
 * PclZip 'rename' event callback that names an archived report's XML
 * entry using its file modification timestamp. Registered as a PclZip
 * listener/callback during archive operations.
 *
 * @param string $p_event  The PclZip event name (unused).
 * @param array  $p_header Reference, the archive entry's header,
 *                         updated with 'stored_filename'.
 *
 * @return int Always 1 (PclZip success code).
 */
function rename_xml_file($p_event, &$p_header) {
	$p_header['stored_filename'] = $p_header['mtime'] . '.xml';

	return 1;
}

/**
 * Builds a JSON representation of a report's current data, mapping its
 * data source aliases into the legacy 'data_template_alias' field name
 * for compatibility. Called when a report's data needs to be archived/
 * cached in JSON form.
 *
 * @param int $report_id The report id to build a JSON archive for.
 *
 * @return string The JSON-encoded report data.
 */
function prepare_json_archive($report_id) {
	// load report data
	$data = get_prepared_report_data($report_id, 'view');

	// transform the data source aliases to the old style
	$data['report_data']['data_template_alias'] = $data['report_ds_alias'];

	return json_encode($data);
}

/**
 * Builds an XML snapshot of a report's current settings/measurands/
 * data items/variables, writes it to a temp file, and adds it to the
 * report's ZIP archive (via the vendored PclZip library), rotating out
 * old archive entries beyond the configured maximum count. Called from
 * autoexport()-style archival code / the report run flow when
 * auto-archiving is enabled for a report.
 *
 * @param int $report_id The report id to archive.
 *
 * @return void This function calls die() on a PclZip archive error.
 */
function update_xml_archive($report_id) {
	$arc_path  = read_config_option('reportit_arc_folder');
	$arc_path .= (substr($arc_path, -1) == '/') ? '' : '/';
	$tmp_path  = REPORTIT_TMP_FD;
	$arc_file  = (($arc_path == '') ? REPORTIT_ARC_FD : $arc_path) . $report_id . '.zip';

	// maximum number of files the archive should contain
	$max = get_report_setting($report_id, 'autoarchive');

	// load report data
	$data = get_prepared_report_data($report_id, 'view');

	// transform the data source aliases to the old style
	$data['report_data']['data_template_alias'] = serialize($data['report_ds_alias']);

	// use an output puffer for flushing
	ob_start();
//	print '<&#63;xml version="1.0" encoding="UTF-8"&#63;>' . PHP_EOL;
	print '<cacti>' . PHP_EOL . '<report>' . PHP_EOL . '<settings>' . PHP_EOL;

	foreach ($data['report_data'] as $key => $value) {
		print "<$key>" . html_escape($value, ENT_NOQUOTES) . "</$key>" . PHP_EOL;
	}
	print '</settings>' . PHP_EOL . '<measurands>' . PHP_EOL;

	foreach ($data['report_measurands'] as $measurand) {
		print '<measurand>' . PHP_EOL;

		foreach ($measurand as $key => $value) {
			print "<$key>" . html_escape($value, ENT_NOQUOTES) . "</$key>" . PHP_EOL;
		}
		print '</measurand>' . PHP_EOL;
	}
	print '</measurands>' . PHP_EOL . '<data_items>' . PHP_EOL;

	foreach ($data['report_results'] as $results) {
		print '<item>' . PHP_EOL;

		foreach ($results as $key => $value) {
			print "<_di__$key>" . html_escape($value, ENT_NOQUOTES) . "</_di__$key>" . PHP_EOL;
		}
		print '</item>' . PHP_EOL;
	}
	print '</data_items>' . PHP_EOL . '<variables>' . PHP_EOL;

	foreach ($data['report_variables'] as $variable) {
		print '<variable>' . PHP_EOL;

		foreach ($variable as $key => $value) {
			print "<$key>" . html_escape($value, ENT_NOQUOTES) . "</$key>" . PHP_EOL;
		}
		print '</variable>' . PHP_EOL;
	}

	print '</variables>' . PHP_EOL . '</report>' . PHP_EOL . '</cacti>' . PHP_EOL;
	$content = ob_get_clean();
	$content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');

	// create a tempary file and save XML output
	$cfg        = $data['report_data'];
	$tmpfile    = REPORTIT_TMP_FD . strtotime($cfg['start_date']) . '_' . strtotime($cfg['end_date']) . '_' . time() . '.xml';
	$filehandle = fopen($tmpfile, 'w');
	fwrite($filehandle, $content);
	fclose($filehandle);

	// load zip file support
	load_external_libs('pclzip');

	// set handle for archiving
	$archive = new PclZip($arc_file);

	// use file rotation
	if (($stat = $archive->properties()) != 0) {
		$cnt = ($max == 0) ? 0 : $stat['nb'];

		if ($cnt > $max + 1) {
			$end = $cnt - $max - 1;
			$archive->delete(PCLZIP_OPT_BY_INDEX, '0-' . $end);
		} elseif ($cnt == $max + 1) {
			$archive->delete(PCLZIP_OPT_BY_INDEX, '0');
		}
	}

	// add XML output to the archive
	$v_list = $archive->add($tmpfile, PCLZIP_OPT_REMOVE_ALL_PATH);

	if ($v_list == 0) {
		die('Error : ' . $archive->errorInfo(true));
	}

	// change mode
	chmod($arc_file, 0644);

	// clean up TMP
	unlink($tmpfile);
}

/**
 * Loads a single archived report run's XML entry from its ZIP archive
 * (by modification time), parses it, and populates the plugin's
 * temporary cache tables (cache_reports/cache_measurands/
 * cache_variables and a dedicated per-archive results table) so it can
 * be displayed like a live report. Skips work if already cached.
 * Called from show_report()/export()/show_graph_overview() when
 * viewing/exporting an archived report run for the first time in a
 * session.
 *
 * @param int $report_id The report id whose archive should be cached.
 * @param int $mtime     The archived entry's modification timestamp
 *                       identifying which archived run to load.
 *
 * @return void This function calls die_html_custom_error() if the
 *             requested archive entry isn't found.
 */
function cache_xml_file($report_id, $mtime) {
	$cache_id   = $report_id . '_' . $mtime;
	$columns    = '';
	$values     = '';
	$cols       = [];
	$index      = false;

	$arc_path   = read_config_option('reportit_arc_folder');
	$arc_path .= (substr($arc_path, -1) == '/') ? '' : '/';
	$arc_file   = (($arc_path == '') ? REPORTIT_ARC_FD : $arc_path) . $report_id . '.zip';

	// check if cache is up to date
	if (db_table_exists('plugin_reportit_tmp_' . $cache_id)) {
		return;
	}

	// load zip file support
	load_external_libs('pclzip');

	// set handle for archiving
	$archive = new PclZip($arc_file);

	// unzip xml archive and load xml file
	$info = $archive->listContent();

	foreach ($info as $key => $array) {
		if ($array['mtime'] == $mtime) {
			$index = $array['index'];

			break;
		}
	}

	if ($index === false) {
		die_html_custom_error('Report not found in archive.', true);
	}

	$data         = $archive->extractByIndex($index, PCLZIP_OPT_EXTRACT_AS_STRING);
	$content      = simplexml_load_string($data[0]['content']);
	$json_content = json_encode($content);
	$archive      = json_decode($json_content, true);

	// transform data and fill up the cache tables
	trans_array2sql($archive['report']['settings'], $columns, $values, $cache_id);
	db_execute("REPLACE INTO plugin_reportit_cache_reports $columns VALUES $values");

	trans_array2sql($archive['report']['measurands'], $columns, $values, $cache_id);
	db_execute("REPLACE INTO plugin_reportit_cache_measurands $columns VALUES $values");

	if (trans_array2sql($archive['report']['variables'], $columns, $values, $cache_id)) {
		db_execute("REPLACE INTO plugin_reportit_cache_variables $columns VALUES $values");
	}

	trans_array2sql($archive['report']['data_items'], $columns, $values, false);
	$columns = str_replace('_di__', '', $columns);

	$cols = explode(',', substr($columns, 2, -1));
	$sql  = 'CREATE TABLE IF NOT EXISTS plugin_reportit_tmp_' . $cache_id . ' (';

	foreach ($cols as $name) {
		if ($name == '`id`') {
			$sql .= $name . ' int(11) NOT NULL DEFAULT 0,';
		} elseif (strpos($name, '__') !== false) {
			$sql .= $name . ' DOUBLE,';
		} else {
			$sql .= $name . " VARCHAR(255) NOT NULL DEFAULT '',";
		}
	}

	$sql .= 'PRIMARY KEY (`id`)) ENGINE=Aria ROW_FORMAT=Page;';

	db_execute($sql);

	db_execute('REPLACE INTO plugin_reportit_tmp_' . $cache_id . " $columns VALUES $values");
}

/**
 * Transforms a parsed XML archive section (settings/measurands/
 * variables/data_items, as a nested array from json_decode) into a SQL
 * INSERT-ready column list and VALUES clause, handling both single-row
 * and multi-row (list-of-records) sections, and base64/JSON-re-encoding
 * a legacy serialized 'data_template_alias' field when present. Called
 * from cache_xml_file() once per archive section being cached.
 *
 * @param array      $array    Reference, the parsed archive section
 *                             data to transform.
 * @param string     $columns  Reference, populated with the resulting
 *                             '(`col`, `col2`, ...)' column list
 *                             fragment.
 * @param string     $values   Reference, populated with the resulting
 *                             VALUES clause fragment (one or more
 *                             tuples).
 * @param string|false $cache_id Optional cache id to prefix each row
 *                             with (for multi-report cache tables); pass
 *                             false to omit.
 *
 * @return bool True if the section was non-empty and transformed,
 *             false if $array was empty/not an array.
 */
function trans_array2sql(&$array, &$columns, &$values, $cache_id = false) {
	$keys       = false;
	$multi      = false;
	$sub_values = '';

	// reset
	$columns = $cache_id ? '`cache_id`' : '';
	$values  = '';

	if (!is_array($array)) {
		return false;
	}

	if (cacti_sizeof($array)) {
		foreach ($array as $key => $value) {
			if ($key == 'data_template_alias') {
				$value = base64_encode(json_encode(unserialize(stripslashes($value), ['allowed_classes' => false])));
			}

			if (is_array($value)) {
				if (isset($value[0])) {
					foreach ($value as $sub_array) {
						$sub_values = '';

						foreach ($sub_array as $sub_key => $sub_value) {
							if (!$keys) {
								$columns .= ", `$sub_key`";
							}

							$sub_values .= (is_array($sub_value) && !$sub_value) ? ", ''" : ', ' . db_qstr($sub_value);
						}

						$keys = true;

						$values .= $cache_id ? ",('$cache_id' $sub_values)" : ',(' . substr($sub_values, 1) . ')';

						$multi = true;
					}
				} else {
					foreach ($value as $sub_key => $sub_value) {
						$columns .= ", `$sub_key`";
						$values .= (is_array($sub_value) && !$sub_value) ? ", ''" : ', ' . db_qstr($sub_value);
					}
				}
			} else {
				$columns .= ", `$key`";
				$values .= ', ' . db_qstr($value);
			}
		}
	} else {
		return false;
	}

	$columns = $cache_id ? "($columns)" : '(' . substr($columns, 1) . ')';
	$values  = ($multi == true) ? substr($values, 1) : (($cache_id !== false) ? "('$cache_id' $values)" : '(' . substr($values, 1) . ')');

	return true;
}

/**
 * Lists the archived runs available for a report by reading its ZIP
 * archive's entry metadata (modification times), formatted per the
 * user's configured date display format. Called from the report
 * viewer's archive-selection dropdown to populate available archived
 * runs.
 *
 * @param int $report_id The report id whose archive listing should be
 *                       read.
 *
 * @return array|false The list of archived entries (e.g. mtime/
 *                     formatted date pairs) found in the report's ZIP
 *                     archive, or false if the archive has no entries.
 */
function info_xml_archive($report_id) {
	$content  = [];
	$arc_path = read_config_option('reportit_arc_folder');
	$arc_file = (($arc_path == '') ? REPORTIT_ARC_FD : $arc_path) . "/$report_id" . '.zip';
	$format   = config_date_format();

	// load zip file support
	load_external_libs('pclzip');

	// set handle for archiving
	$archive = new PclZip($arc_file);

	// collect some informations about this archive
	if (($list = $archive->listContent()) != 0) {
		foreach ($list as $key => $file) {
			if ($file['status'] == 'ok') {
				[$from, $to]             = explode('_', str_replace('.xml', '', $file['filename']));
				$content[$file['mtime']] = date($format, $from) . ' -> ' . date($format, $to);
			}
		}

		// show the newest ones first
		krsort($content, SORT_NUMERIC);

		return $content;
	} else {
		return false;
	}
}

/**
 * Computes the arithmetic mean of an array of numbers. Called wherever
 * a simple average calculation is needed outside the formula
 * calculation engine.
 *
 * @param array $array The values to average.
 *
 * @return float|string The average value, or an empty string if
 *                      $array is empty.
 */
function average($array) {
	if (cacti_sizeof($array) == 0) {
		return '';
	}

	return (array_sum($array) / count($array));
}

/**
 * Recursively HTML-escapes every scalar value in a (possibly up to
 * 3-levels-deep nested) array in place, converting null values to
 * empty strings. Called before rendering untrusted data (e.g. archived
 * report content) as HTML.
 *
 * @param mixed $data Reference, the scalar or nested array to escape in
 *                    place.
 *
 * @return void
 */
function transform_html_escape(&$data) {
	if (!is_array($data)) {
		html_escape($data);
	} else {
		foreach ($data as $key_1 => $value_1) {
			if (is_array($value_1)) {
				foreach ($value_1 as $key_2 => $value_2) {
					if (is_array($value_2)) {
						foreach ($value_2 as $key_3 => $value_3) {
							$value_2[$key_3] = is_null($value_3) ? '' : html_escape($value_3);
						}

						$value_1[$key_2] = $value_2;
					} else {
						$value_1[$key_2] = is_null($value_2) ? '' : html_escape($value_2);
					}
				}

				$data[$key_1] = $value_1;
			} else {
				$data[$key_1] = is_null($value_1) ? '' : html_escape($value_1);
			}
		}
	}
}

/**
 * Converts a PHP ini-style size string (e.g. '128M', '1G') into a plain
 * byte count. Called from get_mem_usage() to parse the configured
 * memory_limit.
 *
 * @param string $val The size string to convert.
 *
 * @return int The equivalent number of bytes.
 */
function return_bytes($val) {
	$val  = trim($val);
	$last = strtolower($val[strlen($val) - 1]);
	$val  = substr($val, 0, -1);

	switch($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

/**
 * Recursively applies htmlspecialchars() to every scalar value in a
 * (possibly up to 3-levels-deep nested) array in place. Called before
 * rendering untrusted data in a context needing htmlspecialchars-style
 * escaping rather than Cacti's html_escape().
 *
 * @param mixed $data Reference, the scalar or nested array to escape in
 *                    place.
 *
 * @return void
 */
function transform_htmlspecialchars(&$data) {
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

/**
 * Reports the current PHP process's memory usage (current and peak, in
 * MB) as a percentage of the configured memory_limit, for inclusion in
 * report run statistics/logging. Called from run_report() when logging
 * a completed report run's stats.
 *
 * @return array Memory usage info: 'limit' (the configured limit, or
 *              'unlimited'), 'current', and 'peak' (formatted
 *              'XMB(Y%)' strings, or 'Undetected' if usage couldn't be
 *              computed).
 */
function get_mem_usage() {
	$memory_system  = return_bytes(ini_get('memory_limit'));
	$memory_used    = round(memory_get_usage() / pow(1024,2),2);
	$memory_peak    = round(memory_get_peak_usage() / pow(1024,2),2);

	if ($memory_system == -1 || $memory_system == '-') {
		$memory_system  = 'unlimited';
		$memory_used .= 'MB';
		$memory_peak .= 'MB';
	} elseif (!is_numeric($memory_used) || !is_numeric($memory_peak) || !is_numeric($memory_system)) {
		$memory_used = 'Undetected';
		$memory_peak = 'Undetected';
	} else {
		$memory_used .= 'MB(' . round($memory_used / $memory_system * 100,2) . '%)';
		$memory_peak .= 'MB(' . round($memory_peak / $memory_system * 100,2) . '%)';
	}

	return ['limit' => $memory_system, 'current' => $memory_used, 'peak' => $memory_peak];
}

/**
 * Renders a SimpleXMLElement as canonical formatted XML text (optionally
 * stripping all whitespace instead), used to build a stable/comparable
 * text representation of an XML subtree. Called from
 * validate_xml_template_section() to compute a template's checksum.
 *
 * @param SimpleXMLElement $xml_object  The XML element to render.
 * @param bool             $keep_spaces Whether to keep formatted
 *                                     whitespace (true) or strip all
 *                                     whitespace (false).
 *
 * @return string The rendered XML text.
 */
function xml_to_string($xml_object, $keep_spaces = true) {
	$dom                     = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput       = true;
	$dom->loadXML($xml_object->asXml());

	$output = $dom->saveXML($dom->firstChild);

	if (!$keep_spaces) {
		$output = preg_replace('/(\v|\s)+/','',$output);
	}

	return $output;
}

/**
 * Recursively converts a SimpleXMLElement into a nested PHP array,
 * keyed by child element name (leaf nodes become their string value,
 * repeated child names become a list). Called from template_wizard()'s
 * import step to convert parsed template XML sections into a usable
 * array structure.
 *
 * @param SimpleXMLElement $xml_object The XML element to convert.
 * @param bool             $indexed    Whether to unwrap a single-item
 *                                    result array down to its lone
 *                                    element.
 * @param bool             $log        Whether to print debug trace
 *                                    output during conversion.
 *
 * @return array|string The converted array, or an empty string if
 *                      $xml_object is falsy.
 */
function xml_to_array($xml_object, $indexed = false, $log = false) {
	static $indent = -1;

	$indent++;
	$indent_char = str_repeat('  ',$indent);
	$out         = [];

	if (!$xml_object) {
		return '';
	}

	$count = 0;

	foreach ($xml_object->children() as $node) {
		if (count($node->children()) == 0) {
			$out[$node->getName()] = strval($node);
		} else {
			$out[$node->getName()][] = xml_to_array($node);
		}

		/*
		$index = $indexed ? $count : $key;
		$is_object = is_object($node) || is_array($node);
		$is_count  = count((array)$node) > 0;
		$count++;

		if ($is_object && !$is_count) {
			$out[$index] = '';
		} elseif ($is_object && $is_count) {
			$out[$index] = xml_to_array($node, false, $log);
		} else {
			if ($log) {
				print "{$indent_char}xml_to_array[$log, $key, $index, $count] = (" . clean_up_lines(var_export($node, true)) . ")\n";
				print "{$indent_char}xml_to_array[$log, is_object: $is_object, is_count: $is_count]\n";
			}
			$out[$index] = (string)$node;
		}
		*/
	}
	/*
	if ($indexed && !array_key_exists(0, $out)) {
		if ($log) {
			print "{$indent_char}xml_to_array[$log]: making array\n";
		}

		$out = [$out];
	}
	*/

	if ($indexed && count($out) == 1) {
		$out = reset($out);
	}

	if ($log) {
		print "{$indent_char}xml_to_array($log, " . count($out) . '): ' . clean_up_lines(var_export($out, true)) . "\n\n";
	}

	$indent--;

	return $out;
}

/**
 * Builds the XML representation of a single report template (settings,
 * variables, measurands, data source items) for inclusion in a
 * template export file. Called from template_export() for each
 * selected template.
 *
 * @param int $template_id The template id to export.
 * @param int $indent      The current XML indentation level (for
 *                        nested pretty-printing).
 *
 * @return string|false The rendered XML fragment for this template, or
 *                      false if the template doesn't exist.
 */
function export_report_template($template_id, $indent = 0) {
	// load template data
	$template_data = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_templates
		WHERE id = ?',
		[$template_id]);

	// exit if no result has been returned
	if ($template_data == false) {
		return false;
	}

	// export folder should not be shared
	$template_data['export_folder'] = '';

	// load definitions of variables
	$variables_data = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_variables
		WHERE template_id = ?
		ORDER BY id',
		[$template_id]);

	// load definitions of measurands
	$measurands_data = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_measurands
		WHERE template_id = ?
		ORDER BY id',
		[$template_id]);

	// load definitions of data source items
	$data_source_items_data = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_data_source_items
		WHERE template_id = ?
		ORDER BY id',
		[$template_id]);

	// add template version and hash the checksum
	$reportit_info = plugin_reportit_version();
	$reportit      = ['version' => $reportit_info['version'], 'type' => 1];

	// use an output puffer for flushing
	$xml_array = [
		'report_template' => [
			'reportit'   => $reportit,
			'settings'   => $template_data,
			'measurands' => [
				'xml_element' => 'measurand',
				'xml_data'    => $measurands_data,
			],
			'variables'  => [
				'xml_element' => 'variable',
				'xml_data'    => $variables_data,
			],
			'data_source_items' => [
				'xml_element' => 'data_source_item',
				'xml_data'    => $data_source_items_data,
			],
		],
	];

	$xml_temp = convert_array2xml($xml_array, $indent);
	$xml_obj  = simplexml_load_string($xml_temp);

	$valid    = true;
	$checksum = '';

	validate_xml_template($xml_obj, $valid, $checksum);

	if (isset($xml_obj->reportit)) {
		$xml_obj->reportit->addChild('hash', md5($checksum));
	}

	return xml_to_string($xml_obj);
}

/**
 * Recursively renders a nested PHP array as indented XML text,
 * supporting a special 'xml_element'/'xml_data' convention for
 * rendering a repeated list of sibling elements under a shared tag
 * name. Called from export_report_template() to build each template
 * export's XML content.
 *
 * @param array|mixed $data   The data to render; non-array values are
 *                           ignored (produce no output at this level).
 * @param int         $indent The current indentation level (tabs).
 *
 * @return string The rendered XML text.
 */
function convert_array2xml($data, $indent = 0) {
	$output = '';

	if ($indent < 0) {
		$indent = 0;
	}

	$pad = str_repeat("\t", $indent);

	if (is_array($data)) {
		if (array_key_exists('xml_element', $data) && array_key_exists('xml_data', $data)) {
			$element = $data['xml_element'];

			foreach ($data['xml_data'] as $xml_data) {
				$output .= "$pad<$element>" . PHP_EOL;
				$output .= convert_array2xml($xml_data, $indent + 1);
				$output .= "$pad</$element>" . PHP_EOL;
			}
		} else {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$output .= "$pad<$key>" . PHP_EOL;
					$output .= convert_array2xml($value, $indent + 1);
					$output .= "$pad</$key>" . PHP_EOL;
				} else {
					$output .= "$pad<$key>" . html_escape($value, ENT_NOQUOTES) . "</$key>" . PHP_EOL;
				}
			}
		}
	}

	return $output;
}

/**
 * Flattens a (possibly one-level nested) associative array into a
 * single concatenated string of key+value pairs, stripping out
 * placeholder tokens of the form '{{N}}'. Used to build a comparable/
 * hashable representation of template data during import matching.
 * Called from import_template()-related comparison logic.
 *
 * @param array $data The data to flatten.
 *
 * @return string The concatenated key+value string.
 */
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

/**
 * Replaces placeholder tokens of the form '{{N}}' in every element of an
 * array with a given replacement (empty string by default), in place.
 * Called from import_template() to clean placeholder tokens left over
 * from template variable substitution.
 *
 * @param array  $array   Reference, the array whose values should be
 *                        cleaned in place.
 * @param string $replace The replacement text for matched placeholder
 *                        tokens.
 *
 * @return void
 */
function clean_xml_waste(&$array, $replace = '') {
	foreach ($array as $key => $value) {
		$array[$key] = preg_replace('/(^[\{]{2}([0-9]*)[\}]{2}$)/', $replace, $value);
	}
}

/**
 * Imports a single parsed report template (from an uploaded template
 * XML document) into the database, associating it with the selected
 * compatible data template and recreating its variables/measurands/
 * data source item rows. Called from template_wizard()'s 'import' step
 * for each confirmed template in the uploaded file.
 *
 * @param object $report_template   The parsed XML template element to
 *                                  import.
 * @param int    $data_template_id  The Cacti data template id the new
 *                                  report template should be based on.
 *
 * @return void
 */
function import_template($report_template, $data_template_id) {
	$values		 = '';
	$columns	 = '';
	$old		    = [];
	$new		    = [];

	// foreach ($xml_data[0] as $report_template) {
	$template_data              = xml_to_array($report_template->{'settings'});
	$template_variables         = xml_to_array($report_template->variables, true);
	$template_measurands        = xml_to_array($report_template->measurands, true);
	$template_data_source_items = xml_to_array($report_template->data_source_items, true);

	$template_data['id']               = 0;
	$template_data['data_template_id'] = $data_template_id;

	clean_xml_waste($template_data);

	$template_id = sql_save($template_data, 'plugin_reportit_templates');

	foreach ($template_variables as $template_variable) {
		$variable                = $template_variable;
		$variable['id']          = 0;
		$variable['template_id'] = $template_id;

		$new_id = sql_save($variable, 'plugin_reportit_variables');
		$old[]  = $variable['abbreviation'];
		$abbr   = 'c' . $new_id . 'v';
		$new[]  = $abbr;

		db_execute_prepared('UPDATE plugin_reportit_variables
			SET abbreviation = ?
			WHERE id = ?',
			[$abbr, $new_id]);
	}

	foreach ($template_measurands as $template_measurand) {
		$measurand                 = $template_measurand;
		$measurand['id']           = 0;
		$measurand['template_id']  = $template_id;
		$measurand['calc_formula'] = str_replace($old,$new, $measurand['calc_formula']);

		sql_save($measurand, 'plugin_reportit_measurands');
	}

	foreach ($template_data_source_items as $template_data_source_item) {
		$ds_item = $template_data_source_item;
		clean_xml_waste($ds_item);

		$ds_item['id'] = db_fetch_cell_prepared('SELECT id
				FROM data_template_rrd
				WHERE local_data_id = 0
				AND data_template_id = ?
				AND data_source_name = ?',
			[get_request_var('data_template'), $ds_item['data_source_name']]);

		$ds_item['template_id'] = $template_id;

		sql_save($ds_item, 'plugin_reportit_data_source_items', ['id', 'template_id'], false);
	}
}
