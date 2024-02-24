<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Plugins\DataExport\Services\FileService;

class Tasks extends \Piwik\Plugin\Tasks
{
    public function schedule()
    {
        $this->daily('cleanBackupsFolderTask', null, self::LOWEST_PRIORITY);
    }

    public function cleanBackupsFolderTask($force = null)
    {
        $fileService = new FileService();
        $fileService->cleanBackupsFolder($force);
    }

}
