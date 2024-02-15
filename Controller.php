<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Piwik;
use \Piwik\Plugins\DataExport\Services\DatabaseDumpService;
use \Piwik\Plugins\DataExport\Services\DatabaseImportService;
use Piwik\Notification\Manager as NotificationManager;
use Piwik\Notification;
use Piwik\Url;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use \Piwik\Plugins\DataExport\Helpers\UserHelper;


/**
 * A controller lets you for example create a page that can be added to a menu. For more information read our guide
 * http://developer.piwik.org/guides/mvc-in-piwik or have a look at the our API references for controller and view:
 * http://developer.piwik.org/api-reference/Piwik/Plugin/Controller and
 * http://developer.piwik.org/api-reference/Piwik/View
 */
class Controller extends \Piwik\Plugin\ControllerAdmin {
    public function index() {
        // Check that the user has the necessary permissions
        // For now a logged in user is enough
        Piwik::checkUserIsNotAnonymous();

        $zipPreference = UserHelper::getUserPreference('zipDownloadPreference', false);

        $dbConfig = \Piwik\Config::getInstance()->database;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbHost = $dbConfig['host'];

        // Render the Twig template templates/index.twig and assign the view variable answerToLife to the view.
        return $this->renderTemplate(
            'index',
            array(
                'title' => Piwik::translate('DataExport_DataExport'),
                'import_title' => Piwik::translate('DataExport_ImportData'),
                'zipPreference' => $zipPreference,
                'dbName' => $dbName,
                'dbUser' => $dbUser,
                'dbHost' => $dbHost
            )
        );
    }

    /**
     * Handles sending a file to the client for download.
     *
     * @param string $filePath The path to the file to be downloaded.
     * @throws \Exception If the file does not exist or is not readable.
     */
    protected function downloadFile($filePath) {
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
     * Downloads a DB dump
     */
    public function downloadDbDump() {
        Piwik::checkUserHasSuperUserAccess();
        
        $zipDownload = !empty($_POST['zipDownload']);
        UserHelper::setUserPreference('zipDownloadPreference', $zipDownload);

        try {
            $service = new DatabaseDumpService();
            $dumpPath = $service->generateDump($zipDownload);

            $this->downloadFile($dumpPath);
            unlink($dumpPath);
        } catch (\Exception $e) {
            // Handle the error appropriately
            echo $e->getMessage();
        }
    }

    public function handleDbUpload() {
        Piwik::checkUserHasSuperUserAccess();

        if (!empty($_FILES['dbDump']['name'])) {
            $uploadDir = PIWIK_USER_PATH . '/tmp/de_uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmpPath = $_FILES['dbDump']['tmp_name'];
            $fileName = $_FILES['dbDump']['name'];
            $fileSize = $_FILES['dbDump']['size'];
            $fileType = $_FILES['dbDump']['type'];
            $error = $_FILES['dbDump']['error'];

            // Validate file (size, type, error status)
            if ($error > 0) {
                throw new \Exception("Error uploading file. Error code: $error.");
            }
            if ($fileSize > 20971520) { // Example size limit: 20MB
                throw new \Exception("File size exceeds limit.");
            }
            if (!in_array($fileType, [
                'application/octet-stream',
                'application/sql',
                'text/plain'
            ])) {
                throw new \Exception("Invalid file type.");
            }
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($fileExt != 'sql') {
                throw new \Exception("Invalid file extension.");
            }

            $destPath = $uploadDir . $fileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Validate file content
                $handle = fopen($destPath, "r");
                if ($handle) {
                    $firstLine = fgets($handle);
                    if (!preg_match('/^-- (MariaDB|MySQL) dump \d+/', $firstLine)) {
                        throw new \Exception("File does not appear to be a valid MySQL dump.");
                    }
                    fclose($handle);
                } else {
                    throw new \Exception("Unable to read uploaded file.");
                }
                $service = new DatabaseImportService();
                $success = $service->importDump($destPath);
                if ($success) {
                    $notification = new Notification(Piwik::translate('DataExport_ImportSuccessMessage'));
                    $notification->context = Notification::CONTEXT_SUCCESS;
                    NotificationManager::notify('DataExport_ImportSuccess', $notification);
                    $urlToRedirect = Url::getCurrentUrlWithoutQueryString();
                    Url::redirectToUrl($urlToRedirect);
                }
            } else {
                throw new \Exception("Failed to move uploaded file.");
            }
        } else {
            throw new \Exception("No file uploaded.");
        }
    }
}
