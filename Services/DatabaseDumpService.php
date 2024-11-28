<?php

/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

use COM;
use Piwik\Plugins\DataExport\Services\FileService;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;
use Piwik\Db;
use Piwik\Common;
use Piwik\Date;

class DatabaseDumpService {

    /**
     * @var array
     */
    protected $dbConfig;

    /**
     * @var array
     */
    protected $dbReaderConfig;

    /**
     * @var string
     */
    protected $backupDir;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Piwik\Db
     */
    protected $db;

    /**
     * $var \Piwik\Plugins\DataExport\Services\FileService
     */
    protected $fileService;

    /**
     * Constructor.
     */
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger ?: StaticContainer::get(LoggerInterface::class);
        $this->dbConfig = \Piwik\Config::getInstance()->database;
        $this->dbReaderConfig = \Piwik\Config::getInstance()->database_reader;
        $this->fileService = new FileService();
        $this->backupDir = $this->fileService->getBackupDir();
        $this->db = Db::get();
    }

    private function getDbConfig() {
        if (empty($this->dbReaderConfig['host'])) {
            return $this->dbConfig;
        }
        $this->logger->debug('Using read-only database configuration.');
        return $this->dbReaderConfig;
    }

    public function generateDump($downloadPreference = 'none', $dumpPath = null) {
        $this->logger->debug('Generating database dump...');
        $this->logger->debug('Download preference: ' . $downloadPreference);
        $this->logger->debug('Dump path: ' . $dumpPath);
        $dbConfig = $this->getDbConfig();
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];

        // Make sure the backup directory exists
        if (!$this->fileService->ensure_directory_exists($this->backupDir)) {
            throw new \Exception("Failed to create backup directory.");
        }

        $fullPath = $this->backupDir . 'dbdump-' . date('Y-m-d_H-i-s') . '.sql';
        if ($dumpPath) {
            $fullPath = $dumpPath;
        }

        $command = sprintf(
            'mysqldump -u %s -h%s %s > %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            escapeshellarg($fullPath)
        );

        putenv('MYSQL_PWD=' . $dbPassword);
        exec($command, $output, $returnVar);
        putenv('MYSQL_PWD');

        if ($returnVar !== 0) {
            throw new \Exception("Failed to generate database dump.");
        }

        $fullPath = $this->fileService->compressDump($fullPath, $downloadPreference);

        return $fullPath;
    }

    /**
     * Get the minimum idaction_url from matomo_log_link_visit_action for a given date.
     */
    public function getLogActionMinId($date) {
        // Prepare start and end dates with proper formatting
        $startDate = Date::factory($date, null)->setTime('00:00:00')->toString('Y-m-d H:i:s');
        $endDate = Date::factory($date, null)->setTime('23:59:59')->toString('Y-m-d H:i:s');

        // SQL query with positional parameters
        $sql = "SELECT MIN(idaction_url) AS min_idaction
                FROM matomo_log_link_visit_action
                WHERE server_time >= ?
                AND server_time < ?";

        // Provide parameters in the correct order
        $parameters = [$startDate, $endDate];

        // Execute query and fetch the result
        $minId = $this->db->fetchOne($sql, $parameters);

        return $minId;
    }


    public function generateLogDumps($downloadPreference = 'none', $dumpPath = null, $tables = null, $date = null, $siteIds = null) {
        $this->logger->debug('Generating database dump...');
        $this->logger->debug('Download preference: ' . $downloadPreference);
        $this->logger->debug('Dump path: ' . $dumpPath);
        $this->logger->debug('Tables: ' . $tables);
        $this->logger->debug('Date: ' . $date);
        $this->logger->debug('Site IDs: ' . $siteIds);
    
        $dbConfig = $this->getDbConfig();
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];
    
        // Make sure the backup directory exists
        if (!$this->fileService->ensure_directory_exists($this->backupDir)) {
            throw new \Exception("Failed to create backup directory.");
        }
    
        $tablesArray = explode(',', $tables);
    
        $dumpCommands = [];
        foreach ($tablesArray as $i => $table) {
            $table = trim($table);
            $table = Common::prefixTable($table);

            // Handle other tables with direct date and site ID filtering
            $whereCondition = [];
            if (in_array($table, [Common::prefixTable('log_visit')])) {
                $dateTimeCol = 'visit_first_action_time';
            } elseif (in_array($table, [Common::prefixTable('log_link_visit_action'), Common::prefixTable('log_conversion'), Common::prefixTable('log_conversion_item')])) {
                $dateTimeCol = 'server_time';
            } else {
                $dateTimeCol = null;
            }

            if ($dateTimeCol) {
                if ($date) {
                    $whereCondition[] = sprintf("%s BETWEEN '%s 00:00:00' AND '%s 23:59:59'", $dateTimeCol, $date, $date);
                }
                if ($siteIds) {
                    $whereCondition[] = 'idsite IN (' . $siteIds . ')';
                }
                $whereClause = implode(' AND ', $whereCondition);
                $whereCondition = $whereClause ? '--where="' . $whereClause . '"' : '';
            }

            // Handle log_action separately due to lack of date columns
            // Query the first idaction_url from matomo_log_link_visit_action
            // that we can use as a filter for matomo_log_action
            if ($table == Common::prefixTable('log_action')) {
                $minId = $this->getLogActionMinId($date);
                $whereCondition = $minId ? '--where="idaction > ' . $minId . '"' : '';
            }

            $fullPath = $this->backupDir . $table . '-' . date('Y-m-d_H-i') . '.sql';
            if ($dumpPath) {
                $fullPath = $dumpPath;
            }

            // Example:
            // mysqldump --skip-add-drop-table -u root -h localhost matomo matomo_log_action --where="idaction > 123" >> /path/to/dump.sql
            $command = sprintf(
                'mysqldump --skip-add-drop-table -u %s -h%s %s %s %s >> %s',
                escapeshellarg($dbUser),
                escapeshellarg($dbHost),
                escapeshellarg($dbName),
                escapeshellarg($table),
                $whereCondition,
                escapeshellarg($fullPath)
            );
            $dumpCommands[] = $command;
            $this->logger->debug('Dump command: ' . $command);
        }
    
        putenv('MYSQL_PWD=' . $dbPassword);
        foreach ($dumpCommands as $command) {
            exec($command, $output, $returnVar);
            if ($returnVar !== 0) {
                putenv('MYSQL_PWD');
                throw new \Exception("Failed to generate database dump for command: $command.");
            }
        }
        putenv('MYSQL_PWD');
    
        if ($returnVar !== 0) {
            throw new \Exception("Failed to generate database dump.");
        }
    
        $fullPath = $this->fileService->compressDump($fullPath, $downloadPreference);
    
        return $fullPath;
    }

    /**
     * Select all visits and actions data for a given date range.
     *
     * It's likely that there's a subtle quirk in how Matomo’s DB abstraction processes named parameters.
     * Hence, the use of positional parameters in the query.
     */
    public function selectAllVisitsAndActionsData($date = 'yesterday', $siteId = null) {
        // Set the date range for the export
        $dateStart = date('Y-m-d', strtotime($date)) . ' 00:00:00';
        $dateEnd = date('Y-m-d', strtotime($date)) . ' 23:59:59';

        try {
            // Base SQL query with placeholders
            $sql = 'SELECT * 
                    FROM matomo_log_visit AS mlv
                    LEFT JOIN matomo_log_link_visit_action ON mlv.idvisit = matomo_log_link_visit_action.idvisit 
                    LEFT JOIN matomo_log_action ON matomo_log_action.idaction = matomo_log_link_visit_action.idaction_url 
                    LEFT JOIN matomo_log_conversion ON mlv.idvisit = matomo_log_conversion.idvisit 
                    LEFT JOIN matomo_log_conversion_item ON mlv.idvisit = matomo_log_conversion_item.idvisit
                    WHERE visit_last_action_time >= ?
                    AND visit_last_action_time <= ?';

            // Add conditionally for siteId
            $parameters = [
                $dateStart,
                $dateEnd,
            ];

            if ($siteId && $siteId > 0 && $siteId != 'all') {
                $sql .= ' AND mlv.idsite = ?';
                $parameters[] = $siteId;
            }

            $this->logger->debug('SQL: ' . $sql . ' | Parameters: ' . json_encode($parameters));

            // Use a prepared statement with parameterized values
            $data = $this->db->fetchAll($sql, $parameters);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->logger->error('Error exporting data to CSV: ' . $msg);
            return false;
        }
        return $data;
    }

    public function selectAllVisitsAndActions($dumpPath = null, $date = 'yesterday', $siteId = null) {
        $this->logger->info('Exporting database to CSV...');
        $this->logger->info('Dump path: ' . $dumpPath);

        // Make sure the backup directory exists
        if (!$this->fileService->ensure_directory_exists($this->backupDir)) {
            throw new \Exception("Failed to create backup directory.");
        }

        // Get the data
        $data = $this->selectAllVisitsAndActionsData($date, $siteId);
        if (empty($data)) {
            $this->logger->info('No data to export for the specified period.');
            return false;
        }

        // Determine the file path
        $sites = $siteId != null && $siteId > 0 ? 'site-' . $siteId : 'all-sites';
        $now = date('Y-m-d_H-i-s', strtotime('now'));
        $dumpDate = date('Y-m-d', strtotime($date));
        $fileName = 'dump-' . $dumpDate . '-' . $sites . '-' . $now . '.csv';

        $fullPath = $dumpPath ? $dumpPath : $this->backupDir . $fileName;

        // Write the data to the file
        $file = fopen($fullPath, 'w');
        // Write headers
        fputcsv($file, array_keys(reset($data)));
        // Write each row of data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        $this->logger->info('Data exported successfully to ' . $fullPath);

        return $fullPath;
    }
}
