GRID COURSE FORMAT
============================
Package tested in: Moodle 2.3.1+ (Build: 20120712) 2012062501.02

BETA DEVELOPMENT VERSION - NOT FOR PRODUCTION SITES - G J Barnard 'http://moodle.org/user/profile.php?id=442195'

QUICK INSTALL
==============
Download zip package, extract the grid folder and upload this folder into course/format/.

If already installed, remove the row with the 'plugin' of 'format_grid' in the 'config_plugins' table and drop the 'format_grid_icon'
and 'format_grid_summary' tables before clicking on 'notifications'.

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