<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Integration coverage for plugin_reportit_install(): verifies every hook
 * and realm the plugin depends on at runtime are actually registered,
 * together with its full table set, in a single end-to-end pass.
 *
 * plugin_reportit_install() -> reportit_system_setup() require_once()s
 * system/install.php, a plugin-local file that only calls
 * api_plugin_db_table_create()/db_execute() (both stubbed), so this is
 * safe to run for real rather than being skipped.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_registered_hooks']  = [];
	$GLOBALS['__test_registered_realms'] = [];
	$GLOBALS['__test_db_calls']          = [];
});

it('registers every hook reportit depends on, its realms, and provisions its tables', function () {
	plugin_reportit_install();

	$hooks = [];
	foreach ($GLOBALS['__test_registered_hooks'] as $registered) {
		$hooks[$registered['hook']] = $registered;
	}

	foreach ([
		'top_header_tabs',
		'top_graph_header_tabs',
		'draw_navigation_text',
		'config_arrays',
		'config_settings',
		'poller_bottom',
		'clog_regex_array',
	] as $expected) {
		expect($hooks)->toHaveKey($expected);
		expect($hooks[$expected]['name'])->toBe('reportit');
		expect($hooks[$expected]['file'])->toBe('setup.php');
	}

	expect($GLOBALS['__test_registered_realms'])->toHaveCount(3);

	$sql = implode("\n", array_column(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute';
	}), 'sql'));

	foreach ([
		'plugin_reportit_cache_measurands',
		'plugin_reportit_cache_reports',
		'plugin_reportit_cache_variables',
	] as $cacheTable) {
		expect($sql)->toContain($cacheTable);
	}
});
