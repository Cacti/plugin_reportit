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

function export_to_PDF(&$data) {

}

function export_to_CSV(&$data) {
	global $search, $run_scheduled;

	$eol          = PHP_EOL;
	$rows         = '';
	$header       = '';
	$info_line    = '';
	$tab_head_1   = '';
	$tab_head_2   = '';
	$data_sources = array();

	$csv_c_sep    = array(',', ';', "\t", ' ');
	$csv_d_sep    = array(',', '.');

	$measurands   = isset_request_var('measurand')   ? get_request_var('measurand')   : '-1';
	$datasources  = isset_request_var('data_source') ? get_request_var('data_source') : '-1';

	$report_ds_alias   = $data['report_ds_alias'];
	$report_data       = $data['report_data'];
	$report_results    = $data['report_results'];
	$report_measurands = $data['report_measurands'];
	$report_variables  = $data['report_variables'];

	$csv_column_s  = read_config_option('reportit_csv_column_s');
	$csv_decimal_s = read_config_option('reportit_csv_decimal_s');

	/* load user settings */
	if ($run_scheduled !== true) {
		/* request via web */
		$no_formatting = 0;
		$c_sep = $csv_c_sep[$csv_column_s];
		$d_sep = $csv_d_sep[$csv_decimal_s];
	} else {
		/* request via cli */
		$no_formatting = 0;
		$c_sep = $csv_c_sep[$csv_column_s];
		$d_sep = $csv_d_sep[$csv_decimal_s];
	}
	/* plugin version */
	$info = plugin_reportit_version();
	/* form the export header */
	$header = read_config_option('reportit_exp_header');
	$header = str_replace("<cacti_version>", "$eol# Cacti: " . CACTI_VERSION, $header);

	$header = str_replace("<reportit_version>", " ReportIt: " . $info['version'] , $header);

	/* compose additional informations */
	$report_settings = array(
		__('Report title', 'reportit') => "{$report_data['name']}",
		__('Owner', 'reportit')        => "{$report_data['owner']}",
		__('Template', 'reportit')     => "{$report_data['template_name']}",
		__('Start', 'reportit')        => "{$report_data['start_date']}",
		__('End', 'reportit')          => "{$report_data['end_date']}",
		__('Last Run', 'reportit')     => "{$report_data['last_started']}"
	);

	$ds_description = explode('|', $report_data['ds_description']);

	/* read out data sources */
	if ($datasources > -1) {
		$ds_description = array($ds_description[$datasources]);
	} elseif ($datasources < -1) {
		$ds_description = array('overall');
	}

	/* read out the result ids */
	list($rs_ids, $rs_cnt) = explode('-', $report_data['rs_def']);
	$rs_ids = ($rs_ids == '') ? false : explode('|', $rs_ids);
	if ($measurands != '-1' && $rs_ids !== false) {
		$rs_ids = array(get_request_var('measurand'));
		$rs_cnt = 1;
	}

	/* sort out all measurands which shouldn't be visible */
	if ($rs_ids !== false && cacti_sizeof($rs_ids)>0) {
		foreach ($rs_ids as $key => $id) {
			if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == '') {
				$rs_cnt--;
				unset($rs_ids[$key]);
			}
		}
	}

	if ($datasources < 0) {
		/* read out the 'spanned' ids */
		list($ov_ids, $ov_cnt)	 = explode('-', $report_data['sp_def']);

		$ov_ids = ($ov_ids == '') ? false : explode('|', $ov_ids);
		if ($measurands != '-1' && $ov_ids !== false) {
			$ov_ids = array(get_request_var('measurand'));
			$ov_cnt = 1;
		}

		/* sort out all measurands which shouldn't be visible */
		if ($ov_ids !== false && cacti_sizeof($ov_ids)>0) {
			foreach ($ov_ids as $key => $id) {
				if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == '') {
					$ov_cnt--;
					unset($ov_ids[$key]);
				}
			}
		}

		if ($measurands == -1 ) {
			if ($ov_cnt >0 && !in_array('overall', $ds_description)) {
				$ds_description[]= 'overall';
			}
		} elseif (in_array($measurands, $ov_ids)) {
			if ($ov_cnt >0 && !in_array('overall', $ds_description)) {
				$ds_description = array('overall');
			}
		}
	}

	/* create puffered CSV output */
	ob_start();

	/* report header */
	print "$header $eol";

	/* report settings */
	print "$eol";
	foreach ($report_settings as $key => $value) {
		print "# $key: $value $eol";
	}

	/* defined variables */
	print $eol . "# Variables: $eol";
	foreach ($report_variables as $var) {
		print "# {$var['name']}: {$var['value']} $eol";
	}

	/* build a legend to explain the abbreviations of measurands */
	print $eol . "# Legend: $eol";
	foreach ($report_measurands as $id) {
		print "# {$id['abbreviation']}: {$id['name']} $eol";
	}

	/* print table header */
	for($i = 1; $i < 8; $i++) {
		$tab_head_1 .= "$c_sep";
	}

	$tab_head_2 = $tab_head_1;

	foreach ($ds_description as $datasource){
		$name = ($datasource != 'overall') ? $rs_ids : $ov_ids;

		if ($name !== false) {
			foreach ($name as $id) {
				if (is_array($report_ds_alias) && array_key_exists($datasource, $report_ds_alias) && $report_ds_alias[$datasource] != '') {
					$tab_head_1 .= $report_ds_alias[$datasource] . $c_sep;
				} else {
					$tab_head_1 .= $datasource . $c_sep;
				}
				$var = ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
				$tab_head_2 .= $report_measurands[$id]['abbreviation'] . "[" . $report_measurands[$id]['unit'] . "]" . $c_sep;
			}
		}
	}

	print $eol . "$tab_head_1 $eol $tab_head_2 $eol";

	/* print results */
	foreach ($report_results as $result){
		$replace = array ($result['start_time'], $result['end_time'], $result['timezone'], $result['start_day'], $result['end_day']);
		$subhead = str_replace($search, $replace, $result['description']);

		print "\t\t\t<Row>$eol";
		print '"' . $result['name_cache'] . '"' . $c_sep;
		print '"' . $subhead              . '"' . $c_sep;
		print '"' . $result['start_day']  . '"' . $c_sep;
		print '"' . $result['end_day']    . '"' . $c_sep;
		print '"' . $result['start_time'] . '"' . $c_sep;
		print '"' . $result['end_time']   . '"' . $c_sep;
		print '"' . $result['timezone']   . '"' . $c_sep;

		foreach ($ds_description as $datasource) {
			$name = ($datasource != 'overall') ? $rs_ids : $ov_ids;

			if ($name !== false) {
				foreach ($name as $id) {
					$rounding       = $report_measurands[$id]['rounding'];
					$data_type      = $report_measurands[$id]['data_type'];
					$data_precision = $report_measurands[$id]['data_precision'];

					$var   = ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;

					$value = ($result[$var] == NULL)? 'NA': str_replace(".", $d_sep, (($no_formatting) ? $result[$var] : get_unit($result[$var], $rounding, $data_type, $data_precision) ));

					print '"'. $value .'"' . $c_sep;
				}
			}
		}

		print "$eol";
	}

	return ob_get_clean();
}

function export_to_XML(&$data) {
	global $search, $run_scheduled;

	$eol       = PHP_EOL;
	$add_infos = '';
	$output    = '';
	$header    = '';

	$mea = array();

	transform_htmlspecialchars($data);

	$report_data       = $data['report_data'];
	$report_results    = $data['report_results'];
	$report_measurands = $data['report_measurands'];
	$report_variables  = $data['report_variables'];
	$ds_description    = explode('|', $report_data['ds_description']);
	$no_formatting     = 0;

	/* form the export header */
	$header = read_config_option('reportit_exp_header');
	$header = str_replace('<cacti_version>', "\r\nCacti: " . CACTI_VERSION, $header);
	$info = plugin_reportit_version();
	$header = str_replace('<reportit_version>', ' ReportIt: ' . $info['version'], $header);

	/* compose additional informations */
	$report_settings = array(
		'title'        => $report_data['description'],
		'owner'        => $report_data['owner'],
		'template'     => $report_data['template_name'],
		'start'        => $report_data['start_date'],
		'end'          => $report_data['end_date'],
		'last_started' => $report_data['last_started']
	);

	/* read out the result ids */
	list($rs_ids, $rs_cnt) = explode('-', $report_data['rs_def']);
	$rs_ids = ($rs_ids == '') ? false : explode('|', $rs_ids);

	/* read out the 'spanned' ids */
	list($ov_ids, $ov_cnt)	 = explode('-', $report_data['sp_def']);
	$ov_ids = ($ov_ids == '') ? false : explode('|', $ov_ids);

	if ($ov_cnt >0) {
		$ds_description[]= 'overall';
	}

	/* create puffered xml output */
	ob_start();

	print "<!--{$header} -->";
	print "<cacti>$eol<report>$eol<settings>$eol";

	foreach ($report_settings as $key => $value) {
		print "<$key>$value</$key>$eol";
	}

	print "</settings>$eol<variables>$eol";

	foreach ($report_variables as $variable) {
		print "<variable>$eol";

		foreach ($variable as $key => $value) {
			print "<$key>$value</$key>$eol";
		}

		print "</variable>$eol";
	}

	print "</variables>$eol<measurands>$eol";

	foreach ($report_measurands as $measurand){
		$id = $measurand['id'];

		$mea[$id]['abbreviation']   = $measurand['abbreviation'];
		$mea[$id]['visible']        = $measurand['visible'];
		$mea[$id]['unit']           = $measurand['unit'];
		$mea[$id]['rounding']       = $measurand['rounding'];
		$mea[$id]['data_type']      = $measurand['data_type'];
		$mea[$id]['data_precision'] = $measurand['data_precision'];

		print "<measurand>$eol";
		print "<abbreviation>{$measurand['abbreviation']}</abbreviation>$eol";
		print "<description>{$measurand['description']}</description>$eol";
		print "</measurand>$eol";
	}

	print "</measurands>$eol<data_items>$eol";

	foreach ($report_results as $result){
		$replace = array ($result['start_time'], $result['end_time'], $result['timezone'], $result['start_day'], $result['end_day']);
		$subhead = str_replace($search, $replace, $result['description']);

		print "<item>$eol";
		print "<description>{$result['name_cache']}</description>$eol";
		print "<subhead>". $subhead . "</subhead>$eol";
		print "<start_day>{$result['start_day']}</start_day>$eol";
		print "<end_day>{$result['end_day']}</end_day>$eol";
		print "<start_time>{$result['start_time']}</start_time>$eol";
		print "<end_time>{$result['end_time']}</end_time>$eol";
		print "<time_zone>{$result['timezone']}</time_zone>$eol";
		print "<results>$eol";

		foreach ($ds_description as $datasource) {
			print "<$datasource>$eol";

			$name = ($datasource != 'overall') ? $rs_ids : $ov_ids;

			if ($name !== false) {
				foreach ($name as $id) {
					$var            = ($datasource != 'overall') ? $datasource . '__' . $id : 'spanned__' . $id;
					$abbr           = strtolower($mea[$id]['abbreviation']);
					$value          = $result[$var];
					$rounding       = $mea[$id]['rounding'];
					$data_type      = $mea[$id]['data_type'];
					$data_precision = $mea[$id]['data_precision'];

					$value  = ($value == NULL)? 'NA' : (($no_formatting) ? $value : get_unit($value, $rounding, $data_type, $data_precision) );
					print "<$abbr measurand=\"{$mea[$id]['abbreviation']}\" unit=\"{$mea[$id]['unit']}\">$eol";
					print "$value";
					print "</$abbr >$eol";
				}
			}

			print "</$datasource>$eol";
		}

		print"</results>$eol";
		print "</item>$eol";
	}

	print "</data_items>$eol";

	print "</report>$eol</cacti>$eol";
	$output = mb_convert_encoding(ob_get_clean(), 'UTF-8', 'ISO-8859-1');

	return $output;
}

function export_to_YAML(&$data) {
	$report_data = export_to_JSON($data);

	if (function_exists('yaml_emit')) {
		return yaml_emit(json_decode($report_data, true));
	} else {
		cacti_log('WARNING: You attempted to use YAML as the export format, but PHP is not built using the yaml functions', false, 'REPORTIT');
		return false;
	}
}

function export_to_JSON(&$data) {
	global $search, $run_scheduled;

	$json_data = array();

	$info = plugin_reportit_version();

	transform_htmlspecialchars($data);

	/* add some header components */
	$json_data['header'] = str_replace(array('<cacti_version>', '<reportit_version>'), array('Cacti: ' . CACTI_VERSION, 'ReportIt: ' . $info['version']), read_config_option('reportit_exp_header'));

	$json_data['version_cacti']   = CACTI_VERSION;
	$json_data['version_reporit'] = $info['version'];

	$mea = array();

	$report_data       = $data['report_data'];
	$report_results    = $data['report_results'];
	$report_measurands = $data['report_measurands'];
	$report_variables  = $data['report_variables'];
	$ds_description    = explode('|', $report_data['ds_description']);
	$no_formatting     = 0;

	/* compose additional informations */
	$report_settings = array(
		'title'        => $report_data['description'],
		'owner'        => $report_data['owner'],
		'template'     => $report_data['template_name'],
		'start'        => $report_data['start_date'],
		'end'          => $report_data['end_date'],
		'last_started' => $report_data['last_started']
	);

	/* read out the result ids */
	list($rs_ids, $rs_cnt) = explode('-', $report_data['rs_def']);
	$rs_ids = ($rs_ids == '') ? false : explode('|', $rs_ids);

	/* read out the 'spanned' ids */
	list($ov_ids, $ov_cnt)	 = explode('-', $report_data['sp_def']);
	$ov_ids = ($ov_ids == '') ? false : explode('|', $ov_ids);

	if ($ov_cnt > 0) {
		$ds_description[]= 'overall';
	}

	foreach ($report_settings as $key => $value) {
		$json_data['settings'][$key] = $value;
	}

	$i = 0;
	foreach ($report_variables as $variable) {
		foreach ($variable as $key => $value) {
			$json_data['variables'][$i][$key] = $value;
		}

		$i++;
	}

	foreach ($report_measurands as $measurand){
		$id = $measurand['id'];

		$mea[$id]['abbreviation']   = $measurand['abbreviation'];
		$mea[$id]['visible']        = $measurand['visible'];
		$mea[$id]['unit']           = $measurand['unit'];
		$mea[$id]['rounding']       = $measurand['rounding'];
		$mea[$id]['data_type']      = $measurand['data_type'];
		$mea[$id]['data_precision'] = $measurand['data_precision'];

		$json_data['measurands'][$id] = $measurand;
	}

	$json_data['report_raw_data'] = $data;

	$i = 0;

	foreach ($report_results as $result){
		$replace = array ($result['start_time'], $result['end_time'], $result['timezone'], $result['start_day'], $result['end_day']);
		$subhead = str_replace($search, $replace, $result['description']);

		$json_data['data_items']['item'][$i]['description'] = $result['name_cache'];
		$json_data['data_items']['item'][$i]['subhead']     = $subhead;
		$json_data['data_items']['item'][$i]['start_day']   = $result['start_day'];
		$json_data['data_items']['item'][$i]['end_day']     = $result['end_day'];
		$json_data['data_items']['item'][$i]['start_time']  = $result['start_time'];
		$json_data['data_items']['item'][$i]['end_time']    = $result['end_time'];
		$json_data['data_items']['item'][$i]['time_zone']   = $result['timezone'];

		foreach ($ds_description as $datasource) {
			$name = ($datasource != 'overall') ? $rs_ids : $ov_ids;

			if ($name !== false) {
				foreach ($name as $id) {
					$var            = ($datasource != 'overall') ? $datasource . '__' . $id : 'spanned__' . $id;
					$abbr           = strtolower($mea[$id]['abbreviation']);
					$value          = $result[$var];
					$rounding       = $mea[$id]['rounding'];
					$data_type      = $mea[$id]['data_type'];
					$data_precision = $mea[$id]['data_precision'];

					$value  = ($value == NULL)? 'NA' : (($no_formatting) ? $value : get_unit($value, $rounding, $data_type, $data_precision) );

					$json_data['data_items']['item'][$i]['results'][$datasource][$abbr]['measurand'] = $mea[$id]['abbreviation'];
					$json_data['data_items']['item'][$i]['results'][$datasource][$abbr]['unit']      = $mea[$id]['unit'];
					$json_data['data_items']['item'][$i]['results'][$datasource][$abbr]['value']     = $value;
				}
			}
		}

		$i++;
	}

	return json_encode($json_data, JSON_PRETTY_PRINT);
}

function export_to_SML(&$data) {
	$eol = PHP_EOL;

	$sml_workbook	= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"$eol
		xmlns:o=\"urn:schemas-microsoft-com:office:office\"$eol
		xmlns:x=\"urn:schemas-microsoft-com:office:excel\"$eol
		xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"$eol
		xmlns:html=\"http://www.w3.org/TR/REC-html40\">$eol";

	$sml_properties =  "<DocumentProperties xmlns=\"urn:schemas-microsoft-com:office:office\">$eol
		<Created>2007-04-17T09:28:01Z</Created>$eol
		</DocumentProperties>$eol";

	$sml_styles	 = " <Styles>$eol
		<Style ss:ID='theme_1'>$eol
		<Interior/>$eol
		<Font/>$eol
		<Borders/>$eol
		</Style>$eol
		<Style ss:ID='theme_2'>$eol
		<Interior ss:Color='#00356f' ss:Pattern='Solid'/>$eol
		</Style>$eol
		</Styles>$eol";

	$footer = "</Workbook>";

	/* create puffered xml output */
	ob_start();

	print "<?xml version='1.0' encoding='UTF-8'?>";
	print "<?mso-application progid='Excel.Sheet'?>";
	print $sml_workbook;
	print $sml_properties;
	print $sml_styles;
	print new_worksheet($data, $sml_styles);
	print $footer;
	$output = mb_convert_encoding(ob_get_clean(), 'UTF-8', 'ISO-8859-1');

	return $output;
}

function new_worksheet(&$data, &$styles){
	global $search, $run_scheduled;

	$eol          = PHP_EOL;
	$rows         = '';
	$header       = '';
	$info_line    = '';
	$tab_head_1   = '';
	$tab_head_2   = '';
	$data_sources = array();
	$csv_c_sep    = array(',', ';', "\t", ' ');
	$csv_d_sep    = array(',', '.');

	$measurands   = isset_request_var('measurand')? get_request_var('measurand') : '-1';
	$datasources  = isset_request_var('data_source') ? get_request_var('data_source') : '-1';

	/* except serialized data */

	$report_ds_alias   = $data['report_ds_alias'];
	$report_data       = $data['report_data'];
	$report_results    = $data['report_results'];
	$report_measurands = $data['report_measurands'];
	$report_variables  = $data['report_variables'];
	$no_formatting     = 0;

	/* form the export header */
	$info = $info = plugin_reportit_version();
	$header = read_config_option('reportit_exp_header');
	$header = str_replace('<cacti_version>', ' Cacti: ' . CACTI_VERSION, $header);
	$header = str_replace('<reportit_version>', ' ReportIt: ' . $info['version'], $header);

	/* compose additional informations */
	$report_settings = array(
		__('Report title', 'reportit') => $report_data['description'],
		__('Owner', 'reportit')        => $report_data['owner'],
		__('Template', 'reportit')     => $report_data['template_name'],
		__('Start', 'reportit')        => $report_data['start_date'],
		__('End', 'reportit')          => $report_data['end_date'],
		__('Last Run', 'reportit')     => $report_data['last_started']
	);

	$ds_description = explode('|', $report_data['ds_description']);

	/* read out data sources */
	if ($datasources > -1) {
		$ds_description = array($ds_description[$datasources]);
	} elseif ($datasources < -1) {
		$ds_description = array('overall');
	}

	/* read out the result ids */
	list($rs_ids, $rs_cnt) = explode('-', $report_data['rs_def']);
	$rs_ids = ($rs_ids == '') ? false : explode('|', $rs_ids);

	if ($measurands != '-1' && $rs_ids !== false) {
		$rs_ids = array(get_request_var('measurand'));
		$rs_cnt = 1;
	}

	/* sort out all measurands which shouldn't be visible */
	if ($rs_ids !== false && cacti_sizeof($rs_ids)>0) {
		foreach ($rs_ids as $key => $id) {
			if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == '') {
				$rs_cnt--;
				unset($rs_ids[$key]);
			}
		}
	}

	if ($datasources < 0) {
		/* read out the 'spanned' ids */
		list($ov_ids, $ov_cnt) = explode('-', $report_data['sp_def']);
		$ov_ids = ($ov_ids == '') ? false : explode('|', $ov_ids);

		if ($measurands != '-1' && $ov_ids !== false) {
			$ov_ids = array(get_request_var('measurand'));
			$ov_cnt = 1;
		}

		/* sort out all measurands which shouldn't be visible */
		if ($ov_ids !== false && cacti_sizeof($ov_ids)>0) {
			foreach ($ov_ids as $key => $id) {
				if (!isset($data['report_measurands'][$id]['visible']) || $data['report_measurands'][$id]['visible'] == '') {
					$ov_cnt--;
					unset($ov_ids[$key]);
				}
			}
		}

		if ($measurands == -1 ) {
			if ($ov_cnt >0 && !in_array('overall', $ds_description)) {
				$ds_description[]= 'overall';
			}
		} elseif (in_array($measurands, $ov_ids)) {
			if ($ov_cnt >0 && !in_array('overall', $ds_description)) {
				$ds_description = array('overall');
			}
		}
	}

	/* create puffered CSV output */
	ob_start();

	/* worksheet header */
	print "\t<Worksheet ss:Name='{$report_data['description']}'>$eol";
	print "\t\t<Table ss:StyleID='theme_1'>$eol";

	/* report header */
	print sml_cell($header, true);

	/* report settings */
	print sml_cell('', true);
	foreach ($report_settings as $key => $value) {
		print sml_cell("# $key: $value", true);
	}

	/* defined variables */
	print sml_cell('', true);
	print sml_cell('# Variables:', true);
	foreach ($report_variables as $var) {
		print sml_cell("# {$var['name']}: {$var['value']}", true);
	}

	/* build a legend to explain the abbreviations of measurands */
	print sml_cell('# Legend:', true);
	foreach ($report_measurands as $id) {
		print sml_cell ("# {$id['abbreviation']}: {$id['description']}", true);
	}

	/* print table header */
	print sml_cell('', true);

	print "\t\t\t<Row>$eol";

	for($i = 1; $i < 8; $i++) {
		$tab_head_1 .= sml_cell('');
	}

	$tab_head_2 = $tab_head_1;

	foreach ($ds_description as $datasource){
		$name = ($datasource != 'overall') ? $rs_ids : $ov_ids;

		if ($name !== false) {
			foreach ($name as $id) {
				if (is_array($report_ds_alias) && array_key_exists($datasource, $report_ds_alias) && $report_ds_alias[$datasource] != '') {
					$tab_head_1 .= sml_cell($report_ds_alias[$datasource]);
				} else {
					$tab_head_1 .= sml_cell($datasource);
				}

				$var = ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
				$tab_head_2 .= sml_cell($report_measurands[$id]['abbreviation'] . "[" . $report_measurands[$id]['unit'] . "]");
			}
		}
	}

	print "$eol $tab_head_1\t\t\t</Row>$eol\t\t\t<Row>$tab_head_2 $eol";
	print "\t\t\t</Row>$eol";

	/* print results */
	foreach ($report_results as $result){
		$replace = array ($result['start_time'], $result['end_time'], $result['timezone'], $result['start_day'], $result['end_day']);
		$subhead = str_replace($search, $replace, $result['description']);

		print "\t\t\t<Row>$eol";
		print sml_cell($result['name_cache']);
		print sml_cell($subhead);
		print sml_cell($result['start_day']);
		print sml_cell($result['end_day']);
		print sml_cell($result['start_time']);
		print sml_cell($result['end_time']);
		print sml_cell($result['timezone']);

		foreach ($ds_description as $datasource) {
			$name = ($datasource != 'overall') ? $rs_ids : $ov_ids;

			if ($name !== false) {
				foreach ($name as $id) {
					$var = ($datasource != 'overall') ? $datasource.'__'.$id : 'spanned__'.$id;
					$rounding = $report_measurands[$id]['rounding'];
					$data_type = $report_measurands[$id]['data_type'];
					$data_precision = $report_measurands[$id]['data_precision'];

					$value = $result[$var];
					$value = ($value == NULL)? 'NA' : (($no_formatting) ? $value : get_unit($value, $rounding, $data_type, $data_precision) );
					print sml_cell($value);
				}
			}
		}

		print "\t\t\t</Row>$eol";
	}

	/* print worksheet footer */
	print "\t\t</Table>$eol";
	print "\t</Worksheet>$eol";

	return ob_get_clean();
}

function sml_cell($data, $row=false, $styleID=false){
	$eol = PHP_EOL;

	$data_style = is_numeric($data) ? " ss:Type='Number'" : " ss:Type='String'";

	$cell_style = ($styleID == false)	? '' : " ss:StyleID='$styleID'";

	/* form cell output */
	$cell = "\t\t\t\t<Cell $cell_style>$eol\t\t\t\t\t<Data $data_style>$data</Data>$eol\t\t\t\t</Cell>$eol";

	/* add row tags if required */
	if ($row !==false) {
		$cell = "\t\t\t<Row>$eol" . "$cell" . "\t\t\t</Row>$eol";
	}

	return $cell;
}

