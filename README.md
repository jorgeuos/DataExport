# Matomo DataExport Plugin

## Description

This plugin allows you to export the Matomo database to your local computer.
And you can easily import the data into another Matomo instance. For example,
you can use your real data into your development environment.

### Caution

This plugin is not designed for exporting/importing large data sets(yet). Use at your own risk.

## Features

- Export and download the database to a file
- Import a database from a file
- Export and download raw data to a CSV file
- Console commands to:
  - Dump the database
  - Import the database
  - Create the database
  - Drop the database
  - Cleanup old backups
  - Export queries to CSV

## In the works

We will update this plugin, probaly with paid features to connect to other
analytics tools. Such as Snowlflake, Google BigQuery, Apache Kafka, etc.  
Stay tuned!

## Installation

Install it via Matomo Marketplace

## How to use

Navigate to `Admin` -> `Platform` -> `Data Export` and follow the instructions.

## The UI

The UI is pretty straightforward. You can export the database, import a database, and export raw data to a CSV file.

### Raw data exports

I have collected a few queries from the Matomos faq: [How do I write SQL queries...](https://matomo.org/faq/how-to/how-do-i-write-sql-queries-to-select-visitors-list-of-pageviews-searches-events-in-the-matomo-database/).

## Command line

### DB commands
```bash
$ ./console db:dump
$ ./console db:import
$ ./console db:drop
$ ./console db:create

```

### Cleanup command

Downloading files from the UI will generate a copy that stays on the server.
In  order to not take up too much disk space Cleanup is set to run once a day and it deletes files older than 7 days.

```bash
$ ./console scheduled-tasks:run --force "Piwik\Plugins\DataExport\Tasks.cleanBackupsFolderTask"
```

To manually delete all files, you can run:
```bash
$ ./console dataexport:clean-backups -f
```


## License

GPLv3 for now.


