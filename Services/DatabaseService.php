<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

use Piwik\Plugins\DataExport\Services\FileService;
use Piwik\Container\StaticContainer;
use Psr\Log\LoggerInterface;

class DatabaseService {

    /**
     * @var array
     */
    protected $dbConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger ?: StaticContainer::get(LoggerInterface::class);
        $this->dbConfig = \Piwik\Config::getInstance()->database;
    }

    /**
     * Drops the database.
     *
     * @return bool
     */
    public function dropDB() {
        $this->logger->debug('Dropping database...');
        $dbConfig = $this->dbConfig;
        $dbName = $dbConfig['dbname'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];

        // Using PDO for safer connection and execution
        try {
            $dsn = "mysql:host=$dbHost"; // Connect to the server, not the database itself
            $pdo = new \PDO($dsn, $dbUser, $dbPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $sql = "DROP DATABASE IF EXISTS `$dbName`";
            $pdo->exec($sql);

            $this->logger->debug("Database $dbName dropped successfully.");
            return true;
        } catch (\PDOException $e) {
            $this->logger->error("Database drop failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new database.
     *
     * @param string $dbName
     * @return bool
     */
    public function createDB($dbName) {
        $this->logger->debug('Creating database...');
        $dbConfig = $this->dbConfig;

        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];

        try {
            $dsn = "mysql:host=$dbHost";
            $pdo = new \PDO($dsn, $dbUser, $dbPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Manually using backticks for database name
            $sql = "CREATE DATABASE IF NOT EXISTS `$dbName`";
            $pdo->exec($sql);

            $this->logger->info("Database $dbName created successfully.");
            return true;
        } catch (\PDOException $e) {
            $this->logger->error("Database creation failed: " . $e->getMessage());
            return false;
        }
    }
}
