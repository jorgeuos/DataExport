<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

require_once(PIWIK_INCLUDE_PATH . '/plugins/DataExport/vendor/autoload.php');

use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;
use Piwik\Plugins\DataExport\SystemSettings;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use \phpseclib3\Net\SFTP;
use Piwik\Plugins\DataExport\Helpers\SettingsHelper;

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

    public function compressDump($dumpPath, $compress): string {
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
    public function cleanBackupsFolder($force = null) {
        $this->logger->info("Run cleanup task");
        $backupFolder = $this->backupDir;
        $filePatterns = ['*.sql', '*.zip', '*.tar.gz'];
        $daysOld = 7;

        // Calculate the threshold date/time for deletion
        $thresholdTime = time() - ($daysOld * 24 * 60 * 60);
        if ($force) {
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
                } else {
                    $this->logger->info("Not old enough to delete $file");
                    $this->logger->info("Age: " . filemtime($file));
                }
            }
        }
    }

    /**
     * Handles sending a file to the client for download.
     *
     * @param string $filePath The path to the file to be downloaded.
     * @throws \Exception If the file does not exist or is not readable.
     */
    public function downloadFile($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("The file does not exist or is not readable.");
        }

        // Set headers to force the download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Clear output buffer before reading the file
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Read the file and output its contents
        readfile($filePath);

        // Terminate the script to prevent sending additional output
        exit;
    }

    /**
     * Returns the contents of a file as a JSON string.
     * Not used, because it doesn't make sense
     */
    public function returnFileAsJSON($file) {
        $fileContents = file_get_contents($file);
        $fileContents = base64_encode($fileContents);
        $fileContents = json_encode($fileContents);
        return $fileContents;
    }

    function formatSize($size) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    /**
     * Show files in backup directory,
     * only if the directory is in the default location
     */
    public function getDirectorySize($dir) {
        $size = 0;
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                // Check if it's a file or a directory
                if (is_file($dir . '/' . $file)) {
                    // It's a file, add its size
                    $size += filesize($dir . '/' . $file);
                } elseif (is_dir($dir . '/' . $file)) {
                    // It's a directory, recursively call this function
                    $size += self::getDirectorySize($dir . '/' . $file);
                }
            }
        }
        return $size;
    }

    public function getFilesInBackupDir() {
        $path = $this->getBackupDir();
        $ignoreFiles = ['.', '..', '.gitignore', '.DS_Store'];
        $files = array_diff(scandir($path), $ignoreFiles);
        $size = 0;
        foreach ($files as $file) {
            $size += filesize($path . $file);
        }
        $size = $this->formatSize($size);
        return [
            'files' => $files,
            'size' => $size
        ];
    }

    public  function deleteFiles($files) {
        $path = $this->getBackupDir();
        foreach ($files as $file) {
            $filePath = $path . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function uploadToS3($filePath, $bucketName, $key, $secret, $region) {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => $region,
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
        ]);

        try {
            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key'    => basename($filePath),
                'Body'   => fopen($filePath, 'r'),
                'ACL'    => 'private', // or 'public-read' based on your needs
            ]);
            return $result['ObjectURL']; // Returns the URL of the uploaded object
        } catch (S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
    }

    public function uploadToSFTP($filePath, $server, $username, $password, $remotePath) {
        $a = 'b';
        $sftp = new SFTP($server);
        if (!$sftp->login($username, $password)) {
            exit('Login Failed');
        }
        if ($remotePath) {
            $sftp->chdir($remotePath);
        }
        $sftp->put(basename($filePath), $filePath, SFTP::SOURCE_LOCAL_FILE);
    }

    public function syncFile($filePath) {
        $settingsHelper = new SettingsHelper();
        $deSettings = $settingsHelper->getDataExportSettings();
        $syncSettings = [
            'syncExternal' => $deSettings['syncExternal'],
            'syncOption' => $deSettings['syncOption'],
            'syncFilePath' => $deSettings['syncFilePath'],
            'syncBucketName' => $deSettings['syncBucketName'],
            'syncKey' => $deSettings['syncKey'],
            'syncSecret' => $deSettings['syncSecret'],
            'syncRegion' => $deSettings['syncRegion']
        ];
        $a = 'b';
        if ($syncSettings['syncExternal']) {
            if ($syncSettings['syncOption'] == 's3') {
                return $this->uploadToS3($filePath, $syncSettings['syncBucketName'], $syncSettings['syncKey'], $syncSettings['syncSecret'], $syncSettings['syncRegion']);
            } elseif ($syncSettings['syncOption'] == 'sftp') {
                return $this->uploadToSFTP($filePath, $syncSettings['syncBucketName'], $syncSettings['syncKey'], $syncSettings['syncSecret'], $syncSettings['syncFilePath']);
            }
        }
    }
}
