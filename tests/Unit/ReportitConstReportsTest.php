<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for lib/const_reports.php field descriptions. Text domain usage
 * is covered for the whole plugin by I18nTextDomainTest.php.
 */

it('loads report form definitions with corrected description wording', function () {
	global $sched_types, $heartbeats, $timezones;

	if (!defined('SCHEDULE_MANUAL')) {
		define('SCHEDULE_MANUAL', 1);
	}

	$sched_types = [];
	$heartbeats  = [];
	$timezones   = ['UTC' => 'UTC'];

	require_once CACTI_PATH_BASE . '/lib/api_scheduler.php';

	$owner_sql = '';
	require __DIR__ . '/../../lib/const_reports.php';

	expect($form_array_email)->toHaveKey('email_recipient');
	expect($form_array_email['email_recipient']['description'])
		->toContain('addresses/names')
		->toContain('delimiters:')
		->not->toContain('adresses')
		->not->toContain('delemiters');

	expect($form_array_presets)->toHaveKey('autorrdlist');
	expect($form_array_presets['autorrdlist']['description'])
		->toContain('filters. This will be called')
		->not->toContain('filters.This');
});
