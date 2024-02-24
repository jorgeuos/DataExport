<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;
use Piwik\Plugins\DataExport\SystemSettings;

class FileService {

    /**
     * @var string
     */
    protected $backupDir;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $settings;

    /**
     * Constructor.
     */
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger ?: StaticContainer::get(LoggerInterface::class);
        $this->backupDir = PIWIK_USER_PATH . '/tmp/de_backups/';
        $this->settings = new SystemSettings();
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

    public function getBackupDir() {
        $customPath = $this->settings->dataExportBackupPath->getValue();
        if ($customPath) {
            // Check if the custom path has a trailing slash, if not, add it
            if (substr($customPath, -1) != '/') {
                $customPath .= '/';
            }
            $this->backupDir = $customPath;
        }
        return $this->backupDir;
    }

    /**
     * Deletes old backup files from the backup folder.
     */
    public function cleanBackupsFolder($force = null){
        $this->logger->info("Run cleanup task");
        $backupFolder = $this->backupDir;
        $filePatterns = ['*.sql', '*.zip', '*.tar.gz'];
        $daysOld = 7;
    
        // Calculate the threshold date/time for deletion
        $thresholdTime = time() - ($daysOld * 24 * 60 * 60);
        if($force){
            $thresholdTime = time();
        }
    
        foreach ($filePatterns as $pattern) {
            // Use glob to find matching files
            $files = glob($backupFolder . $pattern);

            foreach ($files as $file) {
                $this->logger->info("Checking $file");
                // Check if file modification time is older than the threshold
                if (filemtime($file) < $thresholdTime) {
                    if (unlink($file)) {
                        $this->logger->info("Deleted $file");
                    } else {
                        // Log an error or throw an exception if the file couldn't be deleted
                        $this->logger->debug("Error deleting $file");
                    }
                }
                else {
                    $this->logger->info("Not old enough to delete $file");
                    $this->logger->info("Age: " . filemtime($file));
                }
            }
        }
    }
    

}
