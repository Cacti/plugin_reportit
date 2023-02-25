# ReportIt

## Developers

* Andreas Braun (aka browniebraun)

* Mark Brugnoli-Vinten (aka netniV)

## Purpose

This plugin creates tabular reports which can be exported to CSV, SML and XML as
well.

## Features

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

* Cacti 1.1.11 or higher.

To upgrade "ReportIt" release 0.4.0 or higher is required.

A prior version (0.1, 0.2, 0.3.0, 0.3.1, 0.3.2 or 0.4.0a) has to be completely
uninstalled (mysql tables as well)!

Therefore you can use uninstall.php.

Optional: To use the full set of ReportIt's functionalities additional
extensions are required

* Fast report generation: PHP extension "php_rrdtool"

## Installation

The Installation of ReportIt is similar to other plugins:

* Unpack the tar-file into the plugins folder. It contains a folder called
  "reportit".

* Go to the Plugin Management Console and click on "Install".

* Start Cacti and update your realm permissions under "Utilities/User
  Management".

* Update ReportIt's settings under "Configuration/Settings/Reports".

## Upgrade

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
"Console->Settings->Reports".

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

## Changelog

--- develop ---

* issue: ReportIt constantly attempting to register realms

* feature: Message more elegantly when there are no Report Templates.

* security: Fix potential security exposure with unserialize() function

--- 1.1.3 ---

* issue#37: When adding a new template, changing the drop down box and clicking
  cancel results in 'Unsaved Changes' dialog

* issue#69: Report-based filters should be cleared when switching reports

* issue#71: Backported mailer() function uses function only defined in Cacti
  1.2+

* issue#72: Non well formed numeric value issued by pclzip.lib.php

* issue#75: Error 'Constant REPORTIT_BASE_PATH already defined' appears in logs

* issue#77: Wrong mailer() function is used when CACTI_VERSION is below 1.2.0

* issue#79: Issue adding recipient whilst the optional field is empty


--- 1.1.2 ---

* issue: Errors reported by rrdlist.php when plugin disabled

* issue#24: Unable to add email recipients to report

* issue#51: Report viewing doesn't honor separated "measurand"

* issue#56: Template shows Data template not available, when it is

* issue#61: Improve visual layout when viewing report

* issue#64: Running a scheduled report via crontab doesn't work

* issue#66: Fix to issue causing reportit plugin to error / disable

* Feature: Log reporting now utilises CLOG's regex parsing hooks to provide
  direct links to the report/data item from the CLOG tabs in a similar mode to
  the base Cacti methods

* Feature: Email format can be set to None, so no attachment but still get
  notification of report generation.

* Feature: Reintroduced Emailing Functionality

* Feature: Reintroduced Graphs (basic functionality)

* Feature: Reintroduced Subheaders

* Feature: Reintroduced Summary display

Note: This version uses @netniV's forked version of PHPGraphLib both of which
released under the MIT license for open source usage.

@netniV's Fork:
[https://github.com/netniv/phpgraphlib/](https://github.com/netniv/phpgraphlib/)

@elliottb's Original:
[https://github.com/elliottb/phpgraphlib/](https://github.com/elliottb/phpgraphlib)


--- 1.1.1 ---

* issue: Legacy database was incorrectly upgraded

* issue#58: REPORTIT_BASE_PATH is not defined when PLUGIN is disabled

* Feature: Add shutdown handler to report last formula used


--- 1.1.0 ---

* issue#30: Unable to export templates

* issue#46: Reports marked as public not available to Everyone

* issue#50: Data Source links do not show graphs

* issue#53: Database ON/OFF fields were being corrupted

* issue#54: Crashed report can never be run again

* issue: Upgrading never runs upgrade code once enabled

* issue: Summary does not display correctly

* feature: Allow multiple report templates to be exportable

* feature: Allow multiple report templates to be imported at once


--- 1.0.3 ---

* issue#39: Data Source combo appears in wrong location when creating new Report
  Template

* issue#43: Error [] operator not supported for strings

* issue#44: Message should be clearer when unable to add a report due to no
  templates available


--- 1.0.2 ---

* feature: PHP ZIP library pclzip.lib.php updated 2.8.2

* removed: Support of php_rrdtool extension

* removed: Support of RRDtool server

* removed: Support of Graidle

* issue#41: When creating a new Report Template, save fails and loses all data


--- 1.0.1 ---

* issue#2: Suppress display of 'Constant MAX_DISPLAY_PAGES is already defined'


--- 1.0.0 ---

* feature: Support for Cacti 1.x


--- 0.7.5 ---

* feature: Data Query Variables: List all available data query variables for use
  with a formula


--- 0.7.4 ---

* feature: Number of exported reports can be limited in a Round Robin style

* feature: Allow to create raw data exports. (ignore result formatting)

* bug#00122: Fix issues within boost detection if plugin has not been removed
  completely.

* bug#00121: Remove invalid test code.

* bug#00120: Fix issues with 10G interfaces if ifHighSpeed counter is not
  available.


--- 0.7.3 ---

* feature: New function f_high() returns the highest value out of bunch of given
  parameters like measurands, variables or static values.

* feature: New function f_low() returns the lowest value out of a list of
  numbers

* feature: Support boost's on demand RRD update

* bug#00119: Issues to export a report to CSV as well as Excel with IE

* bug#00118: RRDtool output "NaN" (excatly that writing style) has been
  converted to zero instead of NAN

* bug#00117: Unable to import report templates which are not based on Cacti's
  default data templates

* bug#00116: Design fails in FF due to invalid color declarations. (Thanks to
  Mule)

* bug#00115: Invalid formatting occured in automatical generated export files if
  SI-prefixes are in use

* bug#00114: Transformation of RRDtool output becomes invalid if decimal
  separator is not a point

* bug#00114: Debug file shows wrong memory usage information if memory limit is
  undefined.


--- 0.7.2 ---

* feature: Extend data formatting by different data types (e.g. BIN, FLOAT, INT,
  HEX, OCT ) and the allow to setup the precision

* feature: New function f_int added (thanks to patricko)

* feature: New function f_rnd added

* feature: Use real buttons instead of images

* feature: Add new data items up to the maximum limitation instead of aborting
  the auto generating process

* feature: Add new variable maxRRDValue and improve handling of maximum values

* bug#00113: Export file contained raw data instead of formatted results like
  show in the report view

* bug#00112: Search for data items failed by using an invalid SQL query

* bug#00111: Templates and reports with duplicate descriptions have been shown
  as only single entry in different dialogues.

* bug#00110: Fix internal issue if first data source item is excluded within the
  template configuration

* bug#00109: Copyright updated


--- 0.7.1 ---

* bug#00108: New contact address added to VERSION and README

* bug#00107: Javascript code missing for option new "Auto Generated Export"

* bug#00106: Include paths modified with regard to Cacti 0.8.8

* bug#00105: Modification of f_xth() to match with Cacti's results.

* bug#00104: External lib "pChart" removed

* bug#00103: Limitation of the maximum calculation runtime increased up to 9999
  seconds

* bug#00102: Default decimal separator changed from ',' to '.'

* bug#00101: Calculation process will cause issues if DST change occurs

* bug#00100: Missing indexes avoid saving individual configuration of data items


--- 0.7.0 ---

* feature: (SF Request 1933005) Auto export of scheduled reports to custom
  folder

* feature: Improvement of database structure avoids saving data items in
  separate tables

* feature: Auto Clean-up removes data items which do not exist any longer

* feature: Definition of a group title for all separate measurands will be
  supported

* feature: Export/Import function for report templates available

* feature: (SF Request 1997470) Data source items can be selected within a
  report template

* feature: Report templates are no longer bounded to a single consolidation
  function

* feature: Support of Plugin Architecture 2.x

* feature: Export format "SML" (Spreadsheet XML 2003) will be supported

* feature: Export to CSV, XML and SML can directly done from the report view

* feature: Calculation of 10 Gigabit Ethernet interfaces will be supported

* feature: User can define the max. number of rows for all tables shown in
  ReportIt

* feature: New arrows for sorting the tables

* feature: Report view: Sorted column gets a yellow background

* bug#00099: Generation of charts fails if user did not setup the default type
  before

* bug#00098: Invalid archives will be generated if data source name begins with
  a number

* bug#00097: Alternative path for saving a report archive without a slash at its
  end won't fit on Unix

* bug#00096: Dealing with database names containing a hyphen in failes

* bug#00095: Input field "Unit" supports not enough characters

* bug#00094: Some "chancel" buttons were missing for a better workflow

* bug#00093: Rounding does not work with negative values

* bug#00092: Time frame shown in RDDgraph is always equal to the latest
  reporting period

* bug#00091: Renamimg of some charts

* bug#00090: SQL issue could case invalid list of data items if graph
  permissions are enabled.


--- 0.6.1 ---

* feature: (CLI) Debug option allows to view internal results / stati of the
  calculation process

* feature: (CLI) Report can be calculated singlarly

* feature: Graph type "Line (filled)" added

* feature: Email attachment can be disabled to send out a notification only

* bug#00089: Wrong include path in cc_graph.php

* bug#00088: Archived data source alias can't be unserialized if it contains
  double quotes

* bug#00087: Resolution of subhead variables is not free from errors in exported
  reports

* bug#00086: Incorrect definition of table "reportit_cache_variables"

* bug#00085: Report view does not support output buffering to increase packet
  size (for WANs)

* bug#00084: A SQL error will occur if a measurand is edited.

* bug#00083: Critical: Undefined measured values (NANs) will be interpreted as
  "-1" on sytems with Windows if the type of connection to RRDtool is set to
  "RRDTOOL CACTI(SLOW)"

* bug#00082: User defined value won't be removed from database after template
  variable has been deleted

* bug#00081: Separators for CSV exports (sent via email) does not match user
  defined settings

* bug#00080: Input field for the subhead is too small

* bug#00079: Special HTML tags (br, p, b, i, u) are not supported in the subhead
  like in v0.5.1

* bug#00078: Critical: Quotes in a subhead impede to cache an archived report
  correctly

* bug#00077: Duplicating a report template fails

* bug#00076: Critical: Removing a report template that contains definitions of
  variables could cause that user defined values of other variables, which are
  based on other templates, will be removed instead

* bug#00075: Logging verbosity will be ignored

* bug#00074: If the data source alias is empty chart title won't contain the
  internal name

* bug#00073: Graph View: Uncomplete parameter used in a function call causes SQL
  errors

* bug#00072: Old field identifier inhibits that email settings can be saved

* bug#00071: Url rewriting allows to configure email settings without beeing
  activated

* bug#00070: An error in generating the titles for X and Y-axis causes that Bar,
  HBar, Line charts can fail

* bug#00069: Saved data source alias are invalid in Cacti 0.8.6, because
  sanitizing of SQL commands has changed with Cacti 0.8.7

* bug#00068: Guest user can not view reports although permissions are correct


--- 0.6.0 ---

* feature: Scheduled reports can be emailed to a defined list of recipients
  automatically

* feature: Template configuration supports the definition of an alias for every
  internal data source description which should be displayed instead.

* feature: Report configuration allows to define a template to configure new
  data items automatically

* feature: Administrators (Power Users) can change the report owner

* feature: Additional Filter "graph permission by device" supported

* feature: Report configuration divided into differend segments (General, Email,
  Presets, Administration)

* feature: New graphic module based on "Graidle" (cc_graph has been completely
  rewritten)

* feature: Export format "XML" available

* feature: Additional information per measurand can be displayed (sum, min, max,
  average)

* feature: Filter field "Limit" added

* feature: New search field allows to filter the report view

* feature: Scheduled reports will be archived automatically

* bug#00067: Guest user has no longer access to reporting functions without the
  correct realm ids

* bug#00066: Changing a lot of cc_reports.php to get more compatible to Cacti's
  drawing functions

* bug#00065: Report View: Default setting "Public" replaced by "My reports"

* bug#00064: Caching error occurs if 2 or more measurands call the same function
  with a parameter (only f_xth(), f_dot(), f_sot() are affected)

* bug#00063: Zack Bloom's "Advanced Graphing Class" has been replaced by "Graidle"

* bug#00062: Graph Overiew will always end at 23:59:59 instead of 24:00:00,
  because the calculation changes nearly with every version of RRDtool

* bug#00061: CSV Export file does not contain all variables

* bug#00060: Time zone will be shown without beeing activated on a fresh
  installation of Cacti

* bug#00058: Security check for export process is missing

* bug#00057: Incorrect width displayed with Cacti 0.8.7 and above


--- 0.5.1 ---

* feature: changemode.php modifies an existing ReportIt v0.4.x installation to
  support SQL strict mode

* feature: Active sort criterion: Arrow becomes red instead of yellow

* feature: Subhead configuration supports serial variables to show interface
  settings

* feature: Use of an individual filter per report view can be activated (graph
  settings)

* feature: Report View supports now:

  * Filtering by data sources and measurands

  * Enable/disable subheads (if activated in report configuration)

  * Enable/disable report summary

  * Switch to Graph View (if activated under "settings")

  * Limitation of rows

* feature: New table design with filter options and default limitation of rows

* feature: Active (red) tab is supported

* feature: User can define a default chart type

* feature: Graph support can be enabled/disabled

* feature: New system variable "nan" returns the sum of NAN's during the
  reporting period for all data sources

* feature: uninstall.php will remove all reportit tables and settings
  (completely rewritten)

* feature: New calculation function "f_last" returns the last valid measured
  value

* feature: New calculation function "f_1st" returns the first valid measured
  value

* feature: Explanation of functions and variables by using Java Script
  (measurand configuration)

* feature: New calculation function "f_grd" for statistical analysis (v0.4.2)

* feature: Formatting of dates relates to the user settings (v0.4.2)

* feature: Graph view relates to the reporting period (v0.4.2)

* feature: New reporting periods "Last Week (Mon-Sun)" and "Last Week" (Sun -
  Sat) added. (v0.4.2)

* bug#00056: Calculation results will be saved as "Double" instead of using
  "Float"

* bug#00055: CLI scripts don't work with Cacti 0.8.7

* bug#00054: Validation errors allows to save an end date that lies ahead

* bug#00053: Existing CSV file causes error messages during the export to CSV if
  it is write-protected

* bug#00052: Invalid formula can be saved despite validation

* bug#00051: Action "duplicate" for variables removed

* bug#00050: The duplicate of a report configuration gets status "valid" without
  rerun

* bug#00049: Non well formed numeric value by using connection type "RRDtool
  Cacti (SLOW)"

* bug#00048: Bugfix #00027 removed after the improvement of all SQL statements
  (see #00038)

* bug#00047: SQL error occurs during the saving of a new variable

* bug#00046: "Select All" button does not work in combination with Cacti 0.8.7

* bug#00045: Missing rrd file causes division by zero messages in conjunction
  with php bindings

* bug#00044: True type font arial.ttf removed

* bug#00043: Missing license for DejaVu true type fonts added

* bug#00042: 25% more processing performance for great reports (2000 data items)
  by code reduction / optimization

* bug#00041: Definition of graph fonts moved from graph to global settings

* bug#00040: Wrong replacement of interim results can cause syntax errors if
  simular abbreviations are choosen

* bug#00039: Critical bug during the data splitting process (in conjunction with
  time vectors) causes wrong calculation results

* bug#00038: Unexcact SQL statements cause saving errors on systems with
  sql-mode "strict"

  * All table definitions corrected

  * Saving procedure for report configurations fixed

  * Internal function fixed: reset_report()

  * All "JOIN"s replaced by "INNER JOIN"

* bug#00037: The Calculation of the sliding timeframe "current year" returns a
  wrong start date.  This error occurs on every first day of January.

* bug#00036: The Calculation of the sliding timeframe "current month" returns a
  wrong start date.  This error occurs on every first day of a new month.

* bug#00035: Wrong sequence of measurands after duplicating a template

* bug#00034: Variables are not actualized (wrong internal name) after
  duplicating a template

* bug#00033: An error in the calculation module occurs if more than one function
  with parameters (f_xth, f_sot, f_dot) has been called

* bug#00032: Several css errors fixed (v0.4.2)

* bug#00031: Connection type "RRDtool Cacti" returns wrong data sources in use
  with RRDtool 1.0.x (v0.4.2)

* bug#00030: Issues with sorting the result table (v0.4.2)

* bug#00029: Unable to notice PHP bindings for Windows (v0.4.2)

* bug#00028: Check for GD Freetype Support

* bug#00027: Check for my.cnf sql_mode <> STRICT_TRANS_TABLES, STRICT_ALL_TABLES

* bug#00026: Initialization issue in runtime.php l120 $report_id

* bug#00025: Initialization issue in runtime.php l463 $buffer

* bug#00024: Standard call format for includes

* bug#00023: Verify required php-gd extension prior to using it

* bug#00022: Documentation change: delete stale graphs every 60 seconds

* bug#00021: Verify permissions before writing the png graph file

* bug#00020: Strip graphing code down to a single procedure


--- 0.5.0 ---

* feature: Create Bar Charts and Pie Charts from reports

* feature: Management->Reports->Host Template (optional) Use those Data Source
  Items only, that belong to Hosts of this Host Template

* feature: Management->Reports->Additional Data Source Filter used on the Data
  Source Item List

* feature: Management->Reports->Enable Auto-Generating RRD List.  List of Data
  Source will automatically be generated based on filters

* feature: Management->Reports->Data Item List.  Header will show all applied
  filters

* feature: Reports selected from the Reports Tab will show timeframe in headers


--- 0.4.1 ---

* bug#00019: Template/Report Configuration: Issues with Javascript and
  Mozilla/Firefox

* bug#00018: Wrong decimal separator


--- 0.4.0 ---

* feature: Show local timezone

* feature: Enable/disable timezones

* feature: Use of decimal SI-Prefixes instead of binary ones

* feature: Disable Rounding

* feature: Rounding with binary and decimal SI-Prefixes

* feature: Additional subhead for interface description

* feature: Configurator for templates with measurands and variables

* feature: XSS safeguard for protection against Cross-Side-Scripting

* feature: Headerboxes with hyperlinks

* bug#00017: Running scheduled reporting from CLI

* bug#00016: No Warning occurs if sliding time frame is set to "Down To The
  Present Day" and the end of a defined shift time is a part of the future. The
  calculation will end at the actual time.

* bug#00015: Check of Read/write permission for export folders

* bug#00014: Wrong timestamps with PHP's function "gmmktime()" when Server is
  not set to UTC/GMT


--- 0.3.2 ---

* bug#00013: Call of the calculation functions changed from pass by reference to
  call by reference

* bug#00012: Missing server variable "SCRIPT_FILENAME" in runtime.php


--- 0.3.1 ---

* bug#00011: Definition of table 'reportit_reports' fixed

* bug#00010: Export header moved from user settings to global settings
  (settings/reports)

* bug#00009: Seperate sub folder for export files


--- 0.3.0 ---

* feature: Inputfield for maximum excution time of one calculation

* feature: Scheduled Reporting

* feature: New error messages and notices including a link to the correct
  configuration side

* feature: New fetch via 'rrdtool_execute' and 'RRDtool server', so "reportit"
  becomes win compatible.

* feature: New file design for the internal library

* bug#00008: Create table reportit_reports - Invalid values for type BOOL

* bug#00007: Control of dates for static time frame

* bug#00006: Restart of an finshed calculation by using browser's navigation
  buttons

* bug#00005: Settings 'Current Month' and 'Current Year' define sliding time
  frame up to now

* bug#00004: Wrong SI-prefixs for Yotta and Zetta

* bug#00003: Now user's reports a viewable under 'Public Report Tab' too.

* bug#00002: Wrong results by RRD-files with a lot of NAN's

* bug#00001: "24:00:00" added to working time

-----------------------------------------------
Copyright (c) 2004-2023 - The Cacti Group, Inc.
