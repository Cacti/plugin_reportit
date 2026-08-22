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

$checks = [
	"html_escape_request_var('filter')",
	"\$where_clauses[] = 'a.name LIKE ' . db_qstr('%' . get_request_var('filter') . '%');",
	"\$where_clauses[] = 'a.user_id = ' . (int) get_request_var('owner');",
	"\$where_clauses[] = 'a.template_id = ' . (int) get_request_var('template');",
	"\$affix = ' WHERE ' . implode(' AND ', \$where_clauses);",
	"rawurlencode(get_request_var('filter'))",
];

foreach ($checks as $check) {
	if (strpos($contents, $check) === false) {
		fwrite(STDERR, "Missing expected filter hardening: {$check}\n");
		exit(1);
	}
}

print "OK\n";
