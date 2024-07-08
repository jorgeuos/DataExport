<?php
namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;
use Piwik\Plugins\DataExport\Services\DatabaseImportService;

/**
 * Import logs.
 */
class ImportLogs extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('db:import-logs');
        $this->setDescription('Import logs from a file. Experimental. Do not use in production.');
        $this->addRequiredValueOption(
            'source',
            's',
            'Source file to import.'
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        /** @var LoggerInterface $logger */
        $logger = StaticContainer::get(LoggerInterface::class);
        $logger->info('Starting db:import-logs command');

        $source = $input->getOption('source');

        try {
            $service = new DatabaseImportService();
            $service->importLogDumps($source);
            $logger->info('Logs imported successfully from: ' . $source);
        } catch (\Exception $e) {
            $logger->error('Failed to import logs');
            $logger->error($e->getMessage());
            return self::FAILURE;
        }

        $output->writeln('<info>Logs imported successfully</info>');

        return self::SUCCESS;
    }
}
