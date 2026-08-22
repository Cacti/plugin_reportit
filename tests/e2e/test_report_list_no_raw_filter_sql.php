<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

$path     = __DIR__ . '/../../reportit.php';
$contents = file_get_contents($path);

if ($contents === false) {
	fwrite(STDERR, "Unable to read reportit.php\n");
	exit(1);
}

$forbidden = [
	'value=\'<?php print get_request_var(\'filter\');?>\'',
	"WHERE a.name LIKE '%\" . get_request_var('filter') . \"%'",
	"' AND a.user_id =' . get_request_var('owner')",
	"' AND a.template_id =' . get_request_var('template')",
	"'reportit.php?filter=' . get_request_var('filter')",
];

foreach ($forbidden as $pattern) {
	if (strpos($contents, $pattern) !== false) {
		fwrite(STDERR, "Raw filter handling remains: {$pattern}\n");
		exit(1);
	}
}

print "OK\n";
