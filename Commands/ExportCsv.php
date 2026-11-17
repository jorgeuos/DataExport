<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\DataExport\Services\DatabaseDumpService;
use Piwik\Plugins\DataExport\Services\FileService;

class ExportCsv extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('dataexport:csv');
        $this->setDescription('Export all visits and actions for one day to a CSV file.');

        $this->addOptionalValueOption(
            'dest',
            'd',
            'Output file. A bare file name is written to the backup folder. Defaults to dump-{date}-{sites}-{now}.csv in the backup folder.'
        );
        $this->addOptionalValueOption(
            'report',
            'r',
            'Which report to export. Only selectAllVisitsAndActions is available.',
            'selectAllVisitsAndActions'
        );
        $this->addOptionalValueOption(
            'date',
            null,
            'Date to export (YYYY-MM-DD, "today" or "yesterday"). Defaults to yesterday.',
            'yesterday'
        );
        $this->addOptionalValueOption(
            'idsite',
            'i',
            'Only export this site ID. Defaults to all sites.'
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $dest = $input->getOption('dest');
        if (!empty($dest) && dirname($dest) === '.') {
            $fileService = new FileService();
            $dest = $fileService->getBackupDir() . $dest;
        }

        $date = $input->getOption('date') ?: 'yesterday';
        $siteId = $input->getOption('idsite');
        $siteId = $siteId !== null && $siteId !== '' ? (int) $siteId : null;

        $databaseDumpService = new DatabaseDumpService();
        $path = $databaseDumpService->selectAllVisitsAndActions($dest ?: null, $date, $siteId);

        if (!$path) {
            $output->writeln('<comment>ExportCsv: no data found for the given date, no file written.</comment>');
            return self::SUCCESS;
        }

        $output->writeln(sprintf('<info>ExportCsv: CSV created at %s</info>', $path));

        return self::SUCCESS;
    }
}
