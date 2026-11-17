<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

use Piwik\Plugins\DataExport\Services\FileService;
use Piwik\Plugins\DataExport\Services\Dump\DbConnectionFactory;
use Piwik\Plugins\DataExport\Services\Dump\MysqlCli;
use Piwik\Plugins\DataExport\Services\Dump\SqlImporter;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;

class DatabaseImportService
{

    /**
     * @var array
     */
    protected $dbConfig;

    /**
     * @var string
     */
    protected $backupDir;

    /**
     * @var string
     */
    protected $uploadDir;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        // We only need conf to the write database
        $this->dbConfig = \Piwik\Config::getInstance()->database;
        $fileService = new FileService();
        $this->backupDir = $fileService->getBackupDir();
        $this->uploadDir = PIWIK_USER_PATH . '/tmp/de_uploads/';
        $this->logger = StaticContainer::get(LoggerInterface::class);
    }

    /**
     * Import a database dump (.sql or .sql.gz).
     *
     * @param string $dumpPath
     * @param string|null $method auto, mysql or php. auto uses the mysql
     *                            client when installed, otherwise PHP.
     */
    public function importDump($dumpPath, $method = 'auto')
    {
        if (!file_exists($dumpPath) || !is_readable($dumpPath)) {
            throw new \Exception("The file does not exist or is not readable.");
        }

        $method = $method ?: 'auto';
        $client = MysqlCli::findClient();
        $isGzip = substr(strtolower($dumpPath), -3) === '.gz';

        if ($method === 'mysql' && !$client) {
            throw new \Exception('The mysql (or mariadb) client was not found or exec() is disabled. Use the php import method instead.');
        }

        if ($method === 'php' || !$client || $isGzip) {
            $this->logger->info('Importing with the PHP importer: ' . $dumpPath);
            $db = DbConnectionFactory::create(false);
            try {
                $importer = new SqlImporter($db, $this->logger);
                $importer->importFile($dumpPath);
            } finally {
                $db->closeConnection();
            }
            return true;
        }

        $this->logger->info('Importing with ' . $client['binary'] . ': ' . $dumpPath);
        $dbConfig = DbConnectionFactory::getConfig(false);
        $command = sprintf(
            '%s %s %s < %s 2>&1',
            escapeshellarg($client['binary']),
            MysqlCli::connectionArgs($dbConfig),
            escapeshellarg($dbConfig['dbname']),
            escapeshellarg($dumpPath)
        );

        [$returnVar, $output] = MysqlCli::run($command, (string) $dbConfig['password']);

        if ($returnVar !== 0) {
            throw new \Exception("Failed to import database dump: " . implode("\n", $output));
        }
        return true;
    }


    public function importLogDumps($source)
    {
        $this->logger->debug('Importing database dump from: ' . $source);

        $dbConfig = StaticContainer::get('config')->database;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];

        $importCommands = [];
        $importCommands[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $importCommands[] = 'SET UNIQUE_CHECKS = 0;';

        // Import the log_visit table first and create a temporary mapping table
        $importCommands[] = sprintf(
            'mysql -u %s -h%s %s < %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            escapeshellarg($source)
        );

        $importCommands[] = "CREATE TEMPORARY TABLE idvisit_mapping (old_idvisit BIGINT, new_idvisit BIGINT);";
        $importCommands[] = "INSERT INTO idvisit_mapping (old_idvisit, new_idvisit) SELECT idvisit, LAST_INSERT_ID() FROM log_visit;";

        // Import remaining tables and update foreign key references
        $logTables = ['log_link_visit_action', 'log_conversion', 'log_conversion_item'];
        foreach ($logTables as $table) {
            $importCommands[] = sprintf(
                'mysql -u %s -h%s %s < %s',
                escapeshellarg($dbUser),
                escapeshellarg($dbHost),
                escapeshellarg($dbName),
                escapeshellarg($source)
            );
            $importCommands[] = sprintf(
                "UPDATE %s t JOIN idvisit_mapping m ON t.idvisit = m.old_idvisit SET t.idvisit = m.new_idvisit;",
                $table
            );
        }

        $importCommands[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $importCommands[] = 'SET UNIQUE_CHECKS = 1;';

        putenv('MYSQL_PWD=' . $dbPassword);
        foreach ($importCommands as $command) {
            $this->logger->debug('Executing import command: ' . $command);
            exec($command, $output, $returnVar);
            if ($returnVar !== 0) {
                putenv('MYSQL_PWD');
                throw new \Exception("Failed to import data for command: $command.");
            }
        }
        putenv('MYSQL_PWD');
    }

}
