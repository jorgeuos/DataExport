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
        $this->setDescription('ExportCsv');
        
        $this->addOptionalValueOption(
            'dest',
            'd',
            'Output destination',
        );
        $this->addOptionalValueOption(
            'report',
            'r',
            'Which report to export. Defaults to selectAllVisitsAndActions',
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $dest = $input->getOption('dest');
        if (!empty($dest) && dirname($dest) != '.') {
            $fileService = new FileService();
            $dest = $fileService->getBackupDir() . $dest;
        }

        $databaseDumpService = new DatabaseDumpService();
        $databaseDumpService->selectAllVisitsAndActions($dest);

        $message = sprintf('<info>ExportCsv: CSV created.</info>');
        $output->writeln($message);

        return self::SUCCESS;
    }
}
