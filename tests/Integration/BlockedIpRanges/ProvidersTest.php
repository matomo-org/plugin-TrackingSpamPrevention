<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration\BlockedIpRanges;

use Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TrackingSpamPrevention
 * @group BlockedIpRangesTest
 * @group Plugins
 */
class ProvidersTest extends IntegrationTestCase
{
    /**
     * @dataProvider getIpRangeProviderDataProvider
     */
    public function test_getRanges(BlockedIpRanges\IpRangeProviderInterface $provider)
    {
        $ranges = $provider->getRanges();
        $this->assertNotEmpty($ranges);
        $this->assertTrue(is_array($ranges));
        $this->assertGreaterThan(5, count($ranges));
    }

    public function getIpRangeProviderDataProvider()
    {
        return [
            [new BlockedIpRanges\Aws()],
            [new BlockedIpRanges\Azure()],
            [new BlockedIpRanges\DigitalOcean()],
            [new BlockedIpRanges\Gcloud()],
            [new BlockedIpRanges\Oracle()],
        ];
    }

}
