GRID COURSE FORMAT
============================
Package tested in: Moodle 2.3.1+ (Build: 20120802) 2012062501.07

Requires at least 2012062501.03 because of MDL-33767.

BETA DEVELOPMENT VERSION - NOT FOR PRODUCTION SITES - Please place comments / feedback on:
http://tracker.moodle.org/browse/CONTRIB-3769 - preferred.
or
http://moodle.org/mod/forum/discuss.php?d=207059

QUICK INSTALL
==============
Download zip package, extract the grid folder and upload this folder into course/format/.

NOTE: If you have previously installed a development version and get the latest code you MUST:
Remove the row with the 'plugin' of 'format_grid' in the 'config_plugins' table and drop the 'format_grid_icon'
and 'format_grid_summary' tables in the database before clicking on 'notifications'.

If upgrade fails, please perform the actions as detailed in the note above.

ABOUT
=============
Developed by:
Information in: 

FILES
--------------

* grid/format.php

  Code that actually displays the course view page.

* grid/config.php

  Configuration file, mainly controlling default blocks for the format.

* grid/lang/en/format_grid.php
* grid/lang/ru/format_grid.php
* grid/lang/es/format_grid.php
* grid/lang/fr/format_grid.php

  Language file containing language strings for grid format.

  Note that existing formats store their language strings in the main
  moodle.php, which you can also do, but this separate file is recommended
  for contributed formats.

  Of course you can have other folders as well as just English and Russian
  if you want to provide multiple languages.

* grid/db/install.xml

  Database table definitions.

* grid/db/upgrade.php

  Database upgrade script.

* grid/version.php

  Required for using database tables. The file provides information 
  about plugin version (update when tables change) and required Moodle version.

* grid/styles.css

  The file include in the CSS Moodle generates.

* grid/backup/moodle2/backup_format_grid_plugin.class.php
  grid/backup/moodle2/restore_format_grid_plugin.class.php

  Backup and restore run automatically when backing up the course.
  You can't back up the course format data independently.

ROADMAP
=============

1. Working Moodle 2.4 version.
2. Improved instructions.
3. User definable grid row icon numbers - https://moodle.org/mod/forum/discuss.php?d=196716

HISTORY
=============

13th July 2012 - Version 2.3
1. First release for Moodle 2.3

22nd August 2012 - Version 2.3.1
1. Added missing 'current section' string.

19th December 2012 - Version 2.3.2
1. Fixed ability to use png files because of attempting to use jpeg quality setting.
2. Increased quality setting for better icons.
