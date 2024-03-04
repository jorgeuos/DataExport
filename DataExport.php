<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * Plugin Name: Data Export (Matomo Plugin)
 * Plugin URI: http://plugins.matomo.org/DataExport
 * Description: Export database or connect to your BI tools.
 * Author: Jorgeuos
 * Author URI: https://jorgeuos.com
 * Version: 1.1.0
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

class DataExport extends \Piwik\Plugin {
    public function registerEvents() {
        return [
            'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
        ];
    }

    public function getJavaScriptFiles(&$jsFiles) {
        $jsFiles[] = 'plugins/DataExport/javascripts/data-export.js';
    }

}
