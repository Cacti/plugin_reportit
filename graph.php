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

/* load standard libraries */
include_once(REPORTIT_BASE_PATH . '/lib/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_online.php');
require_once(REPORTIT_BASE_PATH . '/include/vendor/phpgraphlib/phpgraphlib.php');

/* start with graphing */
create_chart();

function create_chart(){
	global $config, $types, $prefixes;

	/* load presets */
	include_once(REPORTIT_BASE_PATH . '/lib/const_graphs.php');
	/* ================= Input validation ================= */
	input_validate_input_number(get_request_var("id"));
	if (!isset_request_var('source')) exit;
	input_validate_input_key(get_request_var("type"), $types, true);
	/* ==================================================== */

	/* ==================== checkpoint ==================== */
	my_report(get_request_var('id'), true);
	/* ==================================================== */

	/* clean up source string */
	set_request_var('source', sanitize_search_string(get_request_var_request("source")));

	/* load session values */
	$id = (read_graph_config_option('reportit_view_filter') == 'on') ? get_request_var('id') : '';

	load_current_session_value("type", "sess_reportit_show_{$id}_type", read_graph_config_option('reportit_g_default', '-10'));
	load_current_session_value("filter", "sess_reportit_show_{$id}_filter", "");
	load_current_session_value("archive", "sess_reportit_show_{$id}_archive", "-1");
	load_current_session_value("limit", "sess_reportit_show_{$id}_limit", "-2");

	/* load chart type settings */
	$type = ''; //TODO: $types[get_request_var('type')];

	/* form the 'where' clause for our main sql query */
	$affix .= " LIKE '%%" . get_request_var('filter') . "%%'";

	/* load report data */
	$cache_id   = get_request_var('id') . '_' . get_request_var('archive');
	$order      = (get_request_var('limit') < 0)? 'DESC' : 'ASC';
	$limit      = abs(get_request_var('limit'))*5;
	$affix     .= "ORDER BY a." . get_request_var('source') . " $order LIMIT 0, $limit";

	if (get_request_var('archive') == -1) {
		$sql = "SELECT a." . get_request_var('source') . ", c.name_cache
				FROM plugin_reportit_results_" . get_request_var('id') . " AS a
				INNER JOIN data_template_data AS c
				ON c.local_data_id = a.id
				WHERE c.name_cache ". $affix;
		//MBV: cacti_log('REPORTIT: ' . $sql);
		$data = get_prepared_report_data(get_request_var('id'),'graidle', $sql);
		//MBV: cacti_log('REPORTIT: ' . var_export($data, true));
	} else {
		$sql = "SELECT a." . get_request_var('source') . ", c.name_cache
				FROM plugin_reportit_tmp_$cache_id AS a
				WHERE a.name_cache ". $affix;
		//MBV: cacti_log('REPORTIT: ' . $sql);
		$data = get_prepared_archive_data($cache_id, 'graidle', $sql);
	}

	/* create chart title */
	$report_ds_alias    = $data['report_ds_alias'];

	$source = explode("__", get_request_var('source'));
	$ds     = (
				is_array($report_ds_alias)                      &&
				array_key_exists($source[0], $report_ds_alias)  &&
				$report_ds_alias[$source[0]] != ''
			   )
			   ? $report_ds_alias[$source[0]]
			   : $source[0];

	$title  = (get_request_var('limit') < 0)? 'HIGH' : 'LOW';
	$title .= " TOP$limit - $ds - {$data['report_measurands'][$source[1]]['abbreviation']}";
	$rounding = $data['report_measurands'][$source[1]]['rounding'];

	if (count($data['report_results'])>0) {
		$i = 1;
		foreach($data['report_results'] as $result) {
			// Could use $result['name_cache'] for the actual name
			$results[$i] = $result[get_request_var('source')];
			$x_values[] = $i;
			$i++;
		}
		if ($rounding) {
			$exponent = auto_rounding($results, $rounding, $order);
			if (array_key_exists($exponent, $prefixes[$rounding])) {
				$prefix = $prefixes[$rounding][$exponent];
			}
		}
	} else {
		exit;
	}

	/* load default settings */
	//TODO: if (read_config_option('reportit_g_mono')=='on') $chart->setFontMono();
	//TODO: $chart->setFont(read_config_option('reportit_g_afont'));
	//TODO: $chart->setFontBd(read_config_option('reportit_g_tfont'));
	//TODO: $chart->setFontLegend(read_config_option('reportit_g_afont'));

	//TODO: $chart->setAA(read_config_option('reportit_g_quality'));
	//TODO: $chart->setdynFontSize();
	//TODO: if (read_graph_config_option('reportit_g_showgrid') == 'on') $chart->setSecondaryAxis(1,1);

	/* define the size of chart */
	$width  = (read_config_option('reportit_g_mwidth') < read_graph_config_option('reportit_g_width'))
			? read_config_option('reportit_g_mwidth')
			: read_graph_config_option('reportit_g_width');
	if ($width < 100) $width = 1024;

	$height = (read_config_option('reportit_g_mheight') < read_graph_config_option('reportit_g_height'))
			? read_config_option('reportit_g_mheight')
			: read_graph_config_option('reportit_g_height');
	if ($height < 100) $height = 768;

	/* load Graidle */
	$chart = new PHPGraphLib($width, $height);
	$chart->setTitle($title);

	/* define the title for the axis */
	if (isset($type['x_axis'])) {
		$x_title = ($type['x_axis']=='1')
				 ?  "{$data['report_measurands'][$source[1]]['abbreviation']}"
				   ."[$prefix{$data['report_measurands'][$source[1]]['unit']}]"
				 : $type['x_axis'];
		//TODO: $chart->setXtitle($x_title);
	}
	if (isset($type['y_axis'])) {
		$y_title = ($type['y_axis']=='1')
				 ?  "{$data['report_measurands'][$source[1]]['abbreviation']}"
				   ."[$prefix{$data['report_measurands'][$source[1]]['unit']}]"
				 : $type['y_axis'];
		//TODO: $chart->setYtitle($y_title);
	}

	//TODO: if (isset($type['filled'])) $chart->setFilled();
	//TODO: if (isset($type['x_value']))
	//MBV: cacti_log('REPORTIT: ' . var_export($results, true));
	//$chart->setXValues(false);
	$chart->addData($results);

	//TODO: $chart->setMulticolor();
	/* workaround to avoid loops in Graidle with Hbar and Spider charts */
	//if (array_sum($results)== 0 & ($type['name'] == 'hb' | $type['name'] == 's')) return;
	//TODO: $chart->setValue($results, $type['name']);
	//TODO: $chart->setExtLegend(0);
	$chart->createGraph();
	//TODO: $chart->carry();
}

