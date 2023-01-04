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

// ----- CONSTANTS FOR: runtime.php -----
define('REPORTIT_NAN', sqrt(-1));

if (!defined('REPORTIT_TMP_FD')) define('REPORTIT_TMP_FD', $config['base_path'] . '/plugins/reportit/tmp/');
if (!defined('REPORTIT_ARC_FD')) define('REPORTIT_ARC_FD', $config['base_path'] . '/plugins/reportit/archive/');
if (!defined('REPORTIT_EXP_FD')) define('REPORTIT_EXP_FD', $config['base_path'] . '/plugins/reportit/exports/');

$timezones = array(
	'AEDT (GMT+11 )'  =>array('hour' => 11, 'min' =>  0),
	'ACDT (GMT+10.5)' =>array('hour' => 10, 'min' => 30),
	'AEST (GMT+10 )'  =>array('hour' => 10, 'min' =>  0),
	'ACST (GMT+09.5)' =>array('hour' =>  9, 'min' => 30),
	' JST (GMT+09 )'  =>array('hour' =>  9, 'min' =>  0),
	' ROK (GMT+09 )'  =>array('hour' =>  9, 'min' =>  0),
	' SST (GMT+08 )'  =>array('hour' =>  8, 'min' =>  0),
	' THA (GMT+07 )'  =>array('hour' =>  7, 'min' =>  0),
	' IST (GMT+05.5)' =>array('hour' =>  5, 'min' => 30),
	'EEST (GMT+03)'   =>array('hour' =>  3, 'min' =>  0),
	'MEST (GMT+02)'   =>array('hour' =>  2, 'min' =>  0),
	' EET (GMT+02)'   =>array('hour' =>  2, 'min' =>  0),
	'CEST (GMT+02)'   =>array('hour' =>  2, 'min' =>  0),
	' BST (GMT+01)'   =>array('hour' =>  1, 'min' =>  0),
	' CET (GMT+01)'   =>array('hour' =>  1, 'min' =>  0),
	' MET (GMT+01)'   =>array('hour' =>  1, 'min' =>  0),
	'WEST (GMT+01)'   =>array('hour' =>  1, 'min' =>  0),
	'GMT'             =>array('hour' =>  0, 'min' =>  0),
	' WET (GMT)'      =>array('hour' =>  0, 'min' =>  0),
	' BDT (GMT-02)'   =>array('hour' => -2, 'min' =>  0),
	'  BT (GMT-03)'   =>array('hour' => -2, 'min' =>  0),
	' EDT (GMT-04)'   =>array('hour' => -4, 'min' =>  0),
	' EST (GMT-05)'   =>array('hour' => -5, 'min' =>  0),
	' CDT (GMT-05)'   =>array('hour' => -5, 'min' =>  0),
	' CST (GMT-06)'   =>array('hour' => -6, 'min' =>  0),
	' MDT (GMT-06)'   =>array('hour' => -6, 'min' =>  0),
	' MST (GMT-07)'   =>array('hour' => -7, 'min' =>  0),
	' PDT (GMT-07)'   =>array('hour' => -7, 'min' =>  0),
	' PST (GMT-08)'   =>array('hour' => -8, 'min' =>  0)
);


$runtime_messages = array(
	1 => 'REPORTIT ERROR: PHP modul for RRDtool is not available.',
	2 => 'REPORTIT ERROR: No data items defined. RIReport[<RID>]',
	3 => 'REPORTIT ERROR: Startpoint is a part of future. RIReport[<RID>] RIDataItem[<DID>]',
	4 => 'REPORTIT ERROR: No valid data found. Check your configuration. RIReport[<RID>]',
	5 => 'REPORTIT WARNING: RRDfetch: <NOTICE> RIReport[<RID>] RIDataItem[<DID>]',
	6 => 'REPORTIT WARNING: End of working time is a part of future. Can only calculate data till now. RIReport[<RID>] RIDataItem[<DID>]',
	7 => 'REPORTIT WARNING: No startpoints available. Check your working days! RIReport[<RID>] RIDataItem[<DID>]',
	8 => 'REPORTIT WARNING: No values available. RIReport[<RID>] RIDataItem[<DID>]',
	9 => 'REPORTIT ERROR: Unable to connect to RRDtool server.',
	10 => 'REPORTIT ERROR: Data template for RIReport[<RID>] has been locked during the cronjob',
	11 => 'REPORTIT WARNING: Unknown timezone: <NOTICE>. Please update configuration of Report [<RID>] RIDataItem[<DID>]',
	12 => 'REPORTIT ERROR: <NOTICE> RIReport[<RID>]',
	13 => 'REPORTIT WARNING: <NOTICE> RIReport[<RID>]',
	14 => 'REPORTIT STATS: <NOTICE> RIReport[<RID>]',
	15 => 'REPORTIT WARNING: <NOTICE>',
	16 => 'REPORTIT NOTICE: <NOTICE>',
	17 => 'REPORTIT ERROR: <NOTICE>'
);

