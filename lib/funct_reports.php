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
 * Disables scheduling for a report. Called from the report list's bulk
 * 'disable' action.
 *
 * @param int $id The plugin_reportit_reports id to disable.
 *
 * @return void
 */
function api_reportit_disable_report($id) {
	db_execute_prepared('UPDATE plugin_reportit_reports
		SET enabled = ""
		WHERE id = ?',
		[$id]);
}

/**
 * Enables scheduling for a report. Called from the report list's bulk
 * 'enable' action.
 *
 * @param int $id The plugin_reportit_reports id to enable.
 *
 * @return void
 */
function api_reportit_enable_report($id) {
	db_execute_prepared('UPDATE plugin_reportit_reports
		SET enabled = "on"
		WHERE id = ?',
		[$id]);
}

/**
 * Deletes a report and all of its associated data (presets, run
 * variables, recipients, data items) as well as its dedicated results
 * table. Called from the report list's bulk 'delete' action.
 *
 * @param int $id The plugin_reportit_reports id to delete.
 *
 * @return void
 */
function api_reportit_delete_report($id) {
	$counter_data_items += db_fetch_cell_prepared('SELECT COUNT(*)
		FROM plugin_reportit_data_items
		WHERE report_id = ?',
		[$id]);

	db_execute_prepared('DELETE FROM plugin_reportit_reports WHERE id = ?', [$id]);
	db_execute_prepared('DELETE FROM plugin_reportit_presets WHERE id = ?', [$id]);
	db_execute_prepared('DELETE FROM plugin_reportit_rvars WHERE report_id = ?', [$id]);
	db_execute_prepared('DELETE FROM plugin_reportit_recipients WHERE report_id = ?', [$id]);
	db_execute_prepared('DELETE FROM plugin_reportit_data_items WHERE report_id = ?', [$id]);
	db_execute('DROP TABLE IF EXISTS plugin_reportit_results_' . $id);
}

/**
 * Duplicates a report as a new report, copying its data items, preset
 * settings, and recipient list, with the new name derived from a
 * caller-supplied title format containing a '<report_title>'
 * placeholder. Called from the report list's bulk 'duplicate' action.
 *
 * @param int    $id       The plugin_reportit_reports id to duplicate.
 * @param string $addition The title format for the new report's name,
 *                         with '<report_title>' replaced by the
 *                         original report's name.
 *
 * @return void
 */
function api_reportit_duplicate_report($id, $addition) {
	// ================= input validation =================
	input_validate_input_number($id);
	// ====================================================

	$report_data = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_reports
		WHERE id = ?', [$id]);

	$report_data['id']   = 0;
	$report_data['name'] = str_replace('<report_title>', $report_data['name'], $addition);

	$new_id = sql_save($report_data, 'plugin_reportit_reports');

	// Copy original rrdlist table  to new rrdlist table
	$data_items = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_data_items
		WHERE report_id = ?',
		[$id]);

	if (cacti_sizeof($data_items)) {
		foreach ($data_items as $data_item) {
			$data_item['report_id'] = $new_id;
			sql_save($data_item, 'plugin_reportit_data_items', ['id', 'report_id'], false);
		}
	}

	// duplicate the presets settings
	$report_presets = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_presets
		WHERE id = ?',
		[$id]);

	$report_presets['id'] = $new_id;

	sql_save($report_presets, 'plugin_reportit_presets', 'id', false);

	// duplicate list of recipients
	$report_recipients = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_recipients
		WHERE report_id = ?',
		[$id]);

	if (cacti_sizeof($report_recipients)) {
		foreach ($report_recipients as $recipient) {
			$recipient['id']        = 0;
			$recipient['report_id'] = $new_id;

			sql_save($recipient, 'plugin_reportit_recipients');
		}
	}
}

/**
 * Launches a background poller_reportit.php process to run a single
 * report immediately. Called from the report list's/report edit
 * page's 'run now' action.
 *
 * @param int $id The plugin_reportit_reports id to run.
 *
 * @return void
 */
function api_reportit_run_report($id) {
	$php_binary = read_config_option('path_php_binary');

	// ================= input validation =================
	input_validate_input_number($id);
	// ====================================================

	if ($id > 0) {
		exec_background($php_binary, CACTI_PATH_BASE . '/plugins/reportit/poller_reportit.php --report-id=' . $id);
	}
}

/**
 * Transfers ownership of a report to a different user. Called from the
 * report list's/report edit page's ownership-transfer action.
 *
 * @param int $id   The plugin_reportit_reports id whose owner is being
 *                  changed.
 * @param int $user The user id to assign as the new owner.
 *
 * @return void
 */
function api_reportit_take_ownership($id, $user) {
	db_execute_prepared('UPDATE plugin_reportit_reports SET user_id = ? WHERE id = ?', [$user, $id]);
}

/**
 * Removes one or more data source items from a report's RRD/graph item
 * list. Called from the report's items tab bulk 'delete' action.
 *
 * @param int   $id    The report id whose data items should be
 *                      removed from.
 * @param array $items The list of plugin_reportit_data_items ids to
 *                      remove.
 *
 * @return void
 */
function api_reportit_remove_data_sources($id, $items) {
	$rrdlist_datas = db_fetch_assoc_prepared('SELECT id
		FROM plugin_reportit_data_items
		WHERE report_id = ?
		AND ' . array_to_sql_or($items, 'id'),
		[$id]);

	if (cacti_sizeof($rrdlist_datas)) {
		foreach ($rrdlist_datas as $rrdlist_data) {
			db_execute_prepared('DELETE FROM plugin_reportit_data_items
				WHERE report_id = ?
				AND id = ?',
				[$id, $rrdlist_data['id']]);
		}
	}
}

/**
 * Adds newly selected RRD/graph items to a report's data item list,
 * applying the report's configured item presets (or a minimal default
 * row set if no presets exist) to each newly inserted item. Called from
 * the report's items tab 'add' action.
 *
 * @param int $id The report id to add data source items to.
 *
 * @return void
 */
function api_reportit_add_data_source($id) {
	$enable_tmz	 = read_config_option('reportit_use_tmz');
	$tmz		       = ($enable_tmz) ? "'GMT'" : "'" . date('T') . "'";
	$columns	    = '';
	$values		    = '';
	$rrd 		      = '';

	// load data item presets
	$presets = db_fetch_row_prepared('SELECT *
		FROM plugin_reportit_presets
		WHERE id = ?',
		[$id]);

	if (cacti_sizeof($presets)) {
		$presets['report_id'] = $id;

		foreach ($presets as $key => $value) {
			$columns .= ', ' . $key;

			if ($key != 'id') {
				$values .= ',' . db_qstr($value);
			}
		}
	} else {
		$columns = ' id, report_id';
		$values .= ', ' . db_qstr($id);
	}

	foreach ($selected_items as $rd) {
		$rrd .= "($rd $values),";
	}

	$rrd     = substr($rrd, 0, strlen($rrd) - 1);
	$columns = substr($columns, 1);

	// save
	db_execute("REPLACE INTO plugin_reportit_data_items ($columns) VALUES $rrd");
}

/**
 * Bulk-updates the timespan fields (start/end day and time, timezone)
 * on all of a report's data items from a single reference item's
 * values. Called from the report's items tab 'apply to all' action.
 *
 * @param int    $id               The report id whose data items should
 *                                 be updated.
 * @param string $reference_items  A serialized array whose first
 *                                 element supplies the reference
 *                                 timespan values.
 *
 * @return void
 */
function api_reportit_update_data_source($id, $reference_items) {
	$reference_items = unserialize(stripslashes($reference_items), ['allowed_classes' => false]);

	db_execute_prepared('UPDATE plugin_reportit_data_items
		SET start_day = ?, end_day = ?, start_time = ?, end_time = ?, timezone = ?
		WHERE report_id = ?',
		[
			$reference_items[0]['start_day'],
			$reference_items[0]['end_day'],
			$reference_items[0]['start_time'],
			$reference_items[0]['end_time'],
			$reference_items[0]['timezone'],
			$id
		]
	);
}
