<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\DataExport\Services\Dump\PhpSqlDumper;

/**
 * @group DataExport
 * @group Plugins
 */
class PhpSqlDumperTypesTest extends TestCase
{
    /**
     * @dataProvider getColumnTypes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getColumnTypes')]
    public function testGetTypeCategory(string $type, string $expected)
    {
        $this->assertSame($expected, PhpSqlDumper::getTypeCategory($type));
    }

    public static function getColumnTypes(): array
    {
        return [
            ['bit(8)', 'bit'],
            ['binary(8)', 'binary'],
            ['varbinary(255)', 'binary'],
            ['blob', 'binary'],
            ['mediumblob', 'binary'],
            ['int(10) unsigned', 'numeric'],
            ['bigint(20)', 'numeric'],
            ['decimal(10,2)', 'numeric'],
            ['double', 'numeric'],
            ['varchar(255)', 'string'],
            ['text', 'string'],
            ['datetime', 'string'],
            ['json', 'string'],
        ];
    }

    public function testQuoteIdentifierEscapesBackticks()
    {
        $this->assertSame('`matomo_log_visit`', PhpSqlDumper::quoteIdentifier('matomo_log_visit'));
        $this->assertSame('`we``ird`', PhpSqlDumper::quoteIdentifier('we`ird'));
    }
}
