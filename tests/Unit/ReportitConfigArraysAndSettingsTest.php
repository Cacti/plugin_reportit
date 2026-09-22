<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for reportit_config_arrays() and reportit_config_settings()
 * in setup.php.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']                 = [];
	$GLOBALS['__test_enabled_plugins']          = [];
	$GLOBALS['__test_auth_augment_roles_calls'] = [];
});

it('does not add the reportit menu entries when the plugin is not enabled', function () {
	global $menu, $messages;

	$menu     = [__('Management') => [], __('Templates') => []];
	$messages = [];

	reportit_config_arrays();

	expect($menu[__('Management')])->not->toHaveKey('plugins/reportit/reportit.php');
	expect($GLOBALS['__test_auth_augment_roles_calls'])->toHaveCount(3);
});

it('adds the reportit menu entries and error messages once the plugin is enabled', function () {
	global $menu, $messages;

	$menu     = [__('Management') => [], __('Templates') => []];
	$messages = [];

	reportit_test_set_plugin_enabled('reportit');

	reportit_config_arrays();

	expect($menu[__('Management')])->toHaveKey('plugins/reportit/reportit.php');
	expect($menu[__('Templates')])->toHaveKey('plugins/reportit/templates.php');
	expect($messages)->toHaveKey('reportit_templates__1');
	expect($messages)->toHaveKey('reportit_templates__2');
	expect($messages)->toHaveKey('reportit_templates__3');
});

it('adds the reports tab and settings when no settings exist yet', function () {
	global $tabs, $tabs_graphs, $settings, $settings_user, $item_rows;

	$tabs          = [];
	$tabs_graphs   = [];
	$settings      = [];
	$settings_user = [];
	$item_rows     = [10, 25, 50];

	reportit_config_settings();

	expect($tabs['reports'])->toBe('Reports');
	expect($tabs_graphs['reportit'])->toBe('Report General Settings');
	expect($settings['reports'])->toHaveKey('reportit_met');
	expect($settings['reports'])->toHaveKey('reportit_view_filter');
	expect($settings_user['reportit'])->toHaveKey('reportit_max_rows');
});

it('merges into existing settings arrays without clobbering them', function () {
	global $tabs, $tabs_graphs, $settings, $settings_user, $item_rows;

	$tabs          = [];
	$tabs_graphs   = [];
	$settings      = ['reports' => ['other_setting' => ['friendly_name' => 'Other']]];
	$settings_user = ['reportit' => ['other_user_setting' => ['friendly_name' => 'Other']]];
	$item_rows     = [10, 25, 50];

	reportit_config_settings();

	expect($settings['reports'])->toHaveKey('other_setting');
	expect($settings['reports'])->toHaveKey('reportit_met');
	expect($settings_user['reportit'])->toHaveKey('other_user_setting');
	expect($settings_user['reportit'])->toHaveKey('reportit_max_rows');
});
