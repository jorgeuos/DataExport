<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\DataExport\Services\FileService;

class CleanBackups extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('dataexport:clean-backups');
        $this->setDescription('CleanBackups');
        $this->addNegatableOption(
            'force',
            'f',
            'Force cleanup',
            false
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $force = $input->getOption('force');

        $question = sprintf('<comment>This will delete all the backups? (Y/N): </comment>');
        if (!$force && !$this->askForConfirmation($question, false)) {
            $output->writeln('<info>Cool, nothing done.</info>');
            return self::SUCCESS;
        }

        // cleanBackupsFolder
        $fileService = new FileService();
        $fileService->cleanBackupsFolder($force);

        $message = sprintf('<info>CleanBackups: Backup folder cleaned.</info>');

        $output->writeln($message);

        return self::SUCCESS;
    }
}
