<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use \Piwik\Plugins\DataExport\Services\DatabaseDumpService;
use \Piwik\Plugins\DataExport\Services\FileService;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;


/**
 * This class lets you define a new command. To read more about commands have a look at our Matomo Console guide on
 * https://developer.matomo.org/guides/piwik-on-the-command-line
 *
 * As Matomo Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class DbDump extends ConsoleCommand {
    /**
     * This method allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure() {
        $this->setName('db:dump');
        $this->setDescription('Dumps the database to a file.');
        $this->addOptionalValueOption(
            'dest',
            'd',
            'Default destination $MATOMO_ROOT/tmp/de_backups/{dbdump}-{Y-m-d_H-i-s}.sql'
        );
        $this->addOptionalValueOption(
            'compress',
            'c',
            'Whether to compress the dump file as a .zip or .tar.gz archive. Defaults to none.',
            'none'
        );
        $this->addNegatableOption(
            'sync',
            's',
            'Default no sync. If set, syncs the dump file to an external location. Set in your config.',
            false
        );
    }

    /**
     * Run the DB dump command.
     */
    protected function doExecute(): int {
        $input = $this->getInput();
        $output = $this->getOutput();

        /** @var LoggerInterface $logger */
        $logger = StaticContainer::get(LoggerInterface::class);

        // BC with API defining a protected constructor
        $logger->info('Starting db:dump command');

        $fileService = new FileService($logger);

        $name = $input->getOption('dest');
        if (!empty($name) && dirname($name) != '.') {
            $logger->info('Creating directory: ' . dirname($name));
            try {
                $fileService->ensure_directory_exists(dirname($name));
            } catch (\Exception $e) {
                $logger->error('Failed to create directory: ' . dirname($name));
                $logger->error($e->getMessage());
                return self::FAILURE;
            }
        }
        $logger->info('Destination: ' . $name);

        $compress = $input->getOption('compress');
        if (!$compress) {
            $logger->error('Please specify a compression format: none, zip, or tar.gz');
            return self::FAILURE;
        }
        $logger->info('Compress: ' . $compress);

        $compress = filter_var($compress, FILTER_VALIDATE_BOOLEAN);

        try {
            $service = new DatabaseDumpService();
            $dumpPath = $service->generateDump($compress, $name);
            $logger->info('Dump generated at: ' . $dumpPath);
        } catch (\Exception $e) {
            $logger->error('Failed to generate dump');
            $logger->error($e->getMessage());
            return self::FAILURE;
        }

        $sync = $input->getOption('sync');
        if ($sync) {
            $logger->info('Syncing file...');
            $fileService->syncFile($dumpPath);
        }

        $message = sprintf('<info>DbDumped: %s</info>', basename($dumpPath));

        $output->writeln($message);

        return self::SUCCESS;
    }
}
