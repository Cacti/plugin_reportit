<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the plugin lifecycle contract functions in setup.php:
 * plugin_reportit_uninstall(), plugin_reportit_check_config(),
 * plugin_reportit_upgrade(), and reportit_upgrade_requirements().
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']              = [];
	$GLOBALS['__test_db_fetch_row_return']    = [];
	unset($_SERVER['PHP_SELF']);
});

it('drops every reportit table on uninstall', function () {
	expect(plugin_reportit_uninstall())->toBeTrue();

	$drops = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute' && stripos($call['sql'], 'DROP TABLE') !== false;
	});

	expect($drops)->toHaveCount(13);
});

it('reports the config check result from reportit_check_upgrade()', function () {
	// with no stored plugin_config row, reportit_check_upgrade() takes its
	// "not installed yet" fallthrough and returns true without writing anything.
	expect(plugin_reportit_check_config())->toBeTrue();

	$writes = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return in_array($call['fn'], ['db_execute', 'db_execute_prepared'], true);
	});

	expect($writes)->toBeEmpty();
});

it('always reports the upgrade as successful', function () {
	expect(plugin_reportit_upgrade())->toBeTrue();
});

it('reports upgrade requirements as always satisfied', function () {
	expect(reportit_upgrade_requirements())->toBeTrue();
});
