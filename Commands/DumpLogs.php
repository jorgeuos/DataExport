<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;
use \Piwik\Plugins\DataExport\Services\DatabaseDumpService;
use \Piwik\Plugins\DataExport\Services\FileService;

/**
 * Dump logs only.
 */
class DumpLogs extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('db:dump-logs');
        $this->setDescription('DumpLogs into a file.');
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
        $this->addOptionalValueOption(
            'tables',
            't',
            'Comma-separated list of tables to dump. Defaults to log_visit,log_link_visit_action,log_action,log_conversion,log_conversion_item.',
            'log_visit,log_link_visit_action,log_action,log_conversion,log_conversion_item'
        );
        $this->addOptionalValueOption(
            'date',
            'D',
            'Date for filtering the logs. Format: YYYY-MM-DD. Defaults to yesterday.',
            null
        );
        $this->addOptionalValueOption(
            'siteids',
            'i',
            'Comma-separated list of site IDs for filtering the logs. Defaults to all site IDs.',
            null
        );
    }

    protected function doInteract(): void
    {
    }

    protected function doInitialize(): void
    {
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        /** @var LoggerInterface $logger */
        $logger = StaticContainer::get(LoggerInterface::class);
        $logger->info('Starting db:dump-logs command');

        $fileService = new FileService($logger);

        $dest = $input->getOption('dest');
        if (!empty($dest) && dirname($dest) != '.') {
            $logger->info('Creating directory: ' . dirname($dest));
            try {
                $fileService->ensure_directory_exists(dirname($dest));
            } catch (\Exception $e) {
                $logger->error('Failed to create directory: ' . dirname($dest));
                $logger->error($e->getMessage());
                return self::FAILURE;
            }
        }
        $logger->info('Destination: ' . $dest);

        $compress = $input->getOption('compress');
        if (!$compress) {
            $logger->error('Please specify a compression format: none, zip, or tar.gz');
            return self::FAILURE;
        }
        $logger->debug('Compress: ' . $compress);

        // Available tables:
        // log_abtesting
        // log_action
        // log_clickid
        // log_conversion
        // log_conversion_item
        // log_crash
        // log_crash_event
        // log_crash_group
        // log_crash_stack
        // log_form
        // log_form_field
        // log_form_page
        // log_funnel
        // log_hsr
        // log_hsr_blob
        // log_hsr_event
        // log_hsr_site
        // log_link_visit_action
        // log_media
        // log_media_plays
        // log_performance
        // log_profiling
        // log_visit
        // log_webvitals_report

        // Default to:
        // log_visit
        // log_link_visit_action
        // log_action
        // log_conversion
        // log_conversion_item

        $tables = $input->getOption('tables');
        $date = $input->getOption('date') ?? date('Y-m-d', strtotime('yesterday'));
        $siteIds = $input->getOption('siteids');


        try {
            $service = new DatabaseDumpService();
            $dumpPath = $service->generateLogDumps($compress, $dest, $tables, $date, $siteIds);

            $logger->info('Dumps generated at: ' . $dumpPath);
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
        $message = sprintf('Logs dumped to: %s', $dumpPath);
        $output->writeln($message);

        return self::SUCCESS;
    }
}
