<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/*
 * Verify plugin source files do not use PHP 8.4-only syntax.
 * This plugin's floor version is PHP 8.3, matching the shared CI test
 * matrix (php: ['8.3', '8.4']) - this repo tracks Cacti's `develop` branch
 * rather than `1.2.x`, which requires PHP 8.3+.
 */

// Discovered recursively so new production PHP files are covered automatically.
$pluginRoot = realpath(__DIR__ . '/../..');

if ($pluginRoot === false) {
        throw new RuntimeException('Unable to resolve the plugin root directory');
}

$files = [];

$iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pluginRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
                continue;
        }

        $relativeFile = ltrim(str_replace($pluginRoot, '', $file->getPathname()), DIRECTORY_SEPARATOR);
        $relativeFile = str_replace(DIRECTORY_SEPARATOR, '/', $relativeFile);

        if (strpos($relativeFile, 'tests/') === 0 || strpos($relativeFile, 'vendor/') === 0 || strpos($relativeFile, 'include/vendor/') === 0) {
                continue;
        }

        $files[] = $relativeFile;
}

sort($files);

function plugin_test_read_source_file($relativeFile) {
        $path = realpath(__DIR__ . '/../../' . $relativeFile);

        if ($path === false) {
                throw new RuntimeException("Unable to resolve required plugin source: {$relativeFile}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
                throw new RuntimeException("Unable to read required plugin source: {$relativeFile}");
        }

        return $contents;
}

it('does not use asymmetric visibility (PHP 8.4)', function () use ($files) {
        foreach ($files as $relativeFile) {
                $contents = plugin_test_read_source_file($relativeFile);

                expect(preg_match('/\bprivate\s*\(\s*set\s*\)|\bprotected\s*\(\s*set\s*\)/', $contents))->toBe(0,
                        "{$relativeFile} uses asymmetric visibility which requires PHP 8.4"
                );
        }
});

it('does not use the #[Deprecated] attribute (PHP 8.4)', function () use ($files) {
        foreach ($files as $relativeFile) {
                $contents = plugin_test_read_source_file($relativeFile);

                expect(preg_match('/#\[\s*\\\\?Deprecated\b/i', $contents))->toBe(0,
                        "{$relativeFile} uses #[Deprecated] which requires PHP 8.4"
                );
        }
});

it('does not use array_find()/array_any()/array_all() (PHP 8.4)', function () use ($files) {
        foreach ($files as $relativeFile) {
                $contents = plugin_test_read_source_file($relativeFile);

                expect(preg_match('/\barray_(find|any|all)\s*\(/', $contents))->toBe(0,
                        "{$relativeFile} uses array_find()/array_any()/array_all() which requires PHP 8.4"
                );
        }
});

it('does not use each() (removed in PHP 8.0)', function () use ($files) {
        foreach ($files as $relativeFile) {
                $contents = plugin_test_read_source_file($relativeFile);

                expect(preg_match('/\beach\s*\(/', $contents))->toBe(0,
                        "{$relativeFile} uses each() which was removed in PHP 8.0"
                );
        }
});

it('does not use create_function() (removed in PHP 8.0)', function () use ($files) {
        foreach ($files as $relativeFile) {
                $contents = plugin_test_read_source_file($relativeFile);

                expect(preg_match('/\bcreate_function\s*\(/', $contents))->toBe(0,
                        "{$relativeFile} uses create_function() which was removed in PHP 8.0"
                );
        }
});

