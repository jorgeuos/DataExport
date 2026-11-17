<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\DataExport\Services\BackupJobService;

/**
 * Runs a backup started from the admin page. Started in the background by
 * BackupJobService, not meant to be run by hand (use db:dump instead).
 */
class BackupJob extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('dataexport:backup-job');
        $this->setDescription('Internal: runs a database backup started from the DataExport admin page. Use db:dump instead.');
        $this->addRequiredArgument('job', 'Job ID');
    }

    protected function doExecute(): int
    {
        $service = new BackupJobService();
        $ok = $service->run((string) $this->getInput()->getArgument('job'));
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
