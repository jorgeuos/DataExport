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
use Piwik\Plugins\DataExport\Services\DatabaseDumpService;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Psr\Log\NullLogger;

/**
 * Regression tests for the visits and actions CSV export.
 *
 * @group DataExport
 * @group Plugins
 */
class VisitsAndActionsExportTest extends IntegrationTestCase
{
    private const DATE = '2024-03-05';

    /** @var int */
    private $idSite;

    public function setUp(): void
    {
        parent::setUp();

        $this->idSite = Fixture::createWebsite(self::DATE . ' 00:00:00');

        // Two visits with two pageviews each and no conversions. Before the
        // fix, idvisit and idsite of such visits were overwritten with NULL
        // by the LEFT JOINed conversion tables.
        $idUrlAction = $this->insertAction('example.com/page', 1);
        $idNameAction = $this->insertAction('Page', 4);
        foreach (['10:00:00', '14:00:00'] as $i => $time) {
            $start = self::DATE . ' ' . $time;
            $end = self::DATE . ' ' . substr($time, 0, 2) . ':05:00';
            Db::query(
                'INSERT INTO ' . Common::prefixTable('log_visit') . '
                    (idsite, idvisitor, config_id, location_ip, visit_first_action_time,
                     visit_last_action_time, visit_total_time, visit_total_actions)
                 VALUES (?, ?, ?, ?, ?, ?, 300, 2)',
                [$this->idSite, hex2bin('a1b2c3d4e5f6070' . $i), hex2bin('0102030405060708'),
                 inet_pton('192.0.2.' . (10 + $i)), $start, $end]
            );
            $idVisit = Db::get()->lastInsertId();
            foreach ([$start, $end] as $serverTime) {
                Db::query(
                    'INSERT INTO ' . Common::prefixTable('log_link_visit_action') . '
                        (idsite, idvisitor, idvisit, server_time, idaction_url, idaction_name)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$this->idSite, hex2bin('a1b2c3d4e5f6070' . $i), $idVisit, $serverTime, $idUrlAction, $idNameAction]
                );
            }
        }
    }

    private function insertAction(string $name, int $type): int
    {
        Db::query(
            'INSERT INTO ' . Common::prefixTable('log_action') . ' (name, hash, type) VALUES (?, CRC32(?), ?)',
            [$name, $name, $type]
        );
        return (int) Db::get()->lastInsertId();
    }

    public function testExportUsesConfiguredTablePrefix()
    {
        // https://github.com/jorgeuos/DataExport/issues/7: the query used a
        // hard-coded "matomo_" prefix.
        $sql = DatabaseDumpService::getSelectAllVisitsAndActionsFromSql();
        $prefix = \Piwik\Config::getInstance()->database['tables_prefix'];
        $this->assertStringContainsString('`' . $prefix . 'log_visit` AS mlv', $sql);
        $this->assertStringContainsString('`' . $prefix . 'log_conversion` AS mlc', $sql);
    }

    public function testExportReturnsEveryActionWithItsVisit()
    {
        $service = new DatabaseDumpService(new NullLogger());
        $rows = $service->selectAllVisitsAndActionsData(self::DATE, $this->idSite);

        $this->assertIsArray($rows);
        $this->assertCount(4, $rows, 'Expected one row per pageview');

        foreach ($rows as $row) {
            $this->assertNotEmpty($row['idvisit']);
            $this->assertEquals($this->idSite, $row['idsite']);
            $this->assertSame(1, preg_match('/^a1b2c3d4e5f6070[01]$/', $row['idvisitor']), 'idvisitor should be hex');
            $this->assertSame(1, preg_match('/^192\.0\.2\.1[01]$/', $row['location_ip']), 'location_ip should be readable');
            $this->assertSame('example.com/page', $row['name']);
        }

        $this->assertCount(2, array_unique(array_column($rows, 'idvisit')));
    }

    public function testExportFiltersBySite()
    {
        $otherSite = Fixture::createWebsite(self::DATE . ' 00:00:00');
        $service = new DatabaseDumpService(new NullLogger());

        $this->assertSame([], $service->selectAllVisitsAndActionsData(self::DATE, $otherSite));
    }
}
