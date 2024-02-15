<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

class DataExport extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'CronArchive.getArchivingAPIMethodForPlugin' => 'getArchivingAPIMethodForPlugin',
            'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
        ];
    }

    // support archiving just this plugin via core:archive
    public function getArchivingAPIMethodForPlugin(&$method, $plugin)
    {
        if ($plugin == 'DataExport') {
            $method = 'DataExport.getExampleArchivedMetric';
        }
    }

    public function getJavaScriptFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/DataExport/javascripts/data-export.js';
    }
}
