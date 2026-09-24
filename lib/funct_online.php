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

/**
 * Returns the current session user's user id. Called throughout this
 * plugin wherever the current user's id is needed.
 *
 * @return int|null The current user's session id, or null if not set.
 */
function my_id() {
	return $_SESSION['sess_user_id'];
}

/**
 * Returns the current session user's username. Called throughout this
 * plugin's UI wherever the current user's display name is needed.
 *
 * @return string|null The current user's username, or null if not
 *                     found.
 */
function my_name() {
	return db_fetch_cell_prepared('SELECT username
		FROM user_auth
		WHERE id = ?',
		[my_id()]);
}

/**
 * Access-control checkpoint: verifies the current user is permitted to
 * access a given report, terminating the request with an error page if
 * not - the report must not exist error out for non-admins, and
 * non-owners are only allowed through if they're an admin or the
 * report is public and $public was requested. Called at the top of
 * report edit/view/action handlers before operating on a report.
 *
 * @param int  $report_id The report id to check access for; a
 *                        non-numeric or zero value skips the check
 *                        (e.g. for a not-yet-created report).
 * @param bool $public    Whether public reports should be allowed for
 *                        non-owner, non-admin users.
 *
 * @return void
 */
function my_report($report_id, $public = false) {
	if (is_numeric($report_id) && $report_id != 0) {
		$user_id  = my_id();

		$user = db_fetch_row_prepared('SELECT user_id, public
			FROM plugin_reportit_reports
			WHERE id = ?',
			[$report_id]);

		if ($user == false) {
			if (!re_admin()) {
				die_html_custom_error('Permission denied');
			}
			die_html_custom_error('Not existing');
		}

		if ($user_id !== $user['user_id']) {
			if (re_admin()) {
				return;
			}

			if ($public && $user['public'] == 'on') {
				return;
			}
			die_html_custom_error('Permission denied');
		}
	}
}

/**
 * Looks up the template id a given report is based on. Called wherever
 * a report's associated template needs to be resolved.
 *
 * @param int $report_id The report id to look up.
 *
 * @return int|null The associated plugin_reportit_templates id, or
 *                  null if not found.
 */
function my_template($report_id) {
	return db_fetch_cell_prepared('SELECT template_id
		FROM plugin_reportit_reports
		WHERE id = ?',
		[$report_id]);
}

/**
 * Access-control checkpoint: terminates the request with an error page
 * if the given template is locked. Called before allowing edits to a
 * template's measurands/data source items.
 *
 * @param int  $template_id The template id to check.
 * @param bool $header      Passed through to die_html_custom_error() if
 *                          the template is locked.
 *
 * @return void
 */
function locked($template_id, $header = true) {
	$status = db_fetch_cell_prepared('SELECT locked
		FROM plugin_reportit_templates
		WHERE id = ?',
		[$template_id]);

	if ($status) {
		die_html_custom_error('Template has been locked', true);
	}
}

/**
 * Looks up another user's username by id. Called when displaying a
 * report's/template's owner where that owner is not the current user.
 *
 * @param int $userid The user id to look up.
 *
 * @return string|null The user's username, or null if not found.
 */
function other_name($userid) {
	return db_fetch_cell_prepared('SELECT username FROM user_auth WHERE id = ?', [$userid]);
}

/**
 * Determines whether the current user should be restricted to the
 * view-only interface: true if they hold neither the admin nor owner
 * realm, or if the current request is already for view.php. Called by
 * navigation/permission-gating code to decide which UI to present.
 *
 * @return bool True if the user is view-only (or already viewing
 *              view.php), false otherwise.
 */
function only_viewer() {
	$id = my_id();

	$report_viewer = db_fetch_cell("SELECT *
		FROM user_auth_realm
		WHERE user_id = $id
		AND (realm_id = " . REPORTIT_USER_ADMIN . '
		OR realm_id = ' . REPORTIT_USER_OWNER . ')');

	if ($report_viewer == null || substr_count($_SERVER['REQUEST_URI'], 'view.php')) {
		return true;
	} else {
		return false;
	}
}

/**
 * Checks whether a user has a given realm permission, either directly
 * or via membership in a user group that has the realm. Called from
 * re_owner()/re_admin() to resolve the ReportIt owner/admin realm
 * permissions.
 *
 * @param int $realm_id The realm id to check.
 * @param int $user_id  The user id to check.
 *
 * @return bool True if the user (directly or via a group) has the
 *              realm, false otherwise.
 */
function user_auth_realm($realm_id, $user_id) {
	$verified = db_fetch_cell('
		SELECT user_auth.id FROM user_auth
			LEFT JOIN (
				SELECT user_id from user_auth_realm WHERE realm_id = ' . $realm_id . '
			) AS user_realm ON user_auth.id = user_realm.user_id
			LEFT JOIN (
				SELECT user_auth_group_members.user_id FROM user_auth_group_members
				INNER JOIN user_auth_group_realm ON
					user_auth_group_members.group_id = user_auth_group_realm.group_id
				WHERE user_auth_group_realm.realm_id = ' . $realm_id . '
			) AS group_member ON group_member.user_id = user_auth.id
			WHERE user_auth.id = ' . $user_id . ' AND (group_member.user_id IS NOT NULL OR user_realm.user_id IS NOT NULL)'
	);

	return ($verified) ? true : false;
}

/**
 * Checks whether the current user holds the ReportIt 'Create Reports'
 * (owner) realm. Called throughout this plugin to gate report-creation/
 * editing features.
 *
 * @return bool True if the current user is a report owner, false
 *              otherwise.
 */
function re_owner() {
	return user_auth_realm(REPORTIT_USER_OWNER, my_id()) ? true : false;
}

/**
 * Checks whether the current user holds the ReportIt 'Manage Reports'
 * (admin) realm. Called throughout this plugin to gate
 * administrative/template-management features.
 *
 * @return bool True if the current user is a ReportIt admin, false
 *              otherwise.
 */
function re_admin() {
	return user_auth_realm(REPORTIT_USER_ADMIN, my_id()) ? true : false;
}

/**
 * Records a custom validation error message for a specific form field
 * in the session (keeping only the first message set), optionally
 * raising a top-level Cacti message code too. Called from validation
 * code throughout this plugin when a specific field fails validation.
 *
 * @param string    $field            The form field name the error
 *                                    applies to.
 * @param string    $custom_message   The custom error message text.
 * @param int|false $toplevel_message A Cacti message code to also raise
 *                                    via raise_message(), or false to
 *                                    skip.
 *
 * @return void
 */
function session_custom_error_message($field, $custom_message, $toplevel_message = 2) {
	$_SESSION['sess_error_fields'][$field] = $field;

	// Do not overwrite the first message.
	if (!isset($_SESSION['sess_custom_error'])) {
		$_SESSION['sess_custom_error'] = $custom_message;
	}

	if (!isset($_SESSION['sess_messages']) && $toplevel_message !== false) {
		raise_message($toplevel_message);
	}
}

/**
 * Displays and clears a pending custom error message previously
 * recorded via session_custom_error_message(). Called at the top of
 * form-rendering pages to surface validation errors from a prior
 * submission.
 *
 * @return void
 */
function session_custom_error_display() {
	if (isset($_SESSION['sess_custom_error'])) {
		display_custom_error_message($_SESSION['sess_custom_error']);
		kill_session_var('sess_custom_error');
	}
}

/**
 * Checks whether a specific form field has a recorded validation error
 * from session_custom_error_message(). Called from form-rendering code
 * to highlight/flag individual invalid fields.
 *
 * @param string $field The form field name to check.
 *
 * @return bool True if the field has a recorded error, false
 *              otherwise.
 */
function is_error_message_field($field) {
	if (isset($_SESSION['sess_error_fields'][$field])) {
		return true;
	} else {
		return false;
	}
}

/**
 * Determines whether a template is eligible to be auto-locked, i.e. it
 * currently has no defined measurands. Called from try_autolock_template()
 * before deciding whether to lock a template.
 *
 * @param int $template_id The template id to check.
 *
 * @return bool True if the template has zero measurands (eligible for
 *              auto-lock), false otherwise.
 */
function stat_autolock_template($template_id) {
	$count = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM plugin_reportit_measurands
		WHERE template_id = ?',
		[$template_id]);

	if ($count != 0) {
		return false;
	} else {
		return true;
	}
}

/**
 * Marks a template as locked. Called from try_autolock_template() once
 * it's determined a template should be locked.
 *
 * @param int $template_id The template id to lock.
 *
 * @return void
 */
function set_autolock_template($template_id) {
	db_execute_prepared('UPDATE plugin_reportit_templates
		SET locked=1
		WHERE id = ?',
		[$template_id]);
}

/**
 * Bulk-updates the calc_formula field for a set of measurands. Called
 * after a template's data source aliases change in a way that requires
 * rewriting its measurands' calculation formulas.
 *
 * @param array $array A list of associative arrays each containing 'id'
 *                     and 'calc_formula' for a measurand to update.
 *
 * @return void
 */
function update_formulas($array) {
	foreach ($array as $key => $value) {
		db_execute_prepared('UPDATE plugin_reportit_measurands
			SET calc_formula = ?
			WHERE id = ?',
			[$value['calc_formula'], $value['id']]);
	}
}

/**
 * Automatically locks a template if it's eligible (no measurands) and
 * has no reports currently in a running state. Called after template
 * edits that might leave it in a state requiring a lock (e.g. removing
 * its last measurand).
 *
 * @param int $template_id The template id to attempt to auto-lock.
 *
 * @return bool True if the template was locked, false if it wasn't
 *              eligible (has running reports).
 */
function try_autolock_template($template_id) {
	$status = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM plugin_reportit_reports
		WHERE template_id = ?
		AND state = 1',
		[$template_id]);

	if ($status == 0) {
		set_autolock_template($template_id);

		return true;
	} else {
		return false;
	}
}

/**
 * Checks whether the running Cacti version's compatibility hash code is
 * at least the given required hash code. Called when validating an
 * imported template's declared Cacti version compatibility.
 *
 * @param int $hash The minimum required Cacti version hash code.
 *
 * @return bool True if the current Cacti version's hash code is
 *              greater than or equal to $hash, false otherwise.
 *
 * @global array $hash_version_codes Map of Cacti version string => hash
 *                                  code, used to resolve the running
 *                                  version's code.
 */
function check_cacti_version($hash) {
	global $hash_version_codes;

	if ($hash_version_codes[CACTI_VERSION] < $hash) {
		return false;
	} else {
		return true;
	}
}

/**
 * Verifies the PHP GD extension (with FreeType support) is available,
 * terminating the request with an error page if not - both are
 * required to render report graphs. Called before rendering any
 * graph-based report view.
 *
 * @return void
 */
function check_graph_support() {
	// Check required PHP extensions: GD Library and Freetype support
	$loaded_extensions = get_loaded_extensions();

	if (!in_array('gd', $loaded_extensions, true)) {
		die_html_custom_error('GD library not available - Check your systems configuration', true);
	}

	$gd_info = gd_info();

	if (!$gd_info['FreeType Support']) {
		die_html_custom_error('GD Freetype Support not available - Check your systems configuration', true);
	}
}

/**
 * Resolves the effective maximum-rows setting for the current user's
 * session, falling back to the configured default if the session value
 * is missing or invalid. Called when rendering report list/table views
 * that need a row-count limit.
 *
 * @return int The effective maximum row count to use.
 */
function get_valid_max_rows() {
	// return the default if a user defined an invalid value for maximum number of rows
	$session_max_rows = read_graph_config_option('reportit_max_rows');

	if (is_numeric($session_max_rows) && $session_max_rows > 0) {
		return $session_max_rows;
	} else {
		return read_default_graph_config_option('reportit_max_rows');
	}
}
