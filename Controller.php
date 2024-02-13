<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Piwik;

/**
 * A controller lets you for example create a page that can be added to a menu. For more information read our guide
 * http://developer.piwik.org/guides/mvc-in-piwik or have a look at the our API references for controller and view:
 * http://developer.piwik.org/api-reference/Piwik/Plugin/Controller and
 * http://developer.piwik.org/api-reference/Piwik/View
 */
class Controller extends \Piwik\Plugin\ControllerAdmin
{
    public function index()
    {
        // Check that the user has the necessary permissions
        // For now a logged in user is enough
        Piwik::checkUserIsNotAnonymous();

        // Render the Twig template templates/index.twig and assign the view variable answerToLife to the view.
        return $this->renderTemplate(
            'index',
            array(
                'title' => Piwik::translate('DataExport_DataExport'),
                'answerToLife' => 42
            )
        );
    }

    /**
     * Handles sending a file to the client for download.
     *
     * @param string $filePath The path to the file to be downloaded.
     * @throws \Exception If the file does not exist or is not readable.
     */
    protected function downloadFile($filePath)
    {
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
    public function downloadDbDump()
    {
        Piwik::checkUserHasSuperUserAccess();
        try {
            $service = new \Piwik\Plugins\DataExport\Services\DatabaseDumpService();
            $dumpPath = $service->generateDump();
    
            $this->downloadFile($dumpPath);
            unlink($dumpPath);
        } catch (\Exception $e) {
            // Handle the error appropriately
            echo $e->getMessage();
        }
    }
}
