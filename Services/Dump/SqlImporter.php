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
 * Pure PHP SQL importer, used when the mysql client is not available.
 *
 * Reads mysqldump / PhpSqlDumper output (plain or .gz) line by line and
 * executes it statement by statement. Statements are split on `;` outside of
 * quotes and comments, so semicolons inside data are handled correctly.
 * mysqldump's executable comments (/*!40101 ... *\/) are kept and executed.
 */
class SqlImporter
{
    /** @var \Piwik\Db\AdapterInterface|\Zend_Db_Adapter_Abstract */
    private $db;

    /** @var LoggerInterface */
    private $logger;

    public function __construct($db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * @return int Number of executed statements
     */
    public function importFile(string $path): int
    {
        $handle = @gzopen($path, 'rb');
        if ($handle === false) {
            throw new \Exception("Cannot open $path for reading.");
        }

        $count = 0;
        try {
            foreach ($this->readStatements($handle) as $statement) {
                $this->execute($statement);
                $count++;
            }
        } finally {
            gzclose($handle);
        }

        $this->logger->debug("SQL import: executed $count statements from $path");
        return $count;
    }

    /**
     * Splits a stream of SQL into statements.
     *
     * @param resource $handle gzopen() handle (also reads uncompressed files)
     */
    public function readStatements($handle): \Generator
    {
        $statement = '';
        $quote = null;          // current quote character: ' " or `
        $inBlockComment = false;
        $keepBlockComment = false; // executable /*! ... */ comments are kept

        while (($line = gzgets($handle)) !== false) {
            // MariaDB 11+ mysqldump writes a client-only sandbox command as the first line
            if ($statement === '' && $quote === null && !$inBlockComment
                && strncmp($line, '/*!999999\\- enable the sandbox mode', 35) === 0
            ) {
                continue;
            }

            $length = strlen($line);
            for ($i = 0; $i < $length; $i++) {
                $char = $line[$i];

                if ($inBlockComment) {
                    if ($char === '*' && $i + 1 < $length && $line[$i + 1] === '/') {
                        $inBlockComment = false;
                        $i++;
                        if ($keepBlockComment) {
                            $statement .= '*/';
                        }
                    } elseif ($keepBlockComment) {
                        $statement .= $char;
                    }
                    continue;
                }

                if ($quote !== null) {
                    $statement .= $char;
                    if ($char === '\\' && $quote !== '`' && $i + 1 < $length) {
                        $statement .= $line[++$i];
                    } elseif ($char === $quote) {
                        if ($i + 1 < $length && $line[$i + 1] === $quote) {
                            // doubled quote is an escaped quote
                            $statement .= $line[++$i];
                        } else {
                            $quote = null;
                        }
                    }
                    continue;
                }

                if ($char === "'" || $char === '"' || $char === '`') {
                    $quote = $char;
                    $statement .= $char;
                    continue;
                }

                if ($char === '-' && $i + 1 < $length && $line[$i + 1] === '-'
                    && ($i + 2 >= $length || ctype_space($line[$i + 2]))
                ) {
                    break; // "-- " comment until end of line
                }

                if ($char === '#') {
                    break; // "#" comment until end of line
                }

                if ($char === '/' && $i + 1 < $length && $line[$i + 1] === '*') {
                    $inBlockComment = true;
                    $keepBlockComment = $i + 2 < $length && $line[$i + 2] === '!';
                    if ($keepBlockComment) {
                        $statement .= '/*';
                    }
                    $i++;
                    continue;
                }

                if ($char === ';') {
                    $trimmed = trim($statement);
                    if ($trimmed !== '') {
                        yield $trimmed;
                    }
                    $statement = '';
                    continue;
                }

                $statement .= $char;
            }

            if ($quote === null && !$inBlockComment && trim($statement) === '') {
                $statement = '';
            } elseif ($quote === null && !$inBlockComment && substr($statement, -1) !== "\n") {
                $statement .= "\n";
            }
        }

        $trimmed = trim($statement);
        if ($trimmed !== '') {
            yield $trimmed;
        }
    }

    private function execute(string $statement): void
    {
        if (preg_match('/^DELIMITER\b/i', $statement)) {
            throw new \Exception('DELIMITER statements (triggers/procedures) are not supported by the PHP importer. Use the mysql client instead.');
        }

        $connection = $this->db->getConnection();
        try {
            if ($connection instanceof \PDO) {
                $connection->exec($statement);
            } elseif ($connection instanceof \mysqli) {
                if ($connection->query($statement) === false) {
                    throw new \Exception($connection->error);
                }
            } else {
                $this->db->query($statement);
            }
        } catch (\Exception $e) {
            throw new \Exception('SQL import failed: ' . $e->getMessage() . ' in statement: ' . substr($statement, 0, 200), 0, $e);
        }
    }
}
