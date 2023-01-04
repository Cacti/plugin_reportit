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

if (!defined('REPORTIT_TMP_FD')) define('REPORTIT_TMP_FD', CACTI_BASE_PATH . '/plugins/reportit/tmp/');
if (!defined('REPORTIT_ARC_FD')) define('REPORTIT_ARC_FD', CACTI_BASE_PATH . '/plugins/reportit/archive/');

$search = array(
	'|t1|',
	'|t2|',
	'|tmz|',
	'|d1|',
	'|d2|'
);

$export_formats	= array(
	'CSV' => __('Text CSV (.csv)', 'reportit'),
	'XML' => __('Raw XML (.xml)', 'reportit'),
	'SML' => __('MS Excel 2003 XML (.xml)', 'reportit')
);

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

$graphs = array(
	'-10' => __('Bar chart: vertical', 'reportit'),
	'10'  => __('Bar chart: horizontal', 'reportit'),
	'20'  => __('Line chart', 'reportit'),
	'21'  => __('Area chart', 'reportit'),
	'30'  => __('Pie chart: 3D', 'reportit'),
	'40'  => __('Spider', 'reportit')
);

$limit = array(
	'-4' => __('%s Hi', 20, 'reportit'),
	'-3' => __('%s Hi', 15, 'reportit'),
	'-2' => __('%s Hi', 10, 'reportit'),
	'-1' => __('%s Hi', 05, 'reportit'),
	'1'  => __('%s Lo', 05, 'reportit'),
	'2'  => __('%s Lo', 10, 'reportit'),
	'3'  => __('%s Lo', 15, 'reportit'),
	'4'  => __('%s Lo', 20, 'reportit')
);

$t_limit = array(
	'0'  => __('Any', 'reportit'),
	'-4' => '20',
	'-3' => '15',
	'-2' => '10',
	'-1' => '05'
);

$add_info = array(
	'-2' => array(__('None', 'reportit'),''),
	'-1' => array(__('Any', 'reportit'), ''),
	'1'  => array(__('Sum', 'reportit'), 'array_sum'),
	'2'  => array(__('Minimum', 'reportit'), 'min'),
	'3'  => array(__('Maximum', 'reportit'), 'max'),
	'4'  => array(__('Average', 'reportit'), 'average')
);

