<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges;
use Piwik\Plugins\TrackingSpamPrevention\Configuration;
use Piwik\Plugins\TrackingSpamPrevention\SystemSettings;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tracker\Cache;
use Piwik\Tracker\Request;
use Piwik\Tracker\VisitExcluded;

/**
 * @group TrackingSpamPrevention
 * @group Plugins
 */
class TrackingSpamPreventionTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Fixture::createWebsite('2020-12-12 01:02:03');
        Fixture::createSuperUser();

        $this->setBlockClouds(true);
        StaticContainer::get(BlockedIpRanges::class)->updateBlockedIpRanges();
    }

    private function makeExcluded($ip)
    {
        $req = new Request(['idsite' => 1, 'cip' => $ip, 'token_auth' => Fixture::getTokenAuth(), 'rec' => 1]);
        return new VisitExcluded($req);
    }

    public function test_trackerCache()
    {
        $cache = Cache::getCacheGeneral();
        $this->assertEquals([
            '10.' => ['10.10.0.0/21'],
            '200.' => ['200.200.0.0/21']
        ], $cache[BlockedIpRanges::OPTION_KEY]);
    }

    public function test_isExcludedVisit_whenNothingBlocked()
    {
        StaticContainer::get(BlockedIpRanges::class)->unsetAllIpRanges();
        $excluded = $this->makeExcluded('10.10.0.3');
        $this->assertFalse($excluded->isExcluded());
    }

    public function test_isExcludedVisit_whenIpBlocked()
    {
        $excluded = $this->makeExcluded('10.10.0.3');
        $this->assertTrue($excluded->isExcluded());
        $excluded = $this->makeExcluded('200.200.0.1');
        $this->assertTrue($excluded->isExcluded());
    }

    public function test_isExcludedVisit_whenWhiteListUsed()
    {
        Config::getInstance()->TrackingSpamPrevention[Configuration::KEY_RANGE_ALLOW_LIST] = [
            '10.10.0.4/32', '10.10.0.3/32',
        ];
        $excluded = $this->makeExcluded('10.10.0.2');
        $this->assertTrue($excluded->isExcluded());

        $excluded = $this->makeExcluded('10.10.0.3');
        $this->assertFalse($excluded->isExcluded());

        $excluded = $this->makeExcluded('10.10.0.4');
        $this->assertFalse($excluded->isExcluded());

        $excluded = $this->makeExcluded('10.10.0.5');
        $this->assertTrue($excluded->isExcluded());
    }

    public function test_isExcludedVisit_whenIpNotBlocked()
    {
        $excluded = $this->makeExcluded('20.20.20.20');
        $this->assertFalse($excluded->isExcluded());
    }

    private function setBlockClouds($val)
    {
        StaticContainer::get(SystemSettings::class)->block_clouds->setValue($val);
        Cache::clearCacheGeneral();
    }
}
