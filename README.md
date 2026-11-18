# ReportIt

ReportIt creates tabular reports from RRD data using configurable templates,
measurands and reporting periods. The 3.0 development branch uses Cacti’s report
queue for scheduled execution and notification delivery, including notification
lists, direct email recipients and BCC.

## Versions and compatibility

This README describes the `develop` source branch.

| Code | Plugin version | Minimum Cacti declared in `INFO` |
| --- | --- | --- |
| [Source branch](INFO) | 3.0 | 1.3.0 |
| [Published v1.1.4](https://github.com/Cacti/plugin_reportit/releases/tag/v1.1.4) | 1.1.4 | 1.2.27 |

Use the published release’s assets and tagged documentation when installing that
package. Branch code can include later changes, even when its version number
matches the release. Cacti’s own PHP and database requirements also apply.

Install this plugin in **`<cacti-root>/plugins/reportit`**, using the directory
name `reportit` rather than `plugin_reportit`.

See [CHANGELOG.md](CHANGELOG.md) for the version history.

## Developers

* Andreas Braun (aka browniebraun)

* Mark Brugnoli-Vinten (aka netniV)

* Larry Adams (aka TheWitness)

## Features

Templates, data items, work schedules, time zones, SI-prefix formatting and
scheduled reporting are provided by this branch. The list below also records
legacy 1.x capabilities. Version 3.0 removes the earlier automatic archive/export
workflow in favor of Cacti’s report log; existing legacy archive/export helpers
do not establish that the old workflow is supported in 3.0. See the changelog
and the tagged README for the published 1.1.4 package.

### Legacy feature list

* Definition of individual report templates by using measurands and variables in
  a mathematical way

* Definition of report configurations depending on report templates and with
  different data items

* Individual configuration of working days, working time, timezone and subheads
  per data item

* Scheduled reporting with sliding time frames

* Provides rounding with binary or decimal SI-Prefixes

* Export to CSV, SML and XML

* Different ways of connecting RRDtool

* Working in localtime supports change to DST and vice versa

* Automatic dispatch of scheduled reports via email

* Generation of top 10 charts

* Report history * Report per email

* Autoexport to a dedicated folder

## Requirements

Before you install "ReportIt", check the following requirements:

* **This 3.0 source branch: Cacti 1.3.0 or later**, as declared in `INFO`.
* **Published 1.1.4: Cacti 1.2.27 or later**, as declared in its release tag.
* **This source branch: PHP 8.3 or later**, as required by
  [composer.json](composer.json). Cacti's own extension and database
  requirements also apply.

The following 0.x migration notes describe legacy versions, not a verified
direct upgrade path to the 3.0 development branch. Back up the database and
existing reports before planning a migration.

To upgrade the legacy release, ReportIt 0.4.0 or higher is required.

Versions before 0.4.0 used a different migration procedure. The old
`uninstall.php` helper is not included in this branch. Preserve a database and
report archive backup, and use the source release's migration instructions
instead of treating an uninstall as a current upgrade step.

Optional: To use the full set of ReportIt's functionalities additional
extensions are required

* Fast report generation: PHP extension 'php_rrdtool'

## Cacti compatibility

The Cancel buttons on ReportIt's forms rely on Cacti core binding the
`cactiReturnTo` class in `include/layout.js`. The 1.2.x line (1.2.31 and later)
ships this binding, but not every release does — notably the current
1.3.0/`develop` line, whose refactored `include/layout.js` no longer binds the
`.cactiReturnTo` class. If your Cacti release's `include/layout.js` has no
`.cactiReturnTo` click handler, add the binding below to its `applySkin()`
function so the Cancel buttons work:

```js
$(document).off('click.cactiReturnTo', '.cactiReturnTo')
    .on('click.cactiReturnTo', '.cactiReturnTo', function(event) {
        event.preventDefault();
        cactiReturnTo($(this).attr('data-url'));
    });
```

## Installation

The Installation of ReportIt is similar to other plugins:

* Unpack the tar-file into the plugins folder. It contains a folder called
  "reportit".

* Go to the Plugin Management Console and click on "Install".

* Start Cacti and update your realm permissions under "Utilities/User
  Management".

* Update ReportIt's settings under "Configuration/Settings/Reports".

## Upgrade

**Legacy 1.x procedure:** the following instructions refer to the older
archive/export layout. For 3.0, review the development changelog and Cacti’s
report-log workflow before migration. Preserve existing archive/export data.


To upgrade an existing version of ReportIt please ensure that its release number
is v0.4.0 or above. It's strongly recommended to make a backup of your cacti
database and your ReportIt folder before! If you're using the default archive
folder of 0.6.x save it first!!!

After that replace your existing ReportIt folder with the new one stored in this
download archive.  Go to the plugin management console and click on "install".
The upgrade process will start automatically!  After it has finished you can
enable ReportIt with a click on "enable".

ReportIt does not require that your webserver has write access to any folder.
But if the history function should be in use, you will have to ensure that the
user, who executes the calculation of your scheduled reports via Crontab or
another scheduler, will have write access to the folders called "tmp" and
"archive".  Same has to be ensured for the export folder.  At least change to
the Cacti Webinterface and check the settings under
Console > Configuration > Settings > Reports.

## How to use

Console > Templates > Reports - create report template, enable publish, disable locked
Console > Management > Reports - create report from your template. Don't forget use "Add data items"
Use ReportIT tab
Manual is in file MANUAL.PDF

## Authors

ReportIt was created and written by Andreas Braun (browniebraun) with numerous
contributors over the years.  Recent upgrades seen other contributors helping
out including Jimmy Connor (cigamit), Mark Brugnoli-Vinten (netniV) and
Carlimeunier.

## Additional Help

If you need additional help, please visit [our
forums](http://forums.cacti.net/about19674.html)

## Possible Bugs

If you find any potential issues, please use [GitHub Issues](
https://github.com/cacti/plugin_reportit/issues/)

## Feature Requests

At first it's recommend to discuss your feature request with the Cacti
community.  After that you should open a feature request on
[GitHub](https://github.com/cacti/plugin_reportit/issues/)

## Future Changes

In the near future, we will be reducing the number of files
in this plugin to conform with the Cacti design specification
that places all of the functions of a plugin under common
base path names.  This will allow ReportIt to get along
better in the Cacti Console specifically.

We will also provide report history tracking and viewing
in an upcoming release.

-----------------------------------------------
Copyright (c) 2004-2026 - The Cacti Group, Inc.
