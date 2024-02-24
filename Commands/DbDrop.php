<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use \Piwik\Plugins\DataExport\Services\DatabaseService;

/**
 * This class lets you define a new command. To read more about commands have a look at our Matomo Console guide on
 * https://developer.matomo.org/guides/piwik-on-the-command-line
 *
 * As Matomo Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class DbDrop extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('db:drop');
        $this->setDescription('DbDrop');
        $this->addNegatableOption(
            'force',
            'f',
            'Force the import',
            false
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $force = $input->getOption('force');

        $dbConfig = \Piwik\Config::getInstance()->database;
        $dbName = $dbConfig['dbname'];

        $message = sprintf('<info>Dropping database: %s</info>', $dbName);
        $output->writeln($message);


        $question = sprintf('<comment>This will delete the current database, wish to continue? (Y/N): </comment>');
        if (!$force && !$this->askForConfirmation($question, false)) {
            $output->writeln('<info>Cool, nothing done.</info>');
            return self::SUCCESS;
        }

        $databaseService = new DatabaseService();
        $databaseService->dropDB();
        $message = sprintf('<info>Database %s dropped.</info>', $dbName);
        $output->writeln($message);

        return self::SUCCESS;
    }
}
