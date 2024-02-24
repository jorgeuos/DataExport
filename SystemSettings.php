<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\Validators\NotEmpty;

/**
 * Defines Settings for DataExport.
 *
 * Usage like this:
 * $settings = new SystemSettings();
 * $settings->metric->getValue();
 * $settings->description->getValue();
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $dataExportBackupPath;

    protected function init()
    {
        // System setting --> textinput
        $this->password = $this->createPathSetting();
    }

    private function createPathSetting()
    {
        return $this->makeSetting('dataExportBackupPath', $default = null, FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'DataExport Backup Folder Path';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = 'Change the default backup folder path.';
        });
    }
}
