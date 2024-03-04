<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Helpers;

use Piwik\Plugins\DataExport\SystemSettings;

class SettingsHelper
{
    private $settings;
    
    public function __construct(){
        $this->settings = new SystemSettings();
    }

    public function getDataExportSettings() {
        return [
            'backupPath' => $this->settings->dataExportBackupPath->getValue(),
            'autoDump' => $this->settings->dataExportAutoDump->getValue(),
            'autoDumpCompression' => $this->settings->dataExportAutoDumpCompression->getValue(),
            'syncExternal' => $this->settings->dataExportSyncExternal->getValue(),
            'syncOption' => $this->settings->dataExportSyncOption->getValue(),
            'syncFilePath' => $this->settings->dataExportSyncFilePath->getValue(),
            'syncBucketName' => $this->settings->dataExportSyncBucketName->getValue(),
            'syncKey' => $this->settings->dataExportSyncKey->getValue(),
            'syncSecret' => $this->settings->dataExportSyncSecret->getValue(),
            'syncRegion' => $this->settings->dataExportSyncRegion->getValue()
        ];
    }
}