# ReportIt

## Developers

* Andreas Braun (aka browniebraun)

* Mark Brugnoli-Vinten (aka netniV)

* Larry Adams (aka TheWitness)

## Purpose

This plugin creates tabular reports from RRDfile data which will be archived and
optionally Emailed to a user or users.

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

* Cacti 1.2.27 or higher.

To upgrade "ReportIt" release 0.4.0 or higher is required.

A prior version (0.1, 0.2, 0.3.0, 0.3.1, 0.3.2 or 0.4.0a) has to be completely
uninstalled (mysql tables as well)!

Therefore you can use uninstall.php.

Optional: To use the full set of ReportIt's functionalities additional
extensions are required

* Fast report generation: PHP extension 'php_rrdtool'

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
Copyright (c) 2004-2024 - The Cacti Group, Inc.
