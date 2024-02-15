<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

 namespace Piwik\Plugins\DataExport\Helpers;

class PHPHelper
{
    public static function getPhpSettings() {
        $memoryLimit = ini_get('memory_limit');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
    
        return [
            'memory_limit' => $memoryLimit,
            'upload_max_filesize' => $uploadMaxFilesize,
            'post_max_size' => $postMaxSize,
        ];
    }
}