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
use Piwik\Piwik;

/**
 * Defines Settings for DataExport.
 *
 * Usage like this:
 * $settings = new SystemSettings();
 * $settings->dataExportBackupPath->getValue();
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings {
    /** @var Setting */
    public $dataExportBackupPath;

    /** @var Setting */
    public $dataExportAutoDump;

    /** @var Setting */
    public $dataExportAutoDumpCompression;

    /** @var Setting */
    public $dataExportSyncExternal;

    /** @var Setting */
    public $dataExportSyncOption;

    /** @var Setting */
    public $dataExportSyncFilePath;

    /** @var Setting */
    public $dataExportSyncBucketName;

    /** @var Setting */
    public $dataExportSyncKey;

    /** @var Setting */
    public $dataExportSyncSecret;

    /** @var Setting */
    public $dataExportSyncRegion;

    /** @var Setting */
    public $dataExportSyncSettings;

    protected function init() {
        $this->dataExportBackupPath = $this->createPathSetting();
        $this->dataExportAutoDump = $this->createAutoDumpSetting();
        $this->dataExportAutoDumpCompression = $this->createAutoDumpCompressionSetting();
        $this->dataExportSyncExternal = $this->createSyncExternalSetting();
        $this->dataExportSyncOption = $this->createSyncOptionSetting();
        $this->dataExportSyncFilePath = $this->createSyncFilePathSetting();
        $this->dataExportSyncBucketName = $this->createSyncBucketNameSetting();
        $this->dataExportSyncKey = $this->createSyncKeySetting();
        $this->dataExportSyncSecret = $this->createSyncSecretSetting();
        $this->dataExportSyncRegion = $this->createSyncRegionSetting();
    }

    private function createPathSetting() {
        return $this->makeSetting('dataExportBackupPath', $default = null, FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_BackupFolderPath');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Piwik::translate('DataExport_BackupPathDescription');
        });
    }

    private function createAutoDumpSetting() {
        return $this->makeSetting('dataExportAutoDump', $default = 'none', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->introduction = Piwik::translate('DataExport_AutoDumpIntro');
            $field->title = Piwik::translate('DataExport_AutoDump');
            $field->uiControl = FieldConfig::UI_CONTROL_RADIO;
            $field->availableValues = [
                'none' => Piwik::translate('DataExport_AutoDumpNone'),
                'daily' => Piwik::translate('DataExport_AutoDumpDaily'),
                'weekly' => Piwik::translate('DataExport_AutoDumpWeekly'),
            ];
            $field->description = Piwik::translate('DataExport_AutoDumpDescription');
        });
    }

    private function createAutoDumpCompressionSetting() {
        return $this->makeSetting('dataExportAutoDumpCompression', $default = 'none', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_AutoDumpCompression');
            $field->uiControl = FieldConfig::UI_CONTROL_RADIO;
            $field->availableValues = [
                'none' => Piwik::translate('DataExport_AutoDumpNone'),
                'daily' => Piwik::translate('DataExport_AutoDumpCompressionZip'),
                'weekly' => Piwik::translate('DataExport_AutoDumpCompressionTarGz'),
            ];
            $field->description = Piwik::translate('DataExport_AutoDumpCompression');
        });
    }

    private function createSyncExternalSetting() {
        return $this->makeSetting('dataExportSyncExternal', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->introduction = Piwik::translate('DataExport_SyncSettings');
            $field->title = Piwik::translate('DataExport_SyncFileExternal');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->description = Piwik::translate('DataExport_SyncFileExternalDesc');
        });
    }

    private function createSyncOptionSetting() {
        return $this->makeSetting('dataExportSyncOption', $default = 's3', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_SyncOption');
            $field->uiControl = FieldConfig::UI_CONTROL_RADIO;
            $field->availableValues = [
                's3' => Piwik::translate('DataExport_SyncOptionS3'),
                'sftp' => Piwik::translate('DataExport_SyncOptionSFTP'),
            ];
            $field->description = Piwik::translate('DataExport_SyncOptionDesc');
            $field->condition = 'dataExportSyncExternal == 1';
        });
    }

    private function createSyncFilePathSetting() {
        return $this->makeSetting('dataExportSyncFilePath', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_SyncFilePath');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Piwik::translate('DataExport_SyncFilePathDesc');
            $field->condition = 'dataExportSyncExternal == 1';
        });
    }

    private function createSyncBucketNameSetting() {
        return $this->makeSetting('dataExportSyncBucketName', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_SyncBucketName');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Piwik::translate('DataExport_SyncBucketNameDesc');
            $field->condition = 'dataExportSyncExternal == 1';
        });
    }

    private function createSyncKeySetting() {
        return $this->makeSetting('dataExportSyncKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_SyncKey');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Piwik::translate('DataExport_SyncKeyDesc');
            $field->condition = 'dataExportSyncExternal == 1';
        });
    }

    private function createSyncSecretSetting() {
        return $this->makeSetting('dataExportSyncSecret', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_SyncSecret');
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
            $field->description = Piwik::translate('DataExport_SyncSecretDesc');
            $field->condition = 'dataExportSyncExternal == 1';
        });
    }

    private function createSyncRegionSetting() {
        return $this->makeSetting('dataExportSyncRegion', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DataExport_SyncRegion');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Piwik::translate('DataExport_SyncRegionDesc');
            $field->condition = 'dataExportSyncExternal == 1';
        });
    }
}
