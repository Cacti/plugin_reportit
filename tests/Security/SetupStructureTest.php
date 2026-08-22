<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Verify setup.php defines required plugin hooks and info function.
 */

$setupPath = realpath(__DIR__ . '/../../setup.php');

if ($setupPath === false) {
	throw new RuntimeException('Unable to resolve setup.php for structure tests.');
}

$source = file_get_contents($setupPath);

if ($source === false) {
	throw new RuntimeException('Unable to read setup.php for structure tests.');
}

it('defines plugin_reportit_install function', function () use ($source) {
	expect($source)->toContain('function plugin_reportit_install');
});

it('defines plugin_reportit_version function', function () use ($source) {
	expect($source)->toContain('function plugin_reportit_version');
});

it('defines plugin_reportit_uninstall function', function () use ($source) {
	expect($source)->toContain('function plugin_reportit_uninstall');
});

it('returns version array with name key', function () use ($source) {
	expect($source)->toMatch('/[\'\""]name[\'\""]\s*=>/');
});

it('returns version array with version key', function () use ($source) {
	expect($source)->toMatch('/[\'\""]version[\'\""]\s*=>/');
});
