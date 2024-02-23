<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

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
     * Constructor.
     */
    public function __construct() {
        $this->dbConfig = \Piwik\Config::getInstance()->database;
        $this->backupDir = PIWIK_USER_PATH . '/tmp/de_backups/';
    }

    private function zipDump($dumpPath) {
        $zipPath = $dumpPath . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFile($dumpPath, basename($dumpPath));
        $zip->close();
        unlink($dumpPath);
        return $zipPath;
    }

    private function tarDump($dumpPath) {
        $tarPath = $dumpPath . '.tar'; // Create a .tar archive first
        $gzPath = $tarPath . '.gz'; // Specify the final .gz path

        $tar = new \PharData($tarPath);
        // Add only the SQL dump file to the archive
        $tar->addFile($dumpPath, basename($dumpPath));

        // Compress the .tar file to .gz separately
        $tar->compress(\Phar::GZ);

        // At this point, a .tar.gz file is created, remove the .tar file
        unlink($tarPath); // Remove the intermediate .tar file
        unlink($dumpPath); // Remove the original dump file

        return $gzPath; // Return the path to th
    }

    public function generateDump($downloadPreference = 'none') {
        $dbConfig = $this->dbConfig;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];


        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $dumpPath = $this->backupDir . 'dbdump-' . date('Y-m-d_H-i-s') . '.sql';
        $command = sprintf(
            'mysqldump -u %s -h%s %s > %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            escapeshellarg($dumpPath)
        );

        putenv('MYSQL_PWD=' . $dbPassword);
        exec($command, $output, $returnVar);
        putenv('MYSQL_PWD');

        if ($returnVar !== 0) {
            throw new \Exception("Failed to generate database dump.");
        }

        if ($downloadPreference === 'zip') {
            $dumpPath = $this->zipDump($dumpPath);
        }

        if ($downloadPreference === 'tar') {
            $dumpPath = $this->tarDump($dumpPath);
        }
        return $dumpPath;
    }
}
