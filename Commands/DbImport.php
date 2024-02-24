<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use \Piwik\Plugins\DataExport\Services\DatabaseImportService;

/**
 * This class lets you define a new command. To read more about commands have a look at our Matomo Console guide on
 * https://developer.matomo.org/guides/piwik-on-the-command-line
 *
 * As Matomo Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class DbImport extends ConsoleCommand {
    /**
     * This method allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure() {
        $this->setName('db:import');
        $this->setDescription('DbImport');
        $this->addRequiredArgument(
            'filename',
            'The name of the import'
        );
        $this->addNegatableOption(
            'force',
            'f',
            'Force the import',
            false
        );
    }

    protected function doExecute(): int {
        $input = $this->getInput();
        $output = $this->getOutput();

        $filename = $input->getArgument('filename');
        $force = $input->getOption('force');

        $question = sprintf('<comment>This will delete the current database, wish to continue? (Y/N): </comment>');
        if (!$force && !$this->askForConfirmation($question, false)) {
            $output->writeln('<info>Cool, nothing done.</info>');
            return self::SUCCESS;
        }

        $message = sprintf('<info>Importing DB: %s</info>', $filename);
        $output->writeln($message);

        $service = new DatabaseImportService();
        $success = $service->importDump($filename);
        if ($success) {
            $output->writeln('<info>DB imported successfully</info>');
            return self::SUCCESS;
        } else {
            $output->writeln('<error>DB import failed</error>');
            return self::FAILURE;
        }
    }
}
