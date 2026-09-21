# GitHub Copilot Instructions

## Priority Guidelines

When generating code for this repository:

1. **Version Compatibility**: This is a Cacti plugin (`reportit`, version 3.0) targeting Cacti 1.3.0+
2. **Context Files**: Prioritize patterns and standards defined in this file (`.github/copilot-instructions.md`)
3. **Codebase Patterns**: When context files don't provide specific guidance, scan the codebase for established patterns
4. **Architectural Consistency**: Maintain plugin-based architecture extending Cacti core
5. **Code Quality**: Prioritize security, maintainability, and compatibility in all generated code

## Technology Stack

### Core Technologies
- **PHP**: Compatible with Cacti 1.3.x (develop) supported versions
- **Platform**: Cacti Plugin Architecture
- **Database**: MySQL/MariaDB with InnoDB engine
- **Reporting**: Generates tabular/archived reports from RRDfile data, optionally emailed to users

### Key Dependencies
- Cacti core framework (`api_plugin_*`, `db_*`, `CACTI_PATH_BASE`)
- `nosync` INFO directive excludes `exports, archive, cache, tmp, *.html, *.conf` from plugin packaging/sync

## Project Structure

```
reportit/                  # Repository root (install to plugins/reportit/ in Cacti)
├── cache/                  # Generated report/measurand/variable cache (not synced)
├── include/                 # Core includes
├── lib/                       # Supporting libraries
├── system/                     # install.php / upgrade.php system routines
├── tmp/                          # Temporary working files (not synced)
├── reportit.php                    # Main report administration UI
├── templates.php                     # Report template administration
├── view.php                            # Report viewing UI
├── poller_reportit.php                   # Background poller entry point (CLI)
├── INFO                                    # Plugin metadata (name, version, compat)
├── README.md
└── setup.php                                # Plugin install/uninstall/upgrade hooks
```

## Naming Conventions

### Function Names
- **Plugin lifecycle/hook-registration functions** MUST be prefixed `plugin_reportit_`: `plugin_reportit_install()`, `plugin_reportit_upgrade()`, `plugin_reportit_version()`.
- **All other functions** MUST be prefixed `reportit_`: `reportit_check_upgrade()`, `reportit_system_setup()`, `reportit_show_tab()`.
- Match the existing prefix used by the function you are editing; do not introduce a third naming scheme.

### Database Tables
All plugin tables are prefixed `plugin_reportit_` (see `plugin_reportit_uninstall()`):

```
plugin_reportit_cache_measurands, plugin_reportit_cache_reports,
plugin_reportit_cache_variables, plugin_reportit_data_items,
plugin_reportit_data_source_items, plugin_reportit_measurands,
plugin_reportit_presets, plugin_reportit_recipients, plugin_reportit_reports,
plugin_reportit_rvars, plugin_reportit_templates, plugin_reportit_variables,
plugin_reportit_data_template_groups
```

## Code Style

### Indentation and Formatting
- **Tabs**: Use tabs (not spaces) for indentation throughout all PHP files.
- **Braces**: Opening brace on the same line for functions and control structures.
- **Spacing**: Space after control structure keywords (`if`, `foreach`, `while`).

### File Headers
ALL PHP files MUST include the standard GPL v2 license header used throughout this repository (see `setup.php`), crediting "The Cacti Group".

## Security Standards

### SQL Query Security
Use prepared statements for anything involving variable input:

```php
// CORRECT
db_execute_prepared('UPDATE plugin_config SET name = ?, author = ?, webpage = ?, version = ? WHERE id = ?',
	array($info['longname'], $info['author'], $info['homepage'], $info['version'], $id));

// WRONG - never do this with request-derived values
db_fetch_row("SELECT * FROM plugin_reportit_reports WHERE id = $id");
```

### Input Validation
Use `get_filter_request_var()` / `get_nfilter_request_var()` for request input; never read `$_GET`/`$_POST` directly.

`get_filter_request_var()` (and its `gfrv()` shorthand, where available) called with only the
`$name` argument (no regex/filter as the 2nd/3rd argument) already validates the value as numeric
and returns it as a **string** -- it does not return an int, and it halts execution if the request
value is not numeric. Because of this, do NOT cast its output to `(int)` when the result is only
used for string output (e.g. `print`/`echo`, string concatenation, embedding in HTML/JS); the cast
is redundant. Only cast when the value is genuinely used in an integer/numeric context (e.g.
arithmetic, strict `===` comparisons).

## Database Operations

### Upgrade Handling
Version-gate schema changes in `reportit_check_upgrade()` (`setup.php`), comparing against the `plugin_config` row, and re-run install/upgrade routines from `system/install.php` / `system/upgrade.php` when the plugin is active:

```php
function reportit_check_upgrade() {
	$current = plugin_reportit_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='reportit'");

	if (cacti_sizeof($old) && $current != $old['version']) {
		if ($old['status'] == 1 || $old['status'] == 4) {
			require_once(CACTI_PATH_BASE . '/plugins/reportit/system/install.php');
			require_once(CACTI_PATH_BASE . '/plugins/reportit/system/upgrade.php');
			reportit_system_upgrade($old['version']);
			plugin_reportit_install();
		}
	}
}
```

## Internationalization

ALL user-facing strings MUST use `__()` with the `'reportit'` text domain.

## Plugin Architecture

### Plugin Hooks
Register all plugin hooks in `plugin_reportit_install()` (`setup.php`):

```php
api_plugin_register_hook('reportit', 'top_header_tabs',       'reportit_show_tab',             'setup.php');
api_plugin_register_hook('reportit', 'top_graph_header_tabs', 'reportit_show_tab',             'setup.php');
api_plugin_register_hook('reportit', 'draw_navigation_text',  'reportit_draw_navigation_text', 'setup.php');
api_plugin_register_hook('reportit', 'config_arrays',         'reportit_config_arrays',        'setup.php');
api_plugin_register_hook('reportit', 'config_settings',       'reportit_config_settings',      'setup.php');
api_plugin_register_hook('reportit', 'poller_bottom',         'reportit_poller_bottom',        'setup.php');
api_plugin_register_hook('reportit', 'clog_regex_array',      'reportit_clog_regex_array',     'setup.php');

api_plugin_register_realm('reportit', 'view.php,charts.php', 'ReportIt - Report Viewing', 1);
api_plugin_register_realm('reportit', 'reportit.php,view.php', 'ReportIt - Create Reports', 1);
api_plugin_register_realm('reportit', 'templates.php', 'ReportIt - Manage Reports', 1);
```

### Report Generation & Export
`run_report()`/`runtime()` in `reportit.php`/`view.php` generate archived reports; `autoexport()`/`autorrdlist()` and `export_to_<format>()` dynamic dispatch functions handle scheduled export formats — keep new export formats following the `export_to_*` naming pattern so the dynamic dispatch continues to work.

## Best Practices

1. Match the existing report/archive/export naming and directory conventions (`cache/`, `archive`, `tmp/`).
2. Keep new export formats named `export_to_<format>()` for the dynamic dispatch in `autoexport()`.
3. Wrap all user-facing strings with `__('text', 'reportit')`.
4. Avoid writing generated report/cache artifacts outside `cache/`/`tmp/`/archive locations already excluded via `nosync` in `INFO`.

## Common Pitfalls to Avoid

```php
// WRONG - building the export function name from unsanitized input
$export_function = 'export_to_' . $_REQUEST['format'];

// CORRECT - validate against a known allowlist first
$allowed = array('pdf', 'csv', 'html');
$format  = get_filter_request_var('format', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(' . implode('|', $allowed) . ')$/')));
$export_function = 'export_to_' . $format;
```

## Version Control

Document all changes in `CHANGELOG.md`; use descriptive commit messages referencing issue/PR numbers when applicable.

## CI & Dependency Baselines

- Do not commit a `composer.json` or `composer.lock` in this plugin's own repo root — the shared CI workflow installs Pest/dev dependencies into Cacti's own Composer-managed vendor tree (checked out alongside the plugin). Use Cacti's `composer.json`, not a plugin-local one.
- Do not add a plugin-local `.phpstan.neon`/`phpstan.neon` or `.php-cs-fixer.php`/`.php-cs-fixer.dist.php` — lint/static-analysis steps run against Cacti's own config from the Cacti core checkout, targeting this plugin's directory. Use the Cacti version, not a plugin-local config.
- Prefer Cacti's `cacti_count()`/`cacti_sizeof()` wrappers over the raw `count()`/`sizeof()` builtins in new or edited code.

## Internationalization (i18n)

- Translatable strings are managed with GNU gettext via `locales/build_gettext.sh`. `locales/po/cacti.pot` is the source template; Weblate owns syncing the per-language `.po`/`.mo` files from it.
- When a pull request adds or changes a string wrapped in `__()`/`__n()`/`__esc()`/`__x()`/`__xn()`/`__gettext()`, run `locales/build_gettext.sh` before pushing and add the resulting change to `locales/po/cacti.pot` only. Do not commit the regenerated per-language `.po`/`.mo` files in the same PR — Weblate takes care of the rest.

## References

- [Cacti main repo](https://github.com/Cacti/cacti)
- [Cacti Documentation](https://www.github.com/Cacti/documentation)
- `README.md` for feature descriptions
- `CHANGELOG.md` for version history

## Security & Quality Conventions

These conventions apply across the Cacti plugin fleet and should be followed whenever touching
existing code or adding new code, not just in dedicated cleanup passes:

- **No hardcoded third-party hosts.** Never hardcode a third-party IP address, hostname, or URL
  in plugin code (even for tooling/download helpers). Expose it as a plugin setting instead, with
  secure-by-default values (e.g. an SSL-verification setting that defaults to verify-on).
- **Prepared statements over `db_qstr()`.** Build dynamic `WHERE` clauses using the
  `$sql_where`/`$sql_params` prepared-statement pattern, not string concatenation via `db_qstr()`.
- **Use `html_escape_request_var()`.** Prefer it over the `html_escape(get_request_var(...))` call
  chain.
- **Harden `unserialize()`.** Always pass `['allow_classes' => false]` as the second argument.
- **i18n text domain.** Every `__()`/`__esc()` call must include this plugin's text domain as the
  final argument, except when deliberately comparing against a literal, untranslated Cacti-core
  label.
- **Plugin table-creation API.** Use `api_plugin_db_table_create()`/`api_plugin_db_add_column()`
  (from Cacti core's `lib/plugins.php`) instead of raw `CREATE TABLE`/`ALTER TABLE ... ADD COLUMN`.
  Both are idempotent (safe no-ops when already applied), so the same call can run unconditionally
  from both the install AND upgrade paths.
- **PHPDoc shape.** Every function gets a PHPDoc block: a one-line description, a blank comment
  line, `@param` lines, a blank comment line, then `@return`. Infer parameter/return types from
  actual usage; don't change the function's real type-hints in the same pass (let static analysis
  flag mismatches separately). Skip vendored third-party library files.
