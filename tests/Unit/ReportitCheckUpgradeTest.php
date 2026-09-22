<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for reportit_check_upgrade() in setup.php: the page-guard,
 * the "not installed yet" fallthrough, the already-up-to-date branch, and
 * the version-drift branch when the plugin's stored status does not
 * require a full schema upgrade.
 *
 * The version-drift branch for status IN (1, 4) is intentionally NOT
 * covered here: it require_once()s system/upgrade.php, which itself
 * require_once()s Cacti core's real lib/api_scheduler.php - a much larger
 * dependency surface than this suite stubs.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']            = [];
	$GLOBALS['__test_db_fetch_row_return'] = [];
	$GLOBALS['__test_registered_hooks']    = [];
	unset($_SERVER['PHP_SELF']);
});

it('short-circuits to true on pages that do not need the version check', function () {
	$_SERVER['PHP_SELF'] = '/cacti/graphs.php';

	expect(reportit_check_upgrade())->toBeTrue();
	expect($GLOBALS['__test_db_calls'])->toBeEmpty();
});

it('runs the check on an allowed page and reports true when nothing is installed yet', function () {
	$_SERVER['PHP_SELF'] = '/cacti/plugins.php';

	expect(reportit_check_upgrade())->toBeTrue();

	$writes = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return in_array($call['fn'], ['db_execute', 'db_execute_prepared'], true);
	});

	expect($writes)->toBeEmpty();
});

it('reports true and writes nothing when the stored version already matches', function () {
	$_SERVER['PHP_SELF'] = '/cacti/plugins.php';

	$info = plugin_reportit_version();
	reportit_test_set_db_fetch_row_return(['version' => $info['version'], 'status' => 1]);

	expect(reportit_check_upgrade())->toBeTrue();

	$writes = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return in_array($call['fn'], ['db_execute', 'db_execute_prepared'], true);
	});

	expect($writes)->toBeEmpty();
});

it('updates plugin_config when the version drifts and the stored status does not require a schema upgrade', function () {
	$_SERVER['PHP_SELF'] = '/cacti/plugins.php';

	reportit_test_set_db_fetch_row_return(['version' => '0.0.0', 'status' => 0]);

	expect(reportit_check_upgrade())->toBeTrue();

	// re-registering hooks/realms only happens for status IN (1, 4).
	expect($GLOBALS['__test_registered_hooks'])->toBeEmpty();

	$updates = array_values(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute_prepared' && stripos($call['sql'], 'UPDATE plugin_config') !== false;
	}));

	expect($updates)->toHaveCount(1);

	$info = plugin_reportit_version();

	expect($updates[0]['params'])->toBe([
		$info['longname'],
		$info['author'],
		$info['homepage'],
		$info['version'],
		'0',
	]);
});
