<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for html_calc_syntax() in lib/funct_html.php: the calc-syntax
 * helper renders one anchor per usable variable token. The anchors use a
 * CSP-safe `reportitAddCalc` class and `data-calc-name` attribute (bound via a
 * delegated handler) rather than an inline onClick.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';

	// setup.php only defines REPORTIT_BASE_PATH at runtime inside
	// reportit_define_constants() (realm/DB work); define the same value here.
	if (!defined('REPORTIT_BASE_PATH')) {
		define('REPORTIT_BASE_PATH', dirname(__DIR__, 2));
	}

	require_once REPORTIT_BASE_PATH . '/lib/funct_shared.php';
	require_once REPORTIT_BASE_PATH . '/lib/funct_html.php';

	if (!function_exists('debug')) {
		function debug($data, $label = '') {}
	}
});

it('renders calc-syntax links with CSP-safe markup and no inline onClick', function () {
	global $calc_var_names, $rubrics;

	$calc_var_names = ['maxValue', 'calcVarA', 'calcVarB'];
	$rubrics        = [];

	$output = html_calc_syntax(0, 1);

	expect($output)->toContain('reportitAddCalc');
	expect($output)->toContain('data-calc-name=');
	expect($output)->not->toContain('onClick');
});
