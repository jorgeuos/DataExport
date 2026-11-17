<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\DataExport\Services\Dump\SqlImporter;
use Psr\Log\NullLogger;

/**
 * The PHP importer has to split dumps from mysqldump, mariadb-dump and the
 * PHP dumper into statements without a SQL parser, so these cases cover the
 * quoting and comment rules that a naive split on ";" gets wrong.
 *
 * @group DataExport
 * @group Plugins
 */
class SqlImporterTest extends TestCase
{
    /** @var string[] */
    private $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function testSplitsSimpleStatements()
    {
        $this->assertSame(
            ['CREATE TABLE t (a INT)', 'INSERT INTO t VALUES (1)'],
            $this->split("CREATE TABLE t (a INT);\nINSERT INTO t VALUES (1);\n")
        );
    }

    public function testKeepsSemicolonsAndNewlinesInsideQuotes()
    {
        $sql = "INSERT INTO t VALUES ('a;b', \"c;d\", 'line1\nline2;');\nSELECT 1;";
        $this->assertSame(
            ["INSERT INTO t VALUES ('a;b', \"c;d\", 'line1\nline2;')", 'SELECT 1'],
            $this->split($sql)
        );
    }

    public function testHandlesBackslashAndDoubledQuoteEscapes()
    {
        $sql = "INSERT INTO t VALUES ('it\\'s;', 'it''s;', 'back\\\\');SELECT 2;";
        $this->assertSame(
            ["INSERT INTO t VALUES ('it\\'s;', 'it''s;', 'back\\\\')", 'SELECT 2'],
            $this->split($sql)
        );
    }

    public function testBacktickIdentifiersDoNotTreatBackslashAsEscape()
    {
        $sql = "CREATE TABLE `we;ird\\` (a INT);SELECT 3;";
        $this->assertSame(
            ["CREATE TABLE `we;ird\\` (a INT)", 'SELECT 3'],
            $this->split($sql)
        );
    }

    public function testDropsCommentsButKeepsExecutableComments()
    {
        $sql = "-- MySQL dump header; with a semicolon\n"
            . "# hash comment;\n"
            . "/* block comment; */\n"
            . "/*!40101 SET NAMES utf8mb4 */;\n"
            . "SELECT '-- not a comment' -- trailing comment;\n"
            . ";\n";
        $this->assertSame(
            ['/*!40101 SET NAMES utf8mb4 */', "SELECT '-- not a comment'"],
            $this->split($sql)
        );
    }

    public function testSkipsMariaDbSandboxLine()
    {
        $sql = "/*!999999\\- enable the sandbox mode */ \nCREATE TABLE t (a INT);\n";
        $this->assertSame(['CREATE TABLE t (a INT)'], $this->split($sql));
    }

    public function testReturnsLastStatementWithoutSemicolon()
    {
        $this->assertSame(['SELECT 1', 'SELECT 2'], $this->split("SELECT 1;\nSELECT 2\n"));
    }

    public function testReadsGzipFiles()
    {
        $path = $this->tempFile('.sql.gz');
        file_put_contents($path, gzencode("SELECT 'a;b';\nSELECT 2;\n"));
        $this->assertSame(["SELECT 'a;b'", 'SELECT 2'], $this->splitFile($path));
    }

    private function split(string $sql): array
    {
        $path = $this->tempFile('.sql');
        file_put_contents($path, $sql);
        return $this->splitFile($path);
    }

    private function splitFile(string $path): array
    {
        $importer = new SqlImporter(null, new NullLogger());
        $handle = gzopen($path, 'rb');
        try {
            return iterator_to_array($importer->readStatements($handle), false);
        } finally {
            gzclose($handle);
        }
    }

    private function tempFile(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dataexport') . $suffix;
        $this->files[] = $path;
        return $path;
    }
}
