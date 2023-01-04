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

$cache_id        = '';
$order           = '';
$limit           = '';
$affix           = '';
$exponent        = '';
$prefix          = '';
$results         = array();
$prefixes        = array();
$x_values        = array();
$report_ds_alias = array();

$prefixes[1] = array(
	0 => '',
	1 => 'Ki',
	2 => 'Mi',
	3 => 'Gi',
	4 => 'Ti',
	5 => 'Pi',
	6 => 'Ei',
	7 => 'Zi',
	8 => 'Yi'
);

$prefixes[2] = array(
	0 => '',
	1 => 'k',
	2 => 'M',
	3 => 'G',
	4 => 'T',
	5 => 'P',
	6 => 'E',
	7 => 'Z',
	8 => 'Y'
);

$types = array(
	'-10' => array (
		'description' => __('Bar (vertical)', 'reportit'),
		'name'        => 'b',
		'x_axis'      => 'Position',
		'y_axis'      => 1
	),
	'10' => array (
		'description' => __('Bar (horizontal)', 'reportit'),
		'name'        => 'hb',
		'x_axis'      => 1,
		'y_axis'      => 'Position'
	),
	'20' => array (
		'description' => __('Line', 'reportit'),
		'name'        => 'l',
		'x_axis'      => 'Position',
		'y_axis'      => 1
	),
	'21' => array (
		'description' => __('Area', 'reportit'),
		'name'        => 'l',
		'x_axis'      => 'Position',
		'y_axis'      => 1,
		'filled'      => 1
	),
	'30' => array (
		'description' => __('Pie chart 3D', 'reportit'),
		'name'        => 'p'
	),
	'40' => array (
		'description' => __('Spider', 'reportit'),
		'name'        => 's',
		'x_value'     => '1'
	),
);

