## Changelog


1.1.9
- WP handles file paths differently than PIWIK_INCLUDE_PATH, changed to __DIR__ for relative paths


1.1.8
- Added directory check for the export folder for the CSV export

1.1.7
- Changed to positional statements
- Solved CSV naming bug
- Added extra logic to get allSites in CSV


1.1.6
- Fixed databaseDumpTask runner


1.1.5

- Fixed compression bug
- Updated the README.md
- Rearrange the ChangeLog to chronological order

1.1.4

- Removed filter_var for compressions, allow multiple compressions

1.1.3

- Added more checks for the export folder
Ran into the the same bug again

1.1.2

- Fixed Bug when dir is empty
- Added translations for the client side

1.1.1

Trying to fix the Description in the Matomo Marketplace

1.1.0

Added support for exporting data to a remote server
- Feature: Sync to S3
- Feature: Sync to SFTP
- Feature: Finished the DB:dump Task, it had a bugs
- Feature: Updated the README.md

1.0.0

Bump to a major release
- Feature: Added bunch of new features for compression
- Feature: Added CSV option for exporting raw data
- Feature: Added Cleanup feature to clean up export folder
- Feature: Added API support for exporting data

More is coming soon...

0.1.2

- Feature: Add command line interface to export data
```bash
$ bin/console db:dump
$ bin/console db:import
$ bin/console db:drop
$ bin/console db:create
```
- Feature: Add support for exporting data as a tar.gz file
- Feature: Add support for cleaning up export folder

