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

class DbCreate extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('db:create');
        $this->setDescription('DbCreate');
        $dbConfig = $dbConfig = \Piwik\Config::getInstance()->database;
        $dbName = $dbConfig['dbname'];
        

        $this->addOptionalValueOption(
            'dbname',
            'd',
            'The name of the database',
            $dbName
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $dbname = $input->getOption('dbname');

        $message = sprintf('<info>DbCreate: %s</info>', $dbname);
        $output->writeln($message);

        $databaseService = new DatabaseService();
        $databaseService->createDB($dbname);

        return self::SUCCESS;
    }
}
