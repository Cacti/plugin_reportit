<?php
/*
   +-------------------------------------------------------------------------+
   | Copyright (C) 2004-2011 The Cacti Group                                 |
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

/* clean up */
ob_start();

/* Try to support 0.8.7 and lower versions as well*/
if(is_file('../../include/global.php')) include_once('../../include/global.php');
else include_once('../../include/config.php');

/* load standard libraries */
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_online.php');
require_once(REPORTIT_BASE_PATH . '/lib_ext/graidle/graidle.php');

/* ======== Validation ======== */
    safeguard_xss();
/* ============================ */

ob_end_clean();

/* start with graphing */
create_chart();


function create_chart(){
    global $config, $types, $prefixes;

    /* load presets */
    include_once(REPORTIT_BASE_PATH . '/lib_int/const_graphs.php');

    /* ================= Input validation ================= */
        input_validate_input_number(get_request_var("id"));
        if(!isset($_REQUEST["source"])) exit;
        input_validate_input_key(get_request_var("type"), $types, true);
    /* ==================================================== */

    /* ==================== checkpoint ==================== */
        my_report(get_request_var('id'), TRUE);
    /* ==================================================== */

    /* clean up source string */
    $_REQUEST["source"] = sanitize_search_string(get_request_var_request("source"));

    /* load session values */
    $id = (read_graph_config_option('reportit_view_filter') == 'on') ? $_REQUEST['id'] : '';
    load_current_session_value("type", "sess_reportit_show_{$id}_type", read_graph_config_option('reportit_g_default', '-10'));
    load_current_session_value("filter", "sess_reportit_show_{$id}_filter", "");
    load_current_session_value("archive", "sess_reportit_show_{$id}_archive", "-1");
    load_current_session_value("limit", "sess_reportit_show_{$id}_limit", "-2");

    /* load chart type settings */
    $type = $types[$_REQUEST['type']];

    /* form the 'where' clause for our main sql query */
    $affix .= " LIKE '%%" . $_REQUEST["filter"] . "%%'";

    /* load report data */
    $cache_id   = $_REQUEST['id'] . '_' . $_REQUEST['archive'];
    $order      = ($_REQUEST['limit'] < 0)? 'DESC' : 'ASC';
    $limit      = abs($_REQUEST['limit'])*5;
    $affix     .= "ORDER BY a.{$_REQUEST["source"]} $order LIMIT 0, $limit";

    if ($_REQUEST['archive'] == -1) {
        $sql = "SELECT a.{$_REQUEST["source"]}
                FROM reportit_results_{$_REQUEST['id']} AS a
                INNER JOIN data_template_data AS c
                ON c.local_data_id = a.id
                WHERE c.name_cache ". $affix;
        $data = get_prepared_report_data($_REQUEST['id'],'graidle', $sql);
    }else {
        $sql = "SELECT a.{$_REQUEST["source"]}
                FROM reportit_tmp_$cache_id AS a
                WHERE a.name_cache ". $affix;
        $data = get_prepared_archive_data($cache_id, 'graidle', $sql);
    }

    /* create chart title */
    $report_ds_alias    = $data['report_ds_alias'];

    $source = explode("__", $_REQUEST['source']);
    $ds     = (
                is_array($report_ds_alias)                      &&
                array_key_exists($source[0], $report_ds_alias)  &&
                $report_ds_alias[$source[0]] != ''
               )
               ? $report_ds_alias[$source[0]]
               : $source[0];

    $title  = ($_REQUEST['limit'] < 0)? 'HIGH' : 'LOW';
    $title .= " TOP$limit - $ds - {$data['report_measurands'][$source[1]]['abbreviation']}";
    $rounding = $data['report_measurands'][$source[1]]['rounding'];

    if (count($data['report_results'])>0) {
        $i = 1;
        foreach($data['report_results'] as $result) {
            $results[] = $result[$_REQUEST['source']];
            $x_values[] = $i;
            $i++;
        }
        if($rounding) {
            $exponent = auto_rounding($results, $rounding, $order);
            $prefix = $prefixes[$rounding][$exponent];
        }
    }else {
        exit;
    }

    /* load Graidle */
    $chart = new Graidle($title);

    /* load default settings */
    if (read_config_option('reportit_g_mono')=='on') $chart->setFontMono();
    $chart->setFont(read_config_option('reportit_g_afont'));
    $chart->setFontBd(read_config_option('reportit_g_tfont'));
    $chart->setFontLegend(read_config_option('reportit_g_afont'));

    $chart->setAA(read_config_option('reportit_g_quality'));
    $chart->setdynFontSize();
    if (read_graph_config_option('reportit_g_showgrid') == 'on') $chart->setSecondaryAxis(1,1);

    /* define the size of chart */
    $width  = (read_config_option('reportit_g_mwidth') < read_graph_config_option('reportit_g_width'))
            ? read_config_option('reportit_g_mwidth')
            : read_graph_config_option('reportit_g_width');
    if($width < 100) $width = 100;
    $chart->setWidth($width);

    $height = (read_config_option('reportit_g_mheight') < read_graph_config_option('reportit_g_height'))
            ? read_config_option('reportit_g_mheight')
            : read_graph_config_option('reportit_g_height');
    if($height < 100) $height = 100;
    $chart->setHeight($height);

    /* define the title for the axis */
    if(isset($type['x_axis'])) {
        $x_title = ($type['x_axis']=='1')
                 ?  "{$data['report_measurands'][$source[1]]['abbreviation']}"
                   ."[$prefix{$data['report_measurands'][$source[1]]['unit']}]"
                 : $type['x_axis'];
        $chart->setXtitle($x_title);
    }
    if(isset($type['y_axis'])) {
        $y_title = ($type['y_axis']=='1')
                 ?  "{$data['report_measurands'][$source[1]]['abbreviation']}"
                   ."[$prefix{$data['report_measurands'][$source[1]]['unit']}]"
                 : $type['y_axis'];
        $chart->setYtitle($y_title);
    }

    if(isset($type['filled'])) $chart->setFilled();
    if(isset($type['x_value'])) $chart->setXValue($x_values);

    $chart->setMulticolor();
    /* workaround to avoid loops in Graidle with Hbar and Spider charts */
    if(array_sum($results)== 0 & ($type['name'] == 'hb' | $type['name'] == 's')) return;
    $chart->setValue($results, $type['name']);
    $chart->setExtLegend(0);
    $chart->create();
    $chart->carry();
}

?>
