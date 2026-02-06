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

    private function isInternalNetwork($host) {
        // Remove port if present (e.g., "db:3306" -> "db")
        $cleanHost = explode(':', $host)[0];

        // Check for localhost variations
        if (in_array(strtolower($cleanHost), ['localhost', '127.0.0.1', '::1'])) {
            return true;
        }

        // Check for Docker/Kubernetes service names (typically don't contain dots)
        if (!filter_var($cleanHost, FILTER_VALIDATE_IP) && !strpos($cleanHost, '.')) {
            $this->logger->debug("Detected container service name: {$cleanHost}");
            return true;
        }

        // Check for private IP ranges
        if (filter_var($cleanHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = ip2long($cleanHost);
            if ($ip !== false) {
                // Private IP ranges (RFC 1918)
                $private_ranges = [
                    ['10.0.0.0', '10.255.255.255'],     // 10.0.0.0/8
                    ['172.16.0.0', '172.31.255.255'],   // 172.16.0.0/12
                    ['192.168.0.0', '192.168.255.255']  // 192.168.0.0/16
                ];

                foreach ($private_ranges as $range) {
                    if ($ip >= ip2long($range[0]) && $ip <= ip2long($range[1])) {
                        $this->logger->debug("Detected private IP range: {$cleanHost}");
                        return true;
                    }
                }
            }
        }

        // Check for Docker default networks
        if (filter_var($cleanHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = ip2long($cleanHost);
            if ($ip !== false) {
                // Docker default bridge network: 172.17.0.0/16
                if ($ip >= ip2long('172.17.0.0') && $ip <= ip2long('172.17.255.255')) {
                    $this->logger->debug("Detected Docker bridge network: {$cleanHost}");
                    return true;
                }
            }
        }

        // Check environment variables that indicate containerized environment
        if (getenv('KUBERNETES_SERVICE_HOST') || getenv('DOCKER_CONTAINER_NAME') || file_exists('/.dockerenv')) {
            $this->logger->debug("Detected containerized environment");
            // Additional logic: if we're in a container and the host looks internal
            if (!strpos($cleanHost, '.') || strpos($cleanHost, '.local') !== false) {
                return true;
            }
        }

        return false;
    }    

    private function shouldUseSSL($host) {
        // Get the plugin's SSL setting
        $settings = new \Piwik\Plugins\DataExport\SystemSettings();
        $sslSetting = $settings->dataExportDatabaseSSL->getValue();

        $this->logger->debug("Database SSL setting: {$sslSetting}");

        switch ($sslSetting) {
            case 'enabled':
                $this->logger->debug("SSL explicitly enabled via plugin setting");
                return true;

            case 'disabled':
                $this->logger->debug("SSL explicitly disabled via plugin setting");
                return false;

            case 'auto':
            default:
                // Auto-detect based on Matomo config and network context
                $generalConfig = \Piwik\Config::getInstance()->General;

                // Check Matomo's SSL-related settings
                if (!empty($generalConfig['assume_secure_protocol']) && $generalConfig['assume_secure_protocol'] == 1) {
                    $this->logger->debug("assume_secure_protocol enabled, using SSL");
                    return true;
                }

                if (!empty($generalConfig['force_ssl']) && $generalConfig['force_ssl'] == 1) {
                    $this->logger->debug("force_ssl enabled, using SSL");
                    return true;
                }

                // Fallback: disable SSL for internal networks
                if ($this->isInternalNetwork($host)) {
                    $this->logger->debug("Auto-detect: Internal network, disabling SSL");
                    return false;
                }

                $this->logger->debug("Auto-detect: External network, enabling SSL");
                return true;
        }
    }

    private function getDumpCommand() {
        // Check if mariadb-dump exists and use it for MariaDB
        exec('which mariadb-dump 2>/dev/null', $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            $this->logger->debug('Using mariadb-dump command');
            return 'mariadb-dump';
        }

        // Fallback to mysqldump
        $this->logger->debug('Using mysqldump command');
        return 'mysqldump';
    }

    private function getMysqlDumpSslOption() {
        exec('mysqldump --version 2>&1', $versionOutput, $returnVar);
        $versionString = implode(' ', $versionOutput);
        $this->logger->debug('mysqldump version: ' . $versionString);

        // Detect MariaDB client
        if (stripos($versionString, 'mariadb') !== false) {
            $this->logger->debug('MariaDB client detected, forcing --skip-ssl for compatibility');
            return '--skip-ssl';
        }

        // Default for MySQL client (works everywhere)
        $useSSL = false; // or $this->shouldUseSSL($dbHost) if you want dynamic detection
        return $useSSL ? '--ssl-mode=REQUIRED' : '--ssl-mode=DISABLED';
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
        $dbPort = isset($dbConfig['port']) ? $dbConfig['port'] : 3306;

        // Make sure the backup directory exists
        if (!$this->fileService->ensure_directory_exists($this->backupDir)) {
            throw new \Exception("Failed to create backup directory.");
        }

        $fullPath = $this->backupDir . 'dbdump-' . date('Y-m-d_H-i-s') . '.sql';
        if ($dumpPath) {
            $fullPath = $dumpPath;
        }


        $this->logger->debug('Args: ' . json_encode([
            'dbUser' => $dbUser,
            'dbHost' => $dbHost,
            'dbName' => $dbName,
            'fullPath' => $fullPath,
        ]));

        $debug_version="mysqldump --version";
        exec($debug_version, $output_version, $returnVar_version);
        $this->logger->debug('mysqldump version output: ' . implode("\n", $output_version));

        $dumpCmd = $this->getDumpCommand();
        $sslOptions = $this->getMysqlDumpSslOption();

        $command = sprintf(
            '%s %s --single-transaction --routines --triggers -u %s -h%s -P%d %s > %s',
            $dumpCmd,
            $sslOptions,
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            $dbPort,
            escapeshellarg($dbName),
            escapeshellarg($fullPath)
        );

        $this->logger->debug('Executing command: ' . $command);

        putenv('MYSQL_PWD=' . $dbPassword);
        exec($command, $output, $returnVar);
        putenv('MYSQL_PWD');

        if ($returnVar !== 0) {
            $errorMsg = implode("\n", $output);
            if (strpos($errorMsg, 'caching_sha2_password') !== false) {
                $this->logger->error(
                    "The dump command failed because your MySQL user uses the 'caching_sha2_password' plugin, " .
                    "which MariaDB client tools cannot use. " .
                    "Either install the MySQL client tools (mysql-client) or run:\n" .
                    "ALTER USER '{$dbUser}'@'%' IDENTIFIED WITH mysql_native_password BY '<password>';"
                );
            }
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
        $dbPort = isset($dbConfig['port']) ? $dbConfig['port'] : 3306;
    
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

            $dumpCmd = $this->getDumpCommand();
            $sslOptions = $this->getMysqlDumpSslOption();

            // Example:
            // mysqldump --skip-add-drop-table -u root -h localhost matomo matomo_log_action --where="idaction > 123" >> /path/to/dump.sql
            $command = sprintf(
                '%s --skip-add-drop-table %s -u %s -h%s -P %d %s %s %s >> %s',
                $dumpCmd,
                $sslOptions,
                escapeshellarg($dbUser),
                escapeshellarg($dbHost),
                $dbPort,
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
                $errorMsg = implode("\n", $output);
                if (strpos($errorMsg, 'caching_sha2_password') !== false) {
                    $this->logger->error(
                        "The dump command failed because your MySQL user uses the 'caching_sha2_password' plugin, " .
                        "which MariaDB client tools cannot use. " .
                        "Either install the MySQL client tools (mysql-client) or run:\n" .
                        "ALTER USER '{$dbUser}'@'%' IDENTIFIED WITH mysql_native_password BY '<password>';"
                    );
                }
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
