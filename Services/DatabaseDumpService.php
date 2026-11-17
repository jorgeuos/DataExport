<?php

/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

use Piwik\Plugins\DataExport\Services\Dump\DbConnectionFactory;
use Piwik\Plugins\DataExport\Services\Dump\MysqlCli;
use Piwik\Plugins\DataExport\Services\Dump\PhpSqlDumper;
use Piwik\Plugins\DataExport\SystemSettings;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;
use Piwik\Db;
use Piwik\Common;
use Piwik\Date;

class DatabaseDumpService {

    public const METHOD_AUTO = 'auto';
    public const METHOD_MYSQLDUMP = 'mysqldump';
    public const METHOD_PHP = 'php';

    /**
     * Log tables and the column used to filter them by date.
     */
    private const LOG_TABLE_DATE_COLUMNS = [
        'log_visit' => 'visit_first_action_time',
        'log_link_visit_action' => 'server_time',
        'log_conversion' => 'server_time',
        'log_conversion_item' => 'server_time',
    ];

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
    public function __construct(?LoggerInterface $logger = null) {
        $this->logger = $logger ?: StaticContainer::get(LoggerInterface::class);
        $this->fileService = new FileService($this->logger);
        $this->backupDir = $this->fileService->getBackupDir();
        $this->db = Db::get();
    }

    /**
     * Connection config for dumps: the reader database when one is configured.
     */
    private function getDbConfig() {
        return DbConnectionFactory::getConfig(true);
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

    /**
     * Whether SSL must be explicitly disabled for the mysqldump connection.
     *
     * - "enabled": never disable SSL (the client default, SSL when available).
     * - "disabled": always disable SSL.
     * - "auto": keep SSL when Matomo's own connection uses it
     *   ([database] enable_ssl = 1), otherwise disable it for internal hosts
     *   (localhost, Docker/Kubernetes service names, private IPs), where
     *   servers often have self-signed or no certificates.
     */
    private function shouldDisableSsl(array $dbConfig): bool {
        $settings = new SystemSettings();
        $sslSetting = $settings->dataExportDatabaseSSL->getValue();
        $this->logger->debug("Database SSL setting: {$sslSetting}");

        if ($sslSetting === 'enabled') {
            return false;
        }
        if ($sslSetting === 'disabled') {
            return true;
        }
        if (!empty($dbConfig['enable_ssl'])) {
            return false;
        }
        if (!empty($dbConfig['unix_socket'])
            || (isset($dbConfig['port']) && is_string($dbConfig['port']) && strpos($dbConfig['port'], '/') === 0)
        ) {
            return true;
        }
        return $this->isInternalNetwork((string) ($dbConfig['host'] ?? 'localhost'));
    }

    /**
     * Returns the dump method that will be used.
     *
     * @param string|null $requested auto, mysqldump or php. Defaults to the plugin setting.
     * @throws \Exception when mysqldump is explicitly requested but not available
     */
    public function resolveDumpMethod(?string $requested = null): string {
        $method = $requested;
        if (empty($method)) {
            $settings = new SystemSettings();
            $method = $settings->dataExportDumpMethod->getValue() ?: self::METHOD_AUTO;
        }

        if ($method === self::METHOD_PHP) {
            return self::METHOD_PHP;
        }

        $mysqldump = MysqlCli::findDump();

        if ($method === self::METHOD_MYSQLDUMP) {
            if (!$mysqldump) {
                throw new \Exception('mysqldump (or mariadb-dump) was not found or exec() is disabled. '
                    . 'Choose the "PHP" or "Auto" backup method instead.');
            }
            return self::METHOD_MYSQLDUMP;
        }

        if ($method !== self::METHOD_AUTO) {
            throw new \Exception("Unknown backup method '$method'. Use auto, mysqldump or php.");
        }

        return $mysqldump ? self::METHOD_MYSQLDUMP : self::METHOD_PHP;
    }

    /**
     * Human readable description of how backups will be made, for the UI.
     */
    public function getDumpMethodStatus(): array {
        $settings = new SystemSettings();
        $configured = $settings->dataExportDumpMethod->getValue() ?: self::METHOD_AUTO;
        $mysqldump = MysqlCli::findDump();
        try {
            $method = $this->resolveDumpMethod();
            $error = null;
        } catch (\Exception $e) {
            $method = null;
            $error = $e->getMessage();
        }
        return [
            'configured' => $configured,
            'method' => $method,
            'mysqldump' => $mysqldump ? $mysqldump['version'] : null,
            'canExec' => MysqlCli::canExec(),
            'error' => $error,
        ];
    }

    /**
     * Dumps the whole database.
     *
     * @param string $downloadPreference none, gzip, zip or tar. gzip compresses
     *                                   while dumping and is the fastest for large databases.
     * @param string|null $dumpPath Full path of the .sql file, defaults to the backup folder
     * @param string|null $method auto, mysqldump or php. Defaults to the plugin setting.
     * @return string Path of the (compressed) dump
     */
    public function generateDump($downloadPreference = 'none', $dumpPath = null, $method = null) {
        $this->logger->debug('Generating database dump...');
        $this->logger->debug('Download preference: ' . $downloadPreference);
        $this->logger->debug('Dump path: ' . $dumpPath);

        // Make sure the backup directory exists
        if ($this->fileService->ensure_directory_exists($this->backupDir) !== true) {
            throw new \Exception("Failed to create backup directory.");
        }

        $fullPath = $dumpPath ?: $this->backupDir . 'dbdump-' . date('Y-m-d_H-i-s') . '.sql';
        $fullPath = self::applyGzipExtension($fullPath, $downloadPreference);

        $configuredMethod = $method ?: null;
        $method = $this->resolveDumpMethod($configuredMethod);
        $this->logger->info('Backup method: ' . $method);

        if ($method === self::METHOD_MYSQLDUMP) {
            try {
                $this->runMysqldump($fullPath);
            } catch (\Exception $e) {
                $settings = new SystemSettings();
                $isAuto = ($configuredMethod ?: ($settings->dataExportDumpMethod->getValue() ?: self::METHOD_AUTO)) === self::METHOD_AUTO;
                if (!$isAuto) {
                    throw $e;
                }
                $this->logger->notice('mysqldump failed, falling back to the PHP backup method: ' . $e->getMessage());
                $this->runPhpDump($fullPath);
            }
        } else {
            $this->runPhpDump($fullPath);
        }

        return $this->fileService->compressDump($fullPath, $downloadPreference);
    }

    /**
     * With gzip, the dump is written compressed right away: name it .sql.gz.
     */
    private static function applyGzipExtension(string $path, $downloadPreference): string {
        if ($downloadPreference === 'gzip' && substr(strtolower($path), -3) !== '.gz') {
            return $path . '.gz';
        }
        return $path;
    }

    private function runPhpDump(string $fullPath): void {
        $db = DbConnectionFactory::create(true);
        try {
            $dumper = new PhpSqlDumper($db, $this->logger);
            $dumper->dumpDatabase($fullPath);
        } finally {
            $db->closeConnection();
        }
    }

    /**
     * Base mysqldump command (binary, SSL and connection options).
     */
    private function getMysqldumpCommand(array $dbConfig): string {
        $mysqldump = MysqlCli::findDump();
        if (!$mysqldump) {
            throw new \Exception('mysqldump not available.');
        }
        $this->logger->debug('mysqldump version: ' . $mysqldump['version']);

        $sslOption = '';
        if ($this->shouldDisableSsl($dbConfig)) {
            $sslOption = MysqlCli::disableSslOption($mysqldump['version']);
            $this->logger->debug('Disabling SSL for mysqldump: ' . $sslOption);
        }

        // --single-transaction: consistent InnoDB snapshot without locking tables (tracking keeps working)
        // --quick: stream rows instead of buffering whole tables in memory
        // --no-tablespaces: avoids needing the PROCESS privilege on MySQL 8.0.21+
        return sprintf(
            '%s --single-transaction --quick --no-tablespaces --default-character-set=utf8mb4 %s %s',
            escapeshellarg($mysqldump['binary']),
            $sslOption,
            MysqlCli::connectionArgs($dbConfig)
        );
    }

    private function runMysqldump(string $fullPath): void {
        $dbConfig = $this->getDbConfig();

        $command = sprintf(
            '%s %s',
            $this->getMysqldumpCommand($dbConfig),
            escapeshellarg($dbConfig['dbname'])
        );
        $this->logger->debug('Dump command: ' . $command);

        [$returnVar, $stderr] = MysqlCli::runToFile($command, $fullPath, false, (string) $dbConfig['password']);
        if ($returnVar !== 0) {
            @unlink($fullPath);
            throw new \Exception("Failed to generate database dump with mysqldump: " . $stderr);
        }
    }

    /**
     * Get the minimum idaction_url from the log_link_visit_action table for a given date.
     */
    public function getLogActionMinId($date) {
        // Prepare start and end dates with proper formatting
        $startDate = Date::factory($date, null)->setTime('00:00:00')->toString('Y-m-d H:i:s');
        $endDate = Date::factory($date, null)->setTime('23:59:59')->toString('Y-m-d H:i:s');

        $sql = "SELECT MIN(idaction_url) AS min_idaction
                FROM " . Common::prefixTable('log_link_visit_action') . "
                WHERE server_time >= ?
                AND server_time <= ?";

        return $this->db->fetchOne($sql, [$startDate, $endDate]);
    }

    /**
     * Builds the WHERE condition for one log table. All values are validated,
     * so the condition is safe to pass to mysqldump --where and PhpSqlDumper.
     *
     * @param string $table unprefixed table name
     * @param string|null $date YYYY-MM-DD
     * @param int[] $siteIds
     */
    private function getLogTableWhere(string $table, ?string $date, array $siteIds): ?string {
        if ($table === 'log_action') {
            // log_action has no date column: only dump actions from the first one
            // used on that day onwards.
            if (!$date) {
                return null;
            }
            $minId = $this->getLogActionMinId($date);
            return $minId ? 'idaction >= ' . (int) $minId : null;
        }

        if (!isset(self::LOG_TABLE_DATE_COLUMNS[$table])) {
            return null;
        }

        $conditions = [];
        if ($date) {
            $conditions[] = sprintf(
                "%s BETWEEN '%s 00:00:00' AND '%s 23:59:59'",
                self::LOG_TABLE_DATE_COLUMNS[$table],
                $date,
                $date
            );
        }
        if (!empty($siteIds)) {
            $conditions[] = 'idsite IN (' . implode(',', $siteIds) . ')';
        }
        return $conditions ? implode(' AND ', $conditions) : null;
    }

    /**
     * Dumps log tables, optionally filtered by date and sites, into one file.
     *
     * @param string $downloadPreference none, zip or tar
     * @param string|null $dumpPath Full path of the .sql file, defaults to the backup folder
     * @param string|null $tables comma separated, unprefixed table names
     * @param string|null $date YYYY-MM-DD
     * @param string|null $siteIds comma separated site IDs
     * @param string|null $method auto, mysqldump or php
     */
    public function generateLogDumps($downloadPreference = 'none', $dumpPath = null, $tables = null, $date = null, $siteIds = null, $method = null) {
        $this->logger->debug('Generating log dumps...');
        $this->logger->debug('Download preference: ' . $downloadPreference);
        $this->logger->debug('Dump path: ' . $dumpPath);
        $this->logger->debug('Tables: ' . $tables);
        $this->logger->debug('Date: ' . $date);
        $this->logger->debug('Site IDs: ' . $siteIds);

        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \Exception("Invalid date '$date', expected YYYY-MM-DD.");
        }

        $siteIdList = [];
        if ($siteIds !== null && $siteIds !== '') {
            foreach (explode(',', $siteIds) as $siteId) {
                $siteId = trim($siteId);
                if (!ctype_digit($siteId)) {
                    throw new \Exception("Invalid site ID '$siteId'.");
                }
                $siteIdList[] = (int) $siteId;
            }
        }

        $tables = $tables ?: 'log_visit,log_link_visit_action,log_action,log_conversion,log_conversion_item';
        $tableWheres = [];
        foreach (explode(',', $tables) as $table) {
            $table = trim($table);
            if ($table === '') {
                continue;
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                throw new \Exception("Invalid table name '$table'.");
            }
            $tableWheres[Common::prefixTable($table)] = $this->getLogTableWhere($table, $date, $siteIdList);
        }

        // Make sure the backup directory exists
        if ($this->fileService->ensure_directory_exists($this->backupDir) !== true) {
            throw new \Exception("Failed to create backup directory.");
        }

        $fullPath = $dumpPath ?: $this->backupDir . 'logdump-' . ($date ?: 'all') . '-' . date('Y-m-d_H-i-s') . '.sql';
        $fullPath = self::applyGzipExtension($fullPath, $downloadPreference);

        $method = $this->resolveDumpMethod($method);
        $this->logger->info('Backup method: ' . $method);

        if ($method === self::METHOD_PHP) {
            $db = DbConnectionFactory::create(true);
            try {
                $dumper = new PhpSqlDumper($db, $this->logger);
                $dumper->dumpTables($fullPath, $tableWheres, (bool) $dumpPath && file_exists($fullPath));
            } finally {
                $db->closeConnection();
            }
        } else {
            $dbConfig = $this->getDbConfig();
            $baseCommand = $this->getMysqldumpCommand($dbConfig);
            $first = true;
            foreach ($tableWheres as $table => $where) {
                // Example:
                // mysqldump --skip-add-drop-table -u root -h localhost matomo matomo_log_action --where="idaction >= 123" >> /path/to/dump.sql
                $command = sprintf(
                    '%s --skip-add-drop-table %s %s %s',
                    $baseCommand,
                    $where ? escapeshellarg('--where=' . $where) : '',
                    escapeshellarg($dbConfig['dbname']),
                    escapeshellarg($table)
                );
                $append = !($first && !$dumpPath);
                $first = false;
                $this->logger->debug('Dump command: ' . $command);

                [$returnVar, $stderr] = MysqlCli::runToFile($command, $fullPath, $append, (string) $dbConfig['password']);
                if ($returnVar !== 0) {
                    throw new \Exception("Failed to dump table $table with mysqldump: " . $stderr);
                }
            }
        }

        return $this->fileService->compressDump($fullPath, $downloadPreference);
    }

    /**
     * Tables joined by the "select all visits and actions" export, in join order.
     * Alias => unprefixed table name.
     */
    private const VISITS_AND_ACTIONS_TABLES = [
        'mlv' => 'log_visit',
        'mllva' => 'log_link_visit_action',
        'mla' => 'log_action',
        'mlc' => 'log_conversion',
        'mlci' => 'log_conversion_item',
    ];

    /**
     * Binary columns that are converted to readable values in exports.
     */
    private const HEX_COLUMNS = ['idvisitor', 'config_id'];
    private const IP_COLUMNS = ['location_ip'];

    /**
     * Returns the FROM/JOIN/WHERE part of the "select all visits and actions"
     * query, using the configured table prefix
     * (see https://github.com/jorgeuos/DataExport/issues/7).
     *
     * Parameters: visit_last_action_time start and end, optionally followed by
     * an `AND mlv.idsite = ?` condition added by the caller.
     */
    public static function getSelectAllVisitsAndActionsFromSql(): string {
        $t = [];
        foreach (self::VISITS_AND_ACTIONS_TABLES as $alias => $table) {
            $t[$alias] = Common::prefixTable($table);
        }

        return "FROM `{$t['mlv']}` AS mlv
                    LEFT JOIN `{$t['mllva']}` AS mllva ON mlv.idvisit = mllva.idvisit
                    LEFT JOIN `{$t['mla']}` AS mla ON mla.idaction = mllva.idaction_url
                    LEFT JOIN `{$t['mlc']}` AS mlc ON mlv.idvisit = mlc.idvisit
                    LEFT JOIN `{$t['mlci']}` AS mlci ON mlv.idvisit = mlci.idvisit
                    WHERE mlv.visit_last_action_time >= ?
                    AND mlv.visit_last_action_time <= ?";
    }

    /**
     * Builds an explicit column list for the export.
     *
     * Several joined tables share column names (idvisit, idsite, idvisitor,
     * server_time, ...). With `SELECT *` the value of the *last* table won, so
     * a visit without conversions got an empty idvisit and idsite from the
     * LEFT JOINed conversion tables. Here the first table that has a column
     * wins, which keeps the same CSV header as before but with correct values.
     */
    public function getSelectAllVisitsAndActionsColumns(): string {
        $seen = [];
        $columns = [];
        foreach (self::VISITS_AND_ACTIONS_TABLES as $alias => $table) {
            $tableColumns = $this->db->fetchCol('SHOW COLUMNS FROM `' . Common::prefixTable($table) . '`');
            foreach ($tableColumns as $column) {
                if (isset($seen[$column])) {
                    continue;
                }
                $seen[$column] = true;
                $columns[] = $alias . '.`' . $column . '`';
            }
        }
        return implode(', ', $columns);
    }

    /**
     * Converts binary columns to readable values (hex visitor IDs, IP addresses),
     * so the data can be written to CSV and encoded as JSON by the API.
     */
    public static function formatExportRow(array $row): array {
        foreach (self::HEX_COLUMNS as $column) {
            if (isset($row[$column]) && $row[$column] !== '') {
                $row[$column] = bin2hex($row[$column]);
            }
        }
        foreach (self::IP_COLUMNS as $column) {
            if (isset($row[$column]) && $row[$column] !== '') {
                $ip = @inet_ntop($row[$column]);
                $row[$column] = $ip !== false ? $ip : bin2hex($row[$column]);
            }
        }
        return $row;
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
            $sql = 'SELECT ' . $this->getSelectAllVisitsAndActionsColumns() . ' '
                . self::getSelectAllVisitsAndActionsFromSql();

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
            $data = array_map([self::class, 'formatExportRow'], $data);
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
