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

function upgrade_reportit_0_7_2_to_0_7_4(){
	global $config, $database_default;
	db_execute("ALTER TABLE `reportit_reports`
				ADD `autoexport_max_records` SMALLINT NOT NULL DEFAULT '0'");

	db_execute("ALTER TABLE `reportit_cache_reports`
				ADD `autoexport_max_records` SMALLINT NOT NULL DEFAULT '0'");

	db_execute("ALTER TABLE `reportit_reports`
				ADD `autoexport_no_formatting` TINYINT( 1 ) NOT NULL DEFAULT '0'");

	db_execute("ALTER TABLE `reportit_cache_reports`
				ADD `autoexport_no_formatting` TINYINT( 1 ) NOT NULL DEFAULT '0'");
}
