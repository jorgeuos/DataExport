<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

class FileService {

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
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->backupDir = PIWIK_USER_PATH . '/tmp/de_backups/';
    }

    private function zipDump(string $dumpPath) {
        $zipPath = $dumpPath . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFile($dumpPath, basename($dumpPath));
        $zip->close();
        unlink($dumpPath);
        return $zipPath;
    }

    private function tarDump(string $dumpPath) {
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

    public function ensure_directory_exists(string $dir) {
        $this->logger->debug('Ensuring directory exists: ' . $dir);
        if (!is_dir($dir)) {
            $this->logger->debug('No dir, creating directory: ' . $dir);
            return mkdir($dir, 0755, true);
        }
        return true;
    }

    public function is_full_path(string $dumpPath) {
        return $dumpPath != basename($dumpPath);
    }

    public function compressDump($dumpPath, $compress): string{
        if ($compress == 'zip') {
            return $this->zipDump($dumpPath);
        } elseif ($compress == 'tar') {
            return $this->tarDump($dumpPath);
        } else {
            return $dumpPath;
        }
    }
}
