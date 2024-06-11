# Matomo DataExport Plugin

## Documentation

## Description

This plugin allows you to **export**, **download**, **sync**, and **import** your Matomo database.
Plus you can **export raw data** to a CSV file and much more.

You can easily import the data into another Matomo instance. For example,
you can use your real data into your development environment.

I have added some features to export raw data to a CSV file.
This is useful if you want to use the data in another tool, such as Excel, Tableau, or PowerBI.
This is a brand new plugin and some features are very experimental,
if you have any issues, please create an issue on GitHub.

![Screenshot of the plugin](https://plugins.matomo.org/DataExport/images/1.1.1/1-Default-view.png?w=1024)
![Screenshot of the plugin](https://plugins.matomo.org/DataExport/images/1.1.1/2-Default-view.png?w=1024)
![Screenshot of the plugin](https://plugins.matomo.org/DataExport/images/1.1.1/3-Settings-page.png?w=1024)

### Caution

This plugin is not designed for exporting/importing large data sets(yet).

### Features

- Export and download the database to a file
- Import a database from a file
- Export and download raw data to a CSV file
- Console commands to:
  - Dump the database
  - Sync the database
  - Import the database
  - Create the database
  - Drop the database
  - Cleanup old backups
  - Export queries to CSV
- API endpoints to:
  - Export raw data

## In the works

We will update this plugin, probaly with paid features to connect to other
analytics tools. Such as Snowlflake, Google BigQuery, Apache Kafka, etc.  
Stay tuned!

## Installation

Install it via Matomo Marketplace

## How to use

Navigate to `Admin` -> `Platform` => `Data Export` and follow the instructions.

## Configuration

Example of the configuration file:
```ini
[DataExport]
dataExportBackupPath = ""
dataExportAutoDump = "daily"
dataExportAutoDumpCompression = "zip"
dataExportSyncExternal = 1
dataExportSyncOption = "sftp"
dataExportSyncFilePath = "/home/YOUR_USER/backups"
dataExportSyncBucketName = "192.168.0.1"
dataExportSyncKey = "USERNAME"
dataExportSyncSecret = "YOUR_PASSWORD"
dataExportSyncRegion = ""
```

## License

GPLv3 for now.


