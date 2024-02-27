<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Piwik;
use Piwik\Access;
use Piwik\Request;
use Piwik\DataTable;
use Piwik\DataTable\DataTableInterface;
use Piwik\Plugins\DataExport\Archiver\ExampleMetric;
use \Piwik\Plugins\DataExport\Services\FileService;
use \Piwik\Plugins\DataExport\Services\DatabaseDumpService;

/**
 * API for plugin DataExport
 *
 * @method static \Piwik\Plugins\DataExport\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * @return DataTable
     */
    public function selectAllVisitsAndActions($idSite = 0, $date = 'yesterday')
    {
        Piwik::checkUserHasSomeAdminAccess();

        // Prefer post parameters over request parameters
        $idSite = Request::fromPost()->getIntegerParameter('idSite', 0);
        if (!$idSite || $idSite == 0) {
            $idSite = Request::fromRequest()->getIntegerParameter('idSite', 0);
        }

        // If no site is specified, check for super user access to allow access to all sites
        if (!$idSite || $idSite == 0) {
            Piwik::checkUserHasSuperUserAccess();
        }
        else {
            // Otherwise, check for admin access to the specified site
            Piwik::checkUserHasAdminAccess($idSite);
        }

        // Prefer post parameters over request parameters
        $date = Request::fromPost()->getStringParameter('date', 'yesterday');
        if (!$date || $date == 'yesterday') {
            $date = Request::fromRequest()->getStringParameter('date', 'yesterday');
        }

        // Get the data
        $dbService = new DatabaseDumpService();
        $data = $dbService->selectAllVisitsAndActionsData($date, $idSite);
        if (!$data) {
            return 'No data found';
        }

        // Create a DataTable from the data
        $dataTable = new DataTable();
        $dataTable->addRowsFromSimpleArray($data);
        return $dataTable;
    }

}
