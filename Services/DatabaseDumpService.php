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
     * Constructor.
     */
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger ?: StaticContainer::get(LoggerInterface::class);
        $this->dbConfig = \Piwik\Config::getInstance()->database;
        $this->backupDir = PIWIK_USER_PATH . '/tmp/de_backups/';
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

        $fileService = new FileService();
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

        $fullPath = $fileService->compressDump($fullPath, $downloadPreference);

        return $fullPath;
    }
}
