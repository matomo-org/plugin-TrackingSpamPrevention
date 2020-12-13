<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration;

use Piwik\Config;
use Piwik\Plugins\TrackingSpamPrevention\AllowListIpRange;
use Piwik\Plugins\TrackingSpamPrevention\Configuration;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TrackingSpamPrevention
 * @group AllowListIpRangeTest
 * @group Plugins
 */
class AllowListIpRangeTest extends IntegrationTestCase
{
    /**
     * @var AllowListIpRange
     */
    private $allowList;

    public function setUp(): void
    {
        parent::setUp();

        $this->allowList = new AllowListIpRange(new Configuration());
    }

    public function test_isAllowed()
    {
        Config::getInstance()->TrackingSpamPrevention[Configuration::KEY_RANGE_ALLOW_LIST] = ['10.10.0.0/21', '15.15.0.0/21', '2001:db8::/64'];

        $this->assertTrue($this->allowList->isAllowed('10.10.0.0'));
        $this->assertTrue($this->allowList->isAllowed('10.10.0.1'));
        $this->assertTrue($this->allowList->isAllowed('10.10.0.255'));
        $this->assertTrue($this->allowList->isAllowed('15.15.0.255'));

        $this->assertFalse($this->allowList->isAllowed('11.11.0.0'));

        $this->assertTrue($this->allowList->isAllowed('2001:db8::'));
        $this->assertTrue($this->allowList->isAllowed('2001:db8:0000:0000:ffff:ffff:ffff:fffe'));

        $this->assertFalse($this->allowList->isAllowed('2001:db8:0000:0001:ffff:ffff:ffff:fffe'));
        $this->assertFalse($this->allowList->isAllowed('2002:db8:0000:0000:ffff:ffff:ffff:fffe'));
    }


}
