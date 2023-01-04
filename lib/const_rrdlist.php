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

//----- CONSTANTS FOR: rrdlist.php -----

$rrdlist_actions = array(
	1 => __('Delete', 'reportit'),
	2 => __('Copy settings to all', 'reportit')
);

$rrdadd_actions = array(
	1 => __('Add', 'reportit')
);

$link_array = array(
	'name_cache',
	'description',
	'',
	'',
	'timezone',
	''
);

// $timezone - array, for dropdown menu
//           - contains the $keys from $timezones array.
foreach ($timezones as $key => $value) {
	$timezone[] = $key;
}

// $weekday - array, for dropdown menu
//          - contains the names of all weekdays
$weekday = array(
	__('Monday', 'reportit'),
	__('Tuesday', 'reportit'),
	__('Wednesday', 'reportit'),
	__('Thursday', 'reportit'),
	__('Friday', 'reportit'),
	__('Saturday', 'reportit'),
	__('Sunday', 'reportit')
);

// $shifttime - array, for dropdown menu
//            - contains all possible timestamps of a day by using steps of 5 minutes
$shifttime = array();

for($i = 0; $i < 24; $i++) {
	$hour=$i;

	if ($hour < 10) {
		$hour = '0' . $hour;
	}

	for($j = 0; $j < 60; $j += 5) {
		$minutes = $j;

		if ($minutes < 10) {
			$minutes = '0' . $minutes;
		}

		$shifttime[] = "$hour:$minutes:00";
	}
}

$shifttime2  = $shifttime;
$shifttime2[]= '24:00:00';

unset($i);
unset($j);

