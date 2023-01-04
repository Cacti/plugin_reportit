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

//----- CONSTANTS FOR: variables.php -----

$variable_actions = array(
	1 => __('Delete', 'reportit')
);

$var_types = array(
	1 => __('Dropdown', 'reportit'),
	2 => __('Input field', 'reportit')
);

$link_array = array(
	'name',
	'abbreviation',
	'max_value',
	'min_value',
	'default_value',
	'input_type'
);

$list_of_modes = array(
	'ASC',
	'DESC'
);

$desc_array = array(
	'description' => array('display' => __('Name', 'reportit'),          'align' => 'left', 'sort' => 'ASC'),
	'nosort'      => array('display' => __('Internal Name', 'reportit'), 'align' => 'left'),
	'pre_filter'  => array('display' => __('Maximum', 'reportit'),       'align' => 'left'),
	'nosort1'     => array('display' => __('Minimum', 'reportit'),       'align' => 'left'),
	'nosort2'     => array('display' => __('Default', 'reportit'),       'align' => 'left', 'sort' => 'ASC'),
	'nosort3'     => array('display' => __('Input Type', 'reportit'),    'align' => 'left', 'sort' => 'ASC'),
);

