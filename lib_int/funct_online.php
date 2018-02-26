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

function my_id() {
	return $_SESSION['sess_user_id'];
}


function my_name() {
	return db_fetch_cell_prepared('SELECT username
		FROM user_auth
		WHERE id = ?',
		array(my_id()));
}


function my_report($report_id, $public = FALSE){
	if (is_numeric($report_id) && $report_id != 0) {
		$user_id  = my_id();

        $user = db_fetch_row_prepared("SELECT user_id, public
			FROM reportit_reports
			WHERE id = ?",
			array($report_id));

        if ($user == false) {
	    	if (!re_admin()) die_html_custom_error('Permission denied');
		    die_html_custom_error('Not existing');
		}

		if ($user_id !== $user['user_id']) {
			if (re_admin()) return;
			if ($public && $user['public'] == 1) return;
			die_html_custom_error('Permission denied');
		}
	}
}

function my_template($report_id) {
	return db_fetch_cell_prepared('SELECT template_id
		FROM reportit_reports
		WHERE id = ?',
		array($report_id));
}

function locked($template_id, $header=true) {
	$status = db_fetch_cell_prepared("SELECT locked
		FROM reportit_templates
		WHERE id = ?",
		array($template_id));

	if ($status) {
		die_html_custom_error('Template has been locked', true);
	}
}

function other_name($userid) {
	return db_fetch_cell_prepared("SELECT username FROM user_auth WHERE id = ?", array($userid));
}

function only_viewer() {
	$id = my_id();

	$report_viewer = db_fetch_cell("SELECT *
		FROM user_auth_realm
		WHERE user_id = $id
		AND (realm_id = " . REPORTIT_USER_ADMIN . "
		OR realm_id = " . REPORTIT_USER_OWNER . ")");

	if ($report_viewer == null || substr_count($_SERVER['REQUEST_URI'], 'cc_view.php')) {
		return true;
	} else {
		return false;
	}
}

function re_owner(){
	$id = my_id();

	$report_owner = db_fetch_cell("SELECT *
		FROM user_auth_realm
		WHERE realm_id = " . REPORTIT_USER_OWNER . "
		AND user_id = $id");

	if ($report_owner == REPORTIT_USER_OWNER) {
		return true;
	} else {
		return false;
	}
}

function re_admin() {
	$id = my_id();

	$report_admin = db_fetch_cell("SELECT *
		FROM user_auth_realm
		WHERE realm_id = " . REPORTIT_USER_ADMIN . "
		AND user_id = $id");

	if ($report_admin == REPORTIT_USER_ADMIN) {
		return true;
	} else {
		return false;
	}
}

function session_custom_error_message($field, $custom_message, $toplevel_message=2) {
	$_SESSION['sess_error_fields'][$field] = $field;

	//Do not overwrite the first message.
	if (!isset($_SESSION['sess_custom_error'])) {
		$_SESSION['sess_custom_error'] = $custom_message;
	}

	if (!isset($_SESSION['sess_messages']) && $toplevel_message !== false) {
		raise_message($toplevel_message);
	}
}

function session_custom_error_display() {
	if (isset($_SESSION['sess_custom_error'])) {
		display_custom_error_message($_SESSION['sess_custom_error']);
		kill_session_var('sess_custom_error');
	}
}

function is_error_message_field($field) {
	if (isset($_SESSION['sess_error_fields'][$field])) {
		return true;
	} else {
	    return false;
	}
}

function stat_autolock_template($template_id) {
    $count = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM reportit_measurands
		WHERE template_id = ?",
		array($template_id));

    if ($count != 0) {
		return false;
	} else {
    	return true;
	}
}

function set_autolock_template($template_id) {
    db_execute_prepared('UPDATE reportit_templates
		SET locked=1
		WHERE id = ?',
		array($template_id));
}

function update_formulas($array) {
    foreach($array as $key => $value) {
		db_execute_prepared('UPDATE reportit_measurands
			SET calc_formula = ?
			WHERE id = ?',
			array($value['calc_formula'], $value['id']));
    }
}

function try_autolock_template($template_id) {
    $status = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM reportit_reports
		WHERE template_id = ?
		AND in_process = 1',
		array($template_id));

    if ($status == 0) {
		set_autolock_template($template_id);
		return true;
    } else {
		return false;
    }
}

function check_cacti_version($hash){
	global $config, $hash_version_codes;

	if ($hash_version_codes[$config['cacti_version']] < $hash) {
		return false;
	} else {
		return true;
	}
}

function check_graph_support(){
	/* Check required PHP extensions: GD Library and Freetype support */
	$loaded_extensions = get_loaded_extensions();
	if (!in_array('gd', $loaded_extensions)) {
		die_html_custom_error("GD library not available - Check your systems configuration", true);
	}

	$gd_info = gd_info();
	if (!$gd_info["FreeType Support"]) {
		die_html_custom_error("GD Freetype Support not available - Check your systems configuration", true);
	}
}

function get_valid_max_rows(){
	/* return the default if a user defined an invalid value for maximum number of rows */
	$session_max_rows = read_graph_config_option("reportit_max_rows");

	if (is_numeric($session_max_rows) && $session_max_rows > 0) {
		return $session_max_rows;
	} else {
		return read_default_graph_config_option("reportit_max_rows");
	}
}

