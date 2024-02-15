<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

class DatabaseDumpService
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
     * Constructor.
     */
    public function __construct()
    {
        $this->dbConfig = \Piwik\Config::getInstance()->database;
        $this->backupDir = PIWIK_USER_PATH . '/tmp/de_backups/';
    }

    public function zipDump($dumpPath)
    {
        $zipPath = $dumpPath . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFile($dumpPath, basename($dumpPath));
        $zip->close();
        return $zipPath;
    }

    public function generateDump($zipDownload = false)
    {
        $dbConfig = $this->dbConfig;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];


        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $dumpPath = $this->backupDir . '/dbdump-' . date('Y-m-d_H-i-s') . '.sql';
        $command = sprintf(
            'mysqldump -u %s -h%s -p%s %s > %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            escapeshellarg($dbPassword),
            escapeshellarg($dbName),
            escapeshellarg($dumpPath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Failed to generate database dump.");
        }

        if ($zipDownload) {
            $zipPath = $this->zipDump($dumpPath);
            unlink($dumpPath);
            return $zipPath;
        }

        return $dumpPath;
    }
}
