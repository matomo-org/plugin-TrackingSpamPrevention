<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration;

use Piwik\Plugins\TrackingSpamPrevention\AllowListIpRange;
use Piwik\Plugins\TrackingSpamPrevention\BlockedGeoIp;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TrackingSpamPrevention
 * @group BlockedGeoIpTest
 * @group Plugins
 */
class BlockedGeoIpTest extends IntegrationTestCase
{
    /**
     * @var AllowListIpRange
     */
    private $blockedGeoIp;

    public function setUp(): void
    {
        parent::setUp();

        $this->blockedGeoIp = new BlockedGeoIp(['mytest']);
    }

    public function test_isExcluded()
    {
        $this->assertFalse($this->blockedGeoIp->isExcluded('127.0.0.1', 'en'));
    }


}
