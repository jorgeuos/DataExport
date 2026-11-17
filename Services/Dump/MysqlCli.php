<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services\Dump;

/**
 * Helpers for the mysqldump / mysql command line clients.
 *
 * MariaDB 11 images often only ship mariadb-dump / mariadb, so both names
 * are looked up. Nothing here works when exec() is disabled.
 */
class MysqlCli
{
    // mariadb-dump/mariadb first: on MariaDB 11 the mysqldump/mysql names are
    // deprecated aliases that print a warning on every run.
    public const DUMP_BINARIES = ['mariadb-dump', 'mysqldump'];
    public const CLIENT_BINARIES = ['mariadb', 'mysql'];

    /** @var array<string, array|null> */
    private static $cache = [];

    public static function canExec(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }
        $disabled = array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))));
        return !in_array('exec', $disabled, true);
    }

    /**
     * Finds the first working binary of the given candidates.
     *
     * @param string[] $candidates
     * @return array|null ['binary' => 'mysqldump', 'version' => 'mysqldump  Ver 10.19 ...']
     */
    public static function find(array $candidates): ?array
    {
        $key = implode(',', $candidates);
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $found = null;
        if (self::canExec()) {
            foreach ($candidates as $binary) {
                $output = [];
                $returnVar = 1;
                @exec(escapeshellarg($binary) . ' --version 2>&1', $output, $returnVar);
                // Drop warnings such as "Deprecated program name" from the version string
                $output = array_values(array_filter($output, function ($line) {
                    return trim($line) !== '' && stripos($line, 'deprecated') === false;
                }));
                if ($returnVar === 0 && !empty($output)) {
                    $found = ['binary' => $binary, 'version' => trim(implode(' ', $output))];
                    break;
                }
            }
        }

        self::$cache[$key] = $found;
        return $found;
    }

    public static function findDump(): ?array
    {
        return self::find(self::DUMP_BINARIES);
    }

    public static function findClient(): ?array
    {
        return self::find(self::CLIENT_BINARIES);
    }

    /**
     * Connection arguments (user, host, port or socket) for the CLI clients.
     * The password is passed via the MYSQL_PWD environment variable, never on
     * the command line where other users could see it.
     */
    public static function connectionArgs(array $dbConfig): string
    {
        $args = '-u ' . escapeshellarg((string) $dbConfig['username']);

        $port = $dbConfig['port'] ?? null;
        $socket = $dbConfig['unix_socket'] ?? null;
        if (is_string($port) && $port !== '' && $port[0] === '/') {
            $socket = $port;
            $port = null;
        }

        if (!empty($socket)) {
            $args .= ' --socket=' . escapeshellarg($socket);
        } else {
            if (!empty($dbConfig['host'])) {
                $args .= ' -h ' . escapeshellarg((string) $dbConfig['host']);
            }
            $args .= ' -P ' . (int) ($port ?: 3306);
        }

        return $args;
    }

    /**
     * Option that disables SSL for the given client version string.
     */
    public static function disableSslOption(string $versionString): string
    {
        if (stripos($versionString, 'mariadb') !== false) {
            return '--skip-ssl';
        }
        if (preg_match('/(\d+)\.(\d+)\.(\d+)/', $versionString, $matches)) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2];
            // "Ver 8.0.33" or "Distrib 5.7.x"; mysqldump 10.x reports the Distrib version later in the string
            if (preg_match('/Distrib (\d+)\.(\d+)/', $versionString, $distrib)) {
                $major = (int) $distrib[1];
                $minor = (int) $distrib[2];
            }
            if ($major >= 8 || ($major == 5 && $minor >= 7)) {
                return '--ssl-mode=DISABLED';
            }
        }
        return '--ssl=0';
    }

    /**
     * Runs a command with MYSQL_PWD set.
     *
     * @return array [int $returnVar, string[] $output]
     */
    public static function run(string $command, string $password): array
    {
        $output = [];
        $returnVar = 1;
        putenv('MYSQL_PWD=' . $password);
        try {
            exec($command, $output, $returnVar);
        } finally {
            putenv('MYSQL_PWD');
        }
        return [$returnVar, $output];
    }
}
