<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services\Dump;

use Psr\Log\LoggerInterface;

/**
 * Pure PHP database dumper, used when mysqldump is not available
 * (e.g. slim Docker images or hosts where exec() is disabled).
 *
 * The output is plain SQL in the same style as mysqldump, so it can be
 * imported with the `mysql` client or with SqlImporter.
 *
 * - Rows are streamed with unbuffered queries, so memory use stays flat
 *   regardless of table size.
 * - The dump runs in a consistent snapshot transaction (InnoDB), like
 *   mysqldump --single-transaction, so tracking is not blocked.
 * - Binary columns are written as hex literals, generated columns are skipped.
 */
class PhpSqlDumper
{
    public const HEADER_SIGNATURE = '-- DataExport PHP dump';

    /** Rows are grouped into INSERT statements of roughly this many bytes. */
    private const DEFAULT_MAX_STATEMENT_BYTES = 1048576;

    /** @var \Piwik\Db\AdapterInterface|\Zend_Db_Adapter_Abstract */
    private $db;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $maxStatementBytes;

    /** @var bool */
    private $inTransaction = false;

    public function __construct($db, LoggerInterface $logger, int $maxStatementBytes = self::DEFAULT_MAX_STATEMENT_BYTES)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->maxStatementBytes = $maxStatementBytes;
    }

    /**
     * Dumps all base tables of the current database to $path.
     */
    public function dumpDatabase(string $path): void
    {
        $handle = $this->openFile($path, 'wb');
        try {
            $this->begin();
            $this->writeHeader($handle);
            foreach ($this->getTables() as $table) {
                $this->dumpTable($handle, $table);
            }
            $this->writeFooter($handle);
        } finally {
            $this->end();
            fclose($handle);
        }
    }

    /**
     * Dumps the given tables, each with an optional WHERE condition, to $path.
     *
     * The table structure is written as CREATE TABLE IF NOT EXISTS without a
     * DROP TABLE, so the file can be imported into a database that already
     * has these tables (like mysqldump --skip-add-drop-table).
     *
     * @param array $tables table name => WHERE condition (string) or null.
     *                      Conditions must already be safe SQL.
     */
    public function dumpTables(string $path, array $tables, bool $append = false): void
    {
        $handle = $this->openFile($path, $append ? 'ab' : 'wb');
        try {
            $this->begin();
            $this->writeHeader($handle);
            foreach ($tables as $table => $where) {
                $this->dumpTable($handle, $table, $where, false);
            }
            $this->writeFooter($handle);
        } finally {
            $this->end();
            fclose($handle);
        }
    }

    /**
     * @return string[] Base tables (no views) of the current database.
     */
    public function getTables(): array
    {
        $rows = $this->db->fetchAll("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = reset($row);
        }
        sort($tables);
        return $tables;
    }

    /**
     * Writes the structure and data of one table.
     *
     * @param resource $handle
     */
    public function dumpTable($handle, string $table, ?string $where = null, bool $dropTable = true): void
    {
        $quotedTable = self::quoteIdentifier($table);
        $this->logger->debug("PHP dump: dumping table $table" . ($where ? " WHERE $where" : ''));

        $create = $this->db->fetchRow('SHOW CREATE TABLE ' . $quotedTable);
        $createSql = $create['Create Table'] ?? array_values($create)[1];
        if (!$dropTable) {
            $createSql = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $createSql);
        }

        $this->write($handle, "\n--\n-- Table structure for table $quotedTable\n--\n\n");
        if ($dropTable) {
            $this->write($handle, "DROP TABLE IF EXISTS $quotedTable;\n");
        }
        $this->write($handle, $createSql . ";\n\n");

        $columns = $this->getColumns($table);
        if (empty($columns)) {
            return;
        }

        $columnList = implode(',', array_map([self::class, 'quoteIdentifier'], array_keys($columns)));
        $types = array_values($columns);
        $insertPrefix = "INSERT INTO $quotedTable ($columnList) VALUES ";

        // BIT values are read as numbers, drivers differ in how they return raw BIT bytes
        $selectList = [];
        foreach ($columns as $column => $category) {
            $quoted = self::quoteIdentifier($column);
            $selectList[] = $category === 'bit' ? "CAST($quoted AS UNSIGNED) AS $quoted" : $quoted;
        }
        $sql = 'SELECT ' . implode(',', $selectList) . " FROM $quotedTable" . ($where ? " WHERE $where" : '');

        $this->write($handle, "--\n-- Dumping data for table $quotedTable\n--\n\n");
        $this->write($handle, "/*!40000 ALTER TABLE $quotedTable DISABLE KEYS */;\n");

        $buffer = '';
        $rowCount = 0;
        foreach ($this->streamRows($sql) as $row) {
            $values = [];
            foreach ($row as $i => $value) {
                $values[] = $this->formatValue($value, $types[$i]);
            }
            $tuple = '(' . implode(',', $values) . ')';

            if ($buffer !== '' && strlen($buffer) + strlen($tuple) > $this->maxStatementBytes) {
                $this->write($handle, $buffer . ";\n");
                $buffer = '';
            }
            $buffer .= ($buffer === '' ? $insertPrefix : ',') . $tuple;
            $rowCount++;
        }
        if ($buffer !== '') {
            $this->write($handle, $buffer . ";\n");
        }

        $this->write($handle, "/*!40000 ALTER TABLE $quotedTable ENABLE KEYS */;\n");
        $this->logger->debug("PHP dump: $rowCount rows from $table");
    }

    /**
     * @return array column name => type category ('binary', 'bit', 'numeric', 'string')
     */
    private function getColumns(string $table): array
    {
        $columns = [];
        foreach ($this->db->fetchAll('SHOW COLUMNS FROM ' . self::quoteIdentifier($table)) as $column) {
            // Generated (virtual/stored/persistent) columns can't be inserted
            if (stripos($column['Extra'] ?? '', 'GENERATED') !== false) {
                continue;
            }
            $columns[$column['Field']] = self::getTypeCategory($column['Type']);
        }
        return $columns;
    }

    public static function getTypeCategory(string $type): string
    {
        $type = strtolower($type);
        if (preg_match('/^bit\b/', $type)) {
            return 'bit';
        }
        if (preg_match('/^(binary|varbinary|tinyblob|blob|mediumblob|longblob|geometry|point|linestring|polygon|multi\w+|geometrycollection)\b/', $type)) {
            return 'binary';
        }
        if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint|decimal|numeric|float|double|real)\b/', $type)) {
            return 'numeric';
        }
        return 'string';
    }

    private function formatValue($value, string $category): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            // PHP 8.1+ may return native floats; var_export keeps full precision
            return var_export($value, true);
        }
        $value = (string) $value;
        switch ($category) {
            case 'binary':
                return $value === '' ? "''" : '0x' . bin2hex($value);
            case 'bit':
            case 'numeric':
                if (is_numeric($value)) {
                    return $value;
                }
                return $this->db->quote($value);
            default:
                return $this->db->quote($value);
        }
    }

    /**
     * Streams rows (numeric arrays) without loading the whole result in memory.
     */
    private function streamRows(string $sql): \Generator
    {
        $connection = $this->db->getConnection();

        if ($connection instanceof \PDO) {
            $bufferedAttr = self::pdoMysqlAttr('USE_BUFFERED_QUERY');
            $connection->setAttribute($bufferedAttr, false);
            try {
                $statement = $connection->query($sql);
                while (($row = $statement->fetch(\PDO::FETCH_NUM)) !== false) {
                    yield $row;
                }
                $statement->closeCursor();
            } finally {
                $connection->setAttribute($bufferedAttr, true);
            }
            return;
        }

        if ($connection instanceof \mysqli) {
            $result = $connection->query($sql, MYSQLI_USE_RESULT);
            if ($result === false) {
                throw new \Exception('Query failed: ' . $connection->error);
            }
            try {
                while (($row = $result->fetch_row()) !== null) {
                    yield $row;
                }
            } finally {
                $result->free();
            }
            return;
        }

        // Unknown adapter: fall back to a buffered query
        foreach ($this->db->fetchAll($sql, [], \PDO::FETCH_NUM) as $row) {
            yield array_values($row);
        }
    }

    /**
     * PDO MySQL attributes moved to Pdo\Mysql in PHP 8.4, the PDO::MYSQL_ATTR_*
     * constants are deprecated as of PHP 8.5.
     */
    private static function pdoMysqlAttr(string $name): int
    {
        if (defined('Pdo\\Mysql::ATTR_' . $name)) {
            return constant('Pdo\\Mysql::ATTR_' . $name);
        }
        return constant('PDO::MYSQL_ATTR_' . $name);
    }

    private function begin(): void
    {
        // Dump TIMESTAMP values in UTC, like mysqldump does
        $this->db->query("SET SESSION time_zone = '+00:00'");
        try {
            $this->db->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->db->query('START TRANSACTION /*!40100 WITH CONSISTENT SNAPSHOT */');
            $this->inTransaction = true;
        } catch (\Exception $e) {
            $this->logger->notice('PHP dump: could not start a consistent snapshot, continuing without: ' . $e->getMessage());
        }
    }

    private function end(): void
    {
        if ($this->inTransaction) {
            try {
                $this->db->query('COMMIT');
            } catch (\Exception $e) {
                // ignore, the connection is discarded anyway
            }
            $this->inTransaction = false;
        }
    }

    private function writeHeader($handle): void
    {
        $version = '';
        try {
            $version = (string) $this->db->fetchOne('SELECT VERSION()');
        } catch (\Exception $e) {
        }
        $dbName = '';
        try {
            $dbName = (string) $this->db->fetchOne('SELECT DATABASE()');
        } catch (\Exception $e) {
        }

        $this->write($handle, self::HEADER_SIGNATURE . " (MySQL compatible)\n"
            . "--\n"
            . "-- Host: (Matomo database)    Database: $dbName\n"
            . "-- Server version: $version\n"
            . "-- Generated: " . gmdate('Y-m-d H:i:s') . " UTC\n"
            . "-- ------------------------------------------------------\n\n"
            . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n"
            . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n"
            . "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n"
            . "/*!40101 SET NAMES utf8mb4 */;\n"
            . "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n"
            . "/*!40103 SET TIME_ZONE='+00:00' */;\n"
            . "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n"
            . "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n"
            . "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n"
            . "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n");
    }

    private function writeFooter($handle): void
    {
        $this->write($handle, "\n/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n"
            . "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n"
            . "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n"
            . "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n"
            . "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n"
            . "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n"
            . "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n"
            . "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n\n"
            . "-- Dump completed on " . gmdate('Y-m-d H:i:s') . " UTC\n");
    }

    /**
     * @return resource
     */
    private function openFile(string $path, string $mode)
    {
        $handle = @fopen($path, $mode);
        if ($handle === false) {
            $error = error_get_last();
            throw new \Exception("Cannot open $path for writing: " . ($error['message'] ?? 'unknown error'));
        }
        return $handle;
    }

    private function write($handle, string $data): void
    {
        if (fwrite($handle, $data) === false) {
            throw new \Exception('Failed to write to the dump file (disk full?).');
        }
    }

    public static function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
