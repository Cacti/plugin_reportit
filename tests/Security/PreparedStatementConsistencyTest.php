<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Verify migrated files use prepared DB helpers exclusively.
 * Catches regressions where raw db_execute/db_fetch_* calls creep back in.
 */

it('uses prepared DB helpers in migrated plugin files', function () {
	$targetFiles = array(
	'lib/funct_calculate.php',
	'lib/funct_export.php',
	'lib/funct_html.php',
	'lib/funct_validate.php',
	);

	$rawPattern = '/\bdb_(?:execute|fetch_row|fetch_assoc|fetch_cell)\s*\(/';
	$preparedPattern = '/\bdb_(?:execute|fetch_row|fetch_assoc|fetch_cell)_prepared\s*\(/';

	foreach ($targetFiles as $relativeFile) {
		$path = realpath(__DIR__ . '/../../' . $relativeFile);

		expect($path)->not->toBeFalse(
			"Failed to resolve target file {$relativeFile}"
		);

		$contents = file_get_contents($path);

		expect($contents)->not->toBeFalse(
			"Failed to read target file {$relativeFile}"
		);

		$lines = explode("\n", $contents);
		$rawCallsOutsideComments = 0;

		foreach ($lines as $line) {
			$trimmed = ltrim($line);

			if (strpos($trimmed, '//') === 0 || strpos($trimmed, '*') === 0 || strpos($trimmed, '#') === 0) {
				continue;
			}

			if (preg_match($rawPattern, $line) && !preg_match($preparedPattern, $line)) {
				$rawCallsOutsideComments++;
			}
		}

		expect($rawCallsOutsideComments)->toBe(0,
			"File {$relativeFile} contains raw (unprepared) DB calls"
		);
	}
});
