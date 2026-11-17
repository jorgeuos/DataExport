<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\tests\Integration;

use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\DataExport\Services\Dump\DbConnectionFactory;
use Piwik\Plugins\DataExport\Services\Dump\PhpSqlDumper;
use Piwik\Plugins\DataExport\Services\Dump\SqlImporter;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Psr\Log\NullLogger;

/**
 * Dumps a table full of awkward values with the PHP dumper, restores it with
 * the PHP importer and checks that every value survives unchanged. This is
 * the path used on servers without mysqldump or the mysql client.
 *
 * @group DataExport
 * @group Plugins
 */
class PhpDumpRoundTripTest extends IntegrationTestCase
{
    /** @var string */
    private $table;

    /** @var string[] */
    private $files = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->table = Common::prefixTable('dataexport_edge`case');
        $quoted = PhpSqlDumper::quoteIdentifier($this->table);

        Db::exec("DROP TABLE IF EXISTS $quoted");
        Db::exec("CREATE TABLE $quoted (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(255) NULL,
            body TEXT NULL,
            raw VARBINARY(64) NULL,
            flags BIT(8) NULL,
            amount DECIMAL(12,4) NULL,
            ratio DOUBLE NULL,
            created DATETIME NULL,
            label_length INT AS (CHAR_LENGTH(label)) VIRTUAL
        ) DEFAULT CHARSET=utf8mb4");

        $rows = [
            ["it's; a \"test\"", "line1\nline2;\r\n-- not a comment", "\x00\x01\x27\x5c\xff", 170, '1234.5678', 0.1 + 0.2, '2024-01-02 03:04:05'],
            ['emoji 🎉 and ümlauts', '/* not a comment */ # nor this', '', 0, '-0.0001', 1e-12, null],
            [null, null, null, null, null, null, null],
            ['back\\slash `tick`', str_repeat('x', 5000), "\x00", 255, '0.0000', -12345.678, '1970-01-01 00:00:01'],
        ];
        foreach ($rows as $row) {
            Db::query(
                "INSERT INTO $quoted (label, body, raw, flags, amount, ratio, created) VALUES (?, ?, ?, ?, ?, ?, ?)",
                $row
            );
        }
    }

    public function tearDown(): void
    {
        Db::exec('DROP TABLE IF EXISTS ' . PhpSqlDumper::quoteIdentifier($this->table));
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function testPlainDumpRestoresIdenticalData()
    {
        $this->assertRoundTrip('.sql');
    }

    public function testGzipDumpRestoresIdenticalData()
    {
        $this->assertRoundTrip('.sql.gz');
    }

    public function testWhereConditionLimitsDumpedRows()
    {
        // Used by db:dump-logs to dump a single day or site
        $path = $this->dump('.sql', 'id IN (1, 3)');
        Db::exec('DROP TABLE ' . PhpSqlDumper::quoteIdentifier($this->table));

        (new SqlImporter(Db::get(), new NullLogger()))->importFile($path);

        $ids = array_column(Db::fetchAll('SELECT id FROM ' . PhpSqlDumper::quoteIdentifier($this->table) . ' ORDER BY id'), 'id');
        $this->assertEquals([1, 3], $ids);
    }

    private function assertRoundTrip(string $suffix)
    {
        $before = $this->fetchRows();
        $path = $this->dump($suffix);

        if ($suffix === '.sql.gz') {
            $this->assertSame("\x1f\x8b", substr(file_get_contents($path), 0, 2), 'Expected a gzip file');
        }

        Db::exec('DROP TABLE ' . PhpSqlDumper::quoteIdentifier($this->table));

        $importer = new SqlImporter(Db::get(), new NullLogger());
        $this->assertGreaterThan(0, $importer->importFile($path));

        $this->assertSame($before, $this->fetchRows());
    }

    private function dump(string $suffix, ?string $where = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dataexport') . $suffix;
        $this->files[] = $path;

        $dumper = new PhpSqlDumper(DbConnectionFactory::create(), new NullLogger());
        $dumper->dumpTables($path, [$this->table => $where]);

        $this->assertFileExists($path);
        return $path;
    }

    private function fetchRows(): array
    {
        return Db::fetchAll(
            'SELECT id, label, body, HEX(raw) AS raw, CAST(flags AS UNSIGNED) AS flags, amount,
                    CAST(ratio AS CHAR) AS ratio, created, label_length
             FROM ' . PhpSqlDumper::quoteIdentifier($this->table) . ' ORDER BY id'
        );
    }
}
