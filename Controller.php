<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Url;
use Piwik\Piwik;
use Piwik\Request;
use Piwik\Notification;
use \Piwik\Plugins\DataExport\Helpers\PHPHelper;
use \Piwik\Plugins\DataExport\Helpers\UserHelper;
use \Piwik\Plugins\DataExport\Helpers\SettingsHelper;
use \Piwik\Plugins\DataExport\Services\FileService;
use Piwik\Notification\Manager as NotificationManager;
use \Piwik\Plugins\DataExport\Services\DatabaseDumpService;
use \Piwik\Plugins\DataExport\Services\DatabaseImportService;

/**
 * A controller lets you for example create a page that can be added to a menu. For more information read our guide
 * http://developer.piwik.org/guides/mvc-in-piwik or have a look at the our API references for controller and view:
 * http://developer.piwik.org/api-reference/Piwik/Plugin/Controller and
 * http://developer.piwik.org/api-reference/Piwik/View
 */
class Controller extends \Piwik\Plugin\ControllerAdmin {

    public function __construct()
    {
        if (file_exists(PIWIK_INCLUDE_PATH . '/plugins/DataExport/vendor/autoload.php')) {
            require_once PIWIK_INCLUDE_PATH . '/plugins/DataExport/vendor/autoload.php';
        }
    }

    public function index() {
        return $this->indexHome();
    }

    public function indexHome() {
        $downloadPreference = UserHelper::getUserPreference('downloadPreference', 'none');

        $dbConfig = \Piwik\Config::getInstance()->database;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbHost = $dbConfig['host'];

        $phpSettings = PHPHelper::getPhpSettings();
        $fileService = new FileService();
        $files = $fileService->getFilesInBackupDir();
        $settingsHelper = new SettingsHelper();
        $settings = $settingsHelper->getDataExportSettings();

        // Render the Twig template templates/index.twig and assign the view variable answerToLife to the view.
        return $this->renderTemplate(
            'index',
            array(
                'title' => Piwik::translate('DataExport_DataExport'),
                'import_title' => Piwik::translate('DataExport_ImportData'),
                'csv_title' => Piwik::translate('DataExport_CsvExport'),
                'downloadPreference' => $downloadPreference,
                'dbName' => $dbName,
                'dbUser' => $dbUser,
                'dbHost' => $dbHost,
                'phpSettings' => $phpSettings,
                'backupPath' => $settings['backupPath'],
                'files' => $files['files'],
                'totalFilesSize' => $files['size'],
                'clear_backups' => Piwik::translate('DataExport_ClearBackups'),
                'settings' => Piwik::translate('DataExport_Settings'),
                'autoDump' => 'none',
            )
        );
    }

    /**
     * Handles sending a file to the client for download.
     *
     * @param string $filePath The path to the file to be downloaded.
     * @throws \Exception If the file does not exist or is not readable.
     */
    public function downloadFile($filePath = null) {
        Piwik::checkUserHasSuperUserAccess();
        $fileService = new FileService();

        if (!$filePath) {
            $filePath = Request::fromRequest()->getStringParameter('file', '');
        }

        // Make sure the file path is not just a file name
        if ($filePath == basename($filePath)) {
            $filePath = $fileService->getBackupDir() . $filePath;
        }

        if (!$filePath || !file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("File does not exist or is not readable.");
        }


        $fileService->downloadFile($filePath);
    }

    /**
     * Downloads a DB dump
     */
    public function downloadDbDump() {
        Piwik::checkUserHasSuperUserAccess();

        // Correctly escape the user input
        $downloadPreference = !empty($_POST['download-preference']) ? htmlspecialchars($_POST['download-preference'], ENT_QUOTES, 'UTF-8') : 'none';

        UserHelper::setUserPreference('downloadPreference', $downloadPreference);

        try {
            $service = new DatabaseDumpService();
            $dumpPath = $service->generateDump($downloadPreference);

            $this->downloadFile($dumpPath);
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
            // if ($fileSize > 20971520) { // Example size limit: 20MB
            // Set to 1024MB
            if ($fileSize > 1073741824) {
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
                    $url = Url::getCurrentQueryStringWithParametersModified([
                        'module' => 'DataExport',
                        'action' => 'index'
                    ]);
                    Url::redirectToUrl('index.php' . $url);
                }
            } else {
                throw new \Exception("Failed to move uploaded file.");
            }
        } else {
            throw new \Exception("No file uploaded.");
        }
    }

    public function selectAllVisitsAndActions() {
        Piwik::checkUserHasSuperUserAccess();

        $siteId = Request::fromRequest()->getIntegerParameter('idSite', 0);
        $date = Request::fromPost()->getStringParameter('date', 'yesterday');
        if (!$date || $date == 'yesterday') {
            $date = Request::fromRequest()->getStringParameter('date', 'yesterday');
        }

        try {
            $service = new DatabaseDumpService();
            $dumpPath = $service->selectAllVisitsAndActions(null, $date, $siteId);
            if (!$dumpPath) {
                $notification = new Notification(Piwik::translate('DataExport_CsvEmptyMessage'));
                $notification->context = Notification::CONTEXT_WARNING;
                NotificationManager::notify('DataExport_CsvEmptyMessage', $notification);
                $url = Url::getCurrentQueryStringWithParametersModified([
                    'module' => 'DataExport',
                    'action' => 'index'
                ]);
                Url::redirectToUrl('index.php' . $url);
            }
            $this->downloadFile($dumpPath);
        } catch (\Exception $e) {
            // Handle the error appropriately
            echo $e->getMessage();
        }
    }

    public function deleteFiles($files = null) {
        Piwik::checkUserHasSuperUserAccess();
        $files = Request::fromPost()->getArrayParameter('files', []);
        $service = new FileService();
        $service->deleteFiles($files);
        $notification = new Notification(Piwik::translate('DataExport_FilesDeletedMessage'));
        $notification->context = Notification::CONTEXT_SUCCESS;
        return $this->indexHome();
    }
}
