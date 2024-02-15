<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

class DatabaseImportService
{

    /**
     * @var array
     */
    protected $dbConfig;

    /**
     * @var string
     */
    protected $backupDir;

    /**
     * @var string
     */
    protected $uploadDir;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->dbConfig = \Piwik\Config::getInstance()->database;
        $this->backupDir = PIWIK_USER_PATH . '/tmp/de_backups/';
        $this->uploadDir = PIWIK_USER_PATH . '/tmp/de_uploads/';
    }

    /**
     * Import a database dump.
     */
    public function importDump($dumpPath)
    {
        $dbConfig = $this->dbConfig;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];

        if (!file_exists($dumpPath) || !is_readable($dumpPath)) {
            throw new \Exception("The file does not exist or is not readable.");
        }

        $command = sprintf(
            'mysql -u %s -h%s -p%s %s < %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            escapeshellarg($dbPassword),
            escapeshellarg($dbName),
            escapeshellarg($dumpPath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Failed to import database dump.");
        }
        return true;
    }
}
