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

define("MAX_DISPLAY_PAGES", 21);
if(!defined('REPORTIT_TMP_FD')) define('REPORTIT_TMP_FD', CACTI_BASE_PATH . '/plugins/reportit/tmp/');
if(!defined('REPORTIT_ARC_FD')) define('REPORTIT_ARC_FD', CACTI_BASE_PATH . '/plugins/reportit/archive/');

$link_array		= array('description', 'user_id', 'template_description', '', '', '');

$search 		= array('|t1|', '|t2|', '|tmz|', '|d1|', '|d2|');

$export_formats	= array('CSV' => 'Text CSV (.csv)',
                        'XML' => 'Raw XML (.xml)',
                        'SML' => 'MS Excel 2003 XML (.xml)');

$threshold 	= 0.5;

$decimal	= array('Y' => pow(1000,8),
			'Z' => pow(1000,7),
			'E' => pow(1000,6),
			'P' => pow(1000,5),
			'T' => pow(1000,4),
			'G' => pow(1000,3),
			'M' => pow(1000,2),
			'K' => 1000);

$binary		= array('Y' => pow(1024,8),
			'Z' => pow(1024,7),
			'E' => pow(1024,6),
			'P' => pow(1024,5),
			'T' => pow(1024,4),
			'G' => pow(1024,3),
			'M' => pow(1024,2),
			'K' => 1024);

$IEC		= read_config_option('reportit_use_IEC');

$graphs        = array('-10'    => 'Bar chart: vertical',
                        '10'    => 'Bar chart: horizontal',
                        '20'    => 'Line chart',
                        '21'    => 'Area chart',
                        '30'    => 'Pie chart: 3D',
                        '40'    => 'Spider');

$limit		= array('-4' 	=> '20 Hi',
					'-3' 	=> '15 Hi',
					'-2' 	=> '10 Hi',
					'-1' 	=> '05 Hi',
					'1'		=> '05 Lo',
					'2' 	=> '10 Lo',
					'3'		=> '15 Lo',
					'4'		=> '20 Lo');

$t_limit	= array('0'	=> 'Any',
					'-4' 	=> '20',
					'-3' 	=> '15',
					'-2' 	=> '10',
					'-1' 	=> '05');

$add_info	= array('-2'	=> array('None',''),
					'-1'	=> array('Any', ''),
					'1'		=> array('Sum', 'array_sum'),
					'2'		=> array('Minimum', 'min'),
					'3'		=> array('Maximum', 'max'),
					'4'		=> array('Average', 'average'));
?>
