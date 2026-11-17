<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services\Dump;

use Piwik\Config;
use Piwik\Db;
use Piwik\Db\Adapter;

/**
 * Creates dedicated database connections for dumps and imports.
 *
 * A separate connection is used (instead of Db::get()) so that a dump can run
 * unbuffered queries and its own transaction without interfering with the
 * rest of Matomo. Connections are created through Matomo's own adapter
 * factory, so host, port, unix sockets, charset and the [database] SSL
 * options (enable_ssl, ssl_ca, ...) behave exactly like Matomo's connection.
 */
class DbConnectionFactory
{
    /**
     * Returns the connection config, using [database_reader] when one is
     * configured and $preferReader is true (dumps only read).
     */
    public static function getConfig(bool $preferReader = false): array
    {
        $master = Db::getDatabaseConfig();

        if (!$preferReader) {
            return $master;
        }

        $reader = Config::getInstance()->database_reader;
        if (empty($reader['host'])) {
            return $master;
        }

        // Same approach as Db::createReaderDatabaseObject()
        $config = Db::getDatabaseConfig($reader);
        foreach (['adapter', 'schema', 'type', 'tables_prefix', 'charset', 'collation'] as $key) {
            $config[$key] = $master[$key] ?? null;
        }
        if (empty($config['dbname'])) {
            $config['dbname'] = $master['dbname'];
        }
        return $config;
    }

    /**
     * Creates a new, dedicated connection.
     *
     * @return \Piwik\Db\AdapterInterface|\Zend_Db_Adapter_Abstract
     */
    public static function create(bool $preferReader = false)
    {
        $config = self::getConfig($preferReader);
        $config['profiler'] = false;
        return Adapter::factory($config['adapter'], $config);
    }
}
