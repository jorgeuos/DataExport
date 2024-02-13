<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

class DatabaseDumpService
{
    public function generateDump()
    {
        $dbConfig = \Piwik\Config::getInstance()->database;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];

        $dumpPath = sys_get_temp_dir() . '/dbdump-' . date('Y-m-d_H-i-s') . '.sql';
        $command = sprintf(
            'mysqldump -u %s -p\'%s\' %s > %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbPassword),
            escapeshellarg($dbName),
            escapeshellarg($dumpPath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Failed to generate database dump.");
        }

        return $dumpPath;
    }
}
