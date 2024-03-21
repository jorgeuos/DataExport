<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

use Piwik\Plugins\DataExport\Services\FileService;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;
use Piwik\Db;

class DatabaseDumpService {

    /**
     * @var array
     */
    protected $dbConfig;

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
        $this->fileService = new FileService();
        $this->backupDir = $this->fileService->getBackupDir();
        $this->db = Db::get();
    }

    public function generateDump($downloadPreference = 'none', $dumpPath = null) {
        $this->logger->debug('Generating database dump...');
        $this->logger->debug('Download preference: ' . $downloadPreference);
        $this->logger->debug('Dump path: ' . $dumpPath);
        $dbConfig = $this->dbConfig;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];

        // Make sure the backup directory exists
        if(!$this->fileService->ensure_directory_exists($this->backupDir)) {
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

    public function selectAllVisitsAndActionsData($date = 'yesterday', $siteId = null){
        // Set the date range for the export
        $dateStart = date('Y-m-d', strtotime($date)) . ' 00:00:00';
        $dateEnd = date('Y-m-d', strtotime($date)) . ' 23:59:59';
        
        try {
            $sql = 'SELECT *
                    FROM matomo_log_visit AS mlv
                    LEFT JOIN matomo_log_link_visit_action ON mlv.idvisit = matomo_log_link_visit_action.idvisit 
                    LEFT JOIN matomo_log_action ON matomo_log_action.idaction = matomo_log_link_visit_action.idaction_url 
                    LEFT JOIN matomo_log_conversion ON mlv.idvisit = matomo_log_conversion.idvisit 
                    LEFT JOIN matomo_log_conversion_item ON mlv.idvisit = matomo_log_conversion_item.idvisit
                    WHERE visit_last_action_time >= "' . $dateStart . '"
                    AND visit_last_action_time <= "' . $dateEnd . '"
                    ';
            if ($siteId && $siteId > 0 && $siteId != 'all') {
                $sql .= ' AND mlv.idsite = ' . $siteId;
            }
            $sql .= ';';

            
            $this->logger->debug('SQL: ' . $sql);
            // Use parameterized query for security and flexibility
            $data = $this->db->fetchAll($sql);
        } catch (\Exception $e) {
            $this->logger->error('Error exporting data to CSV: ' . $e->getMessage());
            return false;
        }
        return $data;
    }

    public function selectAllVisitsAndActions($dumpPath = null, $date = 'yesterday', $siteId = null) {
        $this->logger->info('Exporting database to CSV...');
        $this->logger->info('Dump path: ' . $dumpPath);

        // Get the data
        $data = $this->selectAllVisitsAndActionsData($date, $siteId);
        if (empty($data)) {
            $this->logger->info('No data to export for the specified period.');
            return false;
        }

        // Determine the file path
        $sites = $siteId != null && $siteId < 0 ? 'site-' . $siteId : 'all-sites';
        $now = date('Y-m-d_H-i-s', strtotime('now'));
        $dumpDate = date('Y-m-d', strtotime($date));
        $fileName = 'dump-' . $dumpDate . '-' . $sites .'-' . $now . '.csv';

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
