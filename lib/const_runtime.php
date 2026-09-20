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

// ----- CONSTANTS FOR: runtime.php -----
define('REPORTIT_NAN', sqrt(-1));

if (!defined('REPORTIT_TMP_FD')) {
	define('REPORTIT_TMP_FD', CACTI_PATH_BASE . '/plugins/reportit/tmp/');
}

if (!defined('REPORTIT_ARC_FD')) {
	define('REPORTIT_ARC_FD', CACTI_PATH_BASE . '/plugins/reportit/archive/');
}

if (!defined('REPORTIT_EXP_FD')) {
	define('REPORTIT_EXP_FD', CACTI_PATH_BASE . '/plugins/reportit/exports/');
}

$timezones = [
	'AEDT (GMT+11 )'  => ['hour' => 11, 'min' =>  0],
	'ACDT (GMT+10.5)' => ['hour' => 10, 'min' => 30],
	'AEST (GMT+10 )'  => ['hour' => 10, 'min' =>  0],
	'ACST (GMT+09.5)' => ['hour' =>  9, 'min' => 30],
	'JST (GMT+09 )'   => ['hour' =>  9, 'min' =>  0],
	'ROK (GMT+09 )'   => ['hour' =>  9, 'min' =>  0],
	'SST (GMT+08 )'   => ['hour' =>  8, 'min' =>  0],
	'THA (GMT+07 )'   => ['hour' =>  7, 'min' =>  0],
	'IST (GMT+05.5)'  => ['hour' =>  5, 'min' => 30],
	'EEST (GMT+03)'   => ['hour' =>  3, 'min' =>  0],
	'MEST (GMT+02)'   => ['hour' =>  2, 'min' =>  0],
	'EET (GMT+02)'    => ['hour' =>  2, 'min' =>  0],
	'CEST (GMT+02)'   => ['hour' =>  2, 'min' =>  0],
	'BST (GMT+01)'    => ['hour' =>  1, 'min' =>  0],
	'CET (GMT+01)'    => ['hour' =>  1, 'min' =>  0],
	'MET (GMT+01)'    => ['hour' =>  1, 'min' =>  0],
	'WEST (GMT+01)'   => ['hour' =>  1, 'min' =>  0],
	'GMT'             => ['hour' =>  0, 'min' =>  0],
	'WET (GMT)'       => ['hour' =>  0, 'min' =>  0],
	'BDT (GMT-02)'    => ['hour' => -2, 'min' =>  0],
	'BT (GMT-03)'     => ['hour' => -2, 'min' =>  0],
	'EDT (GMT-04)'    => ['hour' => -4, 'min' =>  0],
	'EST (GMT-05)'    => ['hour' => -5, 'min' =>  0],
	'CDT (GMT-05)'    => ['hour' => -5, 'min' =>  0],
	'CST (GMT-06)'    => ['hour' => -6, 'min' =>  0],
	'MDT (GMT-06)'    => ['hour' => -6, 'min' =>  0],
	'MST (GMT-07)'    => ['hour' => -7, 'min' =>  0],
	'PDT (GMT-07)'    => ['hour' => -7, 'min' =>  0],
	'PST (GMT-08)'    => ['hour' => -8, 'min' =>  0]
];

$runtime_messages = [
	1  => 'ERROR: PHP module for RRDtool is not available.',
	2  => 'ERROR: No data items defined. RIReport[<RID>]',
	3  => 'ERROR: Startpoint is a part of future. RIReport[<RID>] RIDataItem[<DID>]',
	4  => 'ERROR: No valid data found. Check your configuration. RIReport[<RID>]',
	5  => 'WARNING: RRDfetch: <NOTICE> RIReport[<RID>] RIDataItem[<DID>]',
	6  => 'WARNING: End of working time is a part of future. Can only calculate data till now. RIReport[<RID>] RIDataItem[<DID>]',
	7  => 'WARNING: No startpoints available. Check your working days! RIReport[<RID>] RIDataItem[<DID>]',
	8  => 'WARNING: No values available. RIReport[<RID>] RIDataItem[<DID>]',
	9  => 'ERROR: Unable to connect to RRDtool server.',
	10 => 'ERROR: Data Template for RIReport[<RID>] has been locked during the scheduled task',
	11 => 'WARNING: Unknown timezone: <NOTICE>. Please update configuration of Report [<RID>] RIDataItem[<DID>]',
	12 => 'ERROR: <NOTICE> RIReport[<RID>]',
	13 => 'WARNING: <NOTICE> RIReport[<RID>]',
	14 => 'STATS: <NOTICE> RIReport[<RID>]',
	15 => 'WARNING: <NOTICE>',
	16 => 'NOTICE: <NOTICE>',
	17 => 'ERROR: <NOTICE>'
];
