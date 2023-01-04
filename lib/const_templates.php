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

// ----- CONSTANTS FOR: templates.php -----

$template_actions = array(
	1 => 'Delete',
	2 => 'Duplicate',
	3 => 'Export',
);

$desc_array = array(
	'Template Name',
	'Data Template',
	'Pre-filter',
	'Locked',
	'Measurands',
	'Variables'
);

$link_array = array(
	'description',
	'data_template_id',
	'pre_filter',
	'locked',
	'measurands',
	'variables'
);

$order_array = array('ASC', 'DESC');

$sql = 'SELECT DISTINCT b.id, b.name FROM data_template_rrd AS a
	INNER JOIN data_template as b
	ON a.data_template_id = b.id
	WHERE a.local_data_id != 0
	ORDER BY b.name';

$data_templates            = array();
$list_of_data_templates    = array();
$data_templates            = db_fetch_assoc($sql);
foreach($data_templates as $data_template) {
	$list_of_data_templates[$data_template['id']] = $data_template['name'];
}

$sql = 'SELECT DISTINCT b.id, b.name FROM data_template AS b
	ORDER BY b.name';


global $known_data_templates;
$known_data_templates = array();

$data_templates       = db_fetch_assoc($sql);
foreach($data_templates as $data_template) {
	$known_data_templates[$data_template['id']] = $data_template['name'];
}


$hashes = array(
	'1' => array(
		'reportit' => 'c0788d60041d96616d05b87892942948',
		'general'  => 'b993b55029680216764b47d1da5c18d',
		'settings' => 'd446e8da603362e98b7d868e99e144fd',
		'measurand'=> 'd52041e0e00f5daac84f1cd15532732c',
		'variable' => 'fa1e95ba13fc87fa80da64758628d68f'
	)
);

