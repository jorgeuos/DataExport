<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Plugins\DataExport\Services\FileService;
use Piwik\Plugins\DataExport\SystemSettings;
use Piwik\Plugins\DataExport\Services\DatabaseDumpService;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;

class Tasks extends \Piwik\Plugin\Tasks
{

    /** @var Setting */
    private $settings;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(?LoggerInterface $logger = null) {
        $this->logger = $logger ?: StaticContainer::get(LoggerInterface::class);
        $this->settings = new SystemSettings();
    }

    public function schedule()
    {
        $this->daily('cleanBackupsFolderTask', null, self::LOWEST_PRIORITY);

        // Separate task methods: scheduling the same method twice made the
        // daily schedule also run "weekly" dumps every day.
        $this->daily('dailyDatabaseDumpTask', null, self::NORMAL_PRIORITY);

        $this->weekly('weeklyDatabaseDumpTask', null, self::NORMAL_PRIORITY);

    }

    public function cleanBackupsFolderTask($force = null)
    {
        $fileService = new FileService();
        $fileService->cleanBackupsFolder($force);
    }

    private function runDatabaseDump()
    {
        $service = new DatabaseDumpService();
        $compress = $this->settings->dataExportAutoDumpCompression->getValue();
        $dumpPath = $service->generateDump($compress, null);
        $fileService = new FileService();
        $sync = $this->settings->dataExportSyncExternal->getValue();
        if ($sync) {
            $this->logger->info('Syncing dump to external location');
            $fileService->syncFile($dumpPath);
        }
        $this->logger->info('Dump generated at: ' . $dumpPath);
    }

    public function dailyDatabaseDumpTask()
    {
        if ($this->settings->dataExportAutoDump->getValue() === 'daily') {
            $this->logger->info('Starting daily database dump task');
            $this->runDatabaseDump();
        }
    }

    public function weeklyDatabaseDumpTask()
    {
        if ($this->settings->dataExportAutoDump->getValue() === 'weekly') {
            $this->logger->info('Starting weekly database dump task');
            $this->runDatabaseDump();
        }
    }

    /**
     * @deprecated Kept so a task scheduled by an older version still resolves.
     */
    public function databaseDumpTask()
    {
        $this->dailyDatabaseDumpTask();
    }

}
