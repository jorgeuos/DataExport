# Matomo DataExport Plugin

## Description

This plugin allows you to **export**, **download**, **sync**, and **import** your Matomo database.
Plus you can **export raw data** to a CSV file and much more.

You can easily import the data into another Matomo instance. For example,
you can use your real data into your development environment.

I have added some features to export raw data to a CSV file.
This is useful if you want to use the data in another tool, such as Excel, Tableau, or PowerBI.
This is a brand new plugin and some features are very experimental,
if you have any issues, please create an issue on GitHub.

### Caution

This plugin is not designed for exporting/importing large data sets(yet). Use at your own risk.

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

### In the works

We will update this plugin, probaly with paid features to connect to other
analytics tools. Such as Snowlflake, Google BigQuery, Apache Kafka, etc.  
Stay tuned!

### Installation

Install it via Matomo Marketplace

### How to use

Navigate to `Admin` -> `Platform` -> `Data Export` and follow the instructions.

### The UI

The UI is pretty straightforward. You can export the database, import a database, and export raw data to a CSV file.

### Raw data exports

I have collected a few queries from the Matomos faq: [How do I write SQL queries...](https://matomo.org/faq/how-to/how-do-i-write-sql-queries-to-select-visitors-list-of-pageviews-searches-events-in-the-matomo-database/).

### Command line

#### DB commands
```bash
$ ./console db:dump
$ ./console db:import
$ ./console db:drop
$ ./console db:create

```

### Syncing databases

I have added an option to sync databases. This is useful if you want to sync your database to an external server or S3 bucket.

You need to configure the destination in: `Admin(Cog wheel)` --> `System` --> `General settings` --> `Data Export`

And Select the Checkbox, `Sync File to External Server`.

Fill in all the required settings.
![Screenshot 2024-04-08 at 13 07 41](https://github.com/jorgeuos/DataExport/assets/21176316/8f032996-863f-4d10-8c4d-f2bf7e159fa2)


Then it should sync every time a new dump is generated with the `scheduled-tasks`, if you have set up a `./console core:archive`-job, you don't necessarily need to run it manually, It will automatically run when the `core:archive` process runs, one exception is if you added the `--disable-scheduled-tasks` flag to the command.

You can also test the settings by running the following command:
```sh
$ ./console scheduled-tasks:run "Piwik\Plugins\DataExport\Tasks.databaseDumpTask"
```

You can try to add the verbose flag `-vvv` for more log messages. If you encounter any bugs or issues, just create a new issue and I will try to fix it as soon as possible.

Just make sure you have added all the needed configuration settings in the UI or in your `config.ini.php` file.

### Configuration

You can configure in the UI or add the following settings to your `config.ini.php` file:
```ini
[DataExport]
; dataExportBackupPath:
; Path to store the backups on the Matomo server
dataExportBackupPath = ""
; dataExportAutoDump:
; available options: "none", "daily", "weekly"
dataExportAutoDump = "none"
; dataExportAutoDumpCompression:
; available options: "none", "zip", "tar"
dataExportAutoDumpCompression = "none"
; dataExportSyncExternal:
; 1 for true, 0 for false
dataExportSyncExternal = 0
; dataExportSyncOption:
; or "sftp"
dataExportSyncOption = "s3"
; dataExportSyncFilePath:
; Path for s3 or Remote Path for sftp
dataExportSyncFilePath = ""
; dataExportSyncBucketName:
; for s3 or Hostname for sftp
dataExportSyncBucketName = ""
; dataExportSyncKey:
; Access Key for s3 or Username for sftp
dataExportSyncKey = ""
; dataExportSyncSecret:
; Secret Key for s3 or Password for sftp
dataExportSyncSecret = ""
; dataExportSyncRegion:
; Region for s3
dataExportSyncRegion = ""
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

### API endpoints

I have started to add some API endpoints, but consider them experimental for now.  
**CAUTION: The queries are run as live queries, so use sparingly.**

You can specify the date, idSite, and format.

Example:
```bash
curl --request GET \
  --url 'https://YOUR_MATOMO_URL/index.php?module=API&method=DataExport.selectAllVisitsAndActions&date=2024-02-20&idSite=1&format=json&token_auth=YOUR_ADMIN_TOKEN'
```


## License

GPLv3 for now.


