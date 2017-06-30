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

//Check if this script has been called at the CLI.
if(!isset($_SERVER['argv'][0]) || realpath($_SERVER['argv'][0]) != __FILE__) {
    die("<br><b>This script is meant to run at the CLI!</b>");
}

$path = dirname(__FILE__);
chdir($path);
chdir('../../');

/* Try to support 0.8.7 and lower versions as well*/
if(is_file('./include/global.php')) include_once('./include/global.php');
else include_once('./include/config.php');

global $config, $database_default;

$sql        = "show tables from `" . $database_default . "`";
$result     = db_fetch_assoc($sql) or die (mysql_error());
$tables     = array();
$sql        = array();

$input = '';

while(1!=0) {
	echo "\n\n";
	echo "WARNING!\nThis script deletes ALL tables of \"ReportIT\"\n";
	echo "and also removes all user settings which are\n";
	echo "in relationship with this plugin.\n";
	echo "It's strongly recommend to backup your \n";
	echo "Cacti database before using this script!\n";
	echo "Be sure that no report is running.\n";
	echo "Do you really want to go on?[y/n]";

	$input = trim(fgets(STDIN));
	if($input == 'n' || $input == 'N') {
		echo "\n";
		exit;
	}
	if($input == 'y' || $input == 'Y') break;
}

//Delete realm ids
echo "\nRemoving \"User authentification realms\" ...";
$sql = "DELETE FROM `user_auth_realm` WHERE `realm_id` = 800 OR `realm_id` = 801 OR `realm_id` = 802";
db_execute($sql);

echo "\nRemoving \"Settings\" ...";
$sql = "DELETE FROM `settings` WHERE name like 'reportit%'";
db_execute($sql);

echo "\nRemoving \"Graph Settings\" ...";
$sql = "DELETE FROM `settings_graphs` WHERE name like 'reportit%'";
db_execute($sql);

//Delete existing tables
echo "\nRemoving tables ...";
foreach($result as $index => $arr) {
	foreach($arr as $tbl) {
		if(strpos( $tbl, 'reportit') !== FALSE) $tables[] = $tbl;
	}
}

foreach($tables as $table) {
	$sql = "DROP TABLE IF EXISTS $table";
	echo "\n\t$table";
	db_execute($sql);
}

echo "\n\n-Finished-\n\n";
