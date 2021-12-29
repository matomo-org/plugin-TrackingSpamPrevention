<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration;

use Piwik\Config;
use Piwik\Plugins\TrackingSpamPrevention\Configuration;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TrackingSpamPrevention
 * @group ConfigurationTest
 * @group Configuration
 * @group Plugins
 */
class ConfigurationTest extends IntegrationTestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function setUp(): void
    {
        parent::setUp();

        $this->configuration = new Configuration();
        $this->configuration->install();
    }

    public function test_shouldInstallConfig()
    {
        $this->configuration->install();

        $configs = Config::getInstance()->TrackingSpamPrevention;
        $this->assertEquals(array(
            'block_cloud_sync_throw_exception_on_error' => 0,
            'iprange_allowlist' => [''],
            'block_geoip_organisations' => ['alicloud', 'alibaba cloud', 'digitalocean', 'digital ocean'],
        ), $configs);
    }

    public function test_shouldThrowExceptionOnIpRangeSync_default()
    {
        $this->assertFalse($this->configuration->shouldThrowExceptionOnIpRangeSync());
    }

    public function test_shouldThrowExceptionOnIpRangeSync_enabled()
    {
        Config::getInstance()->TrackingSpamPrevention[Configuration::KEY_RANGE_THROW_EXCEPTION] = 1;
        $this->assertTrue($this->configuration->shouldThrowExceptionOnIpRangeSync());
    }

    public function test_getIpRangesAlwaysAllowed_byDefault()
    {
        $this->assertSame([], $this->configuration->getIpRangesAlwaysAllowed());
    }

    public function test_getIpRangesAlwaysAllowed_custom()
    {
        Config::getInstance()->TrackingSpamPrevention = array(
            Configuration::KEY_RANGE_ALLOW_LIST => ['10.12.13.14/32', 'f::f/52', '', '11.12.13.14/21', '12.14.15.16', 'f::f']
        );
        $this->assertSame(['10.12.13.14/32', 'f::f/52', '11.12.13.14/21', '12.14.15.16/32', 'f::f/128'], $this->configuration->getIpRangesAlwaysAllowed());
    }

    public function test_getIpRangesAlwaysAllowed_invalid()
    {
        Config::getInstance()->TrackingSpamPrevention = array(
            Configuration::KEY_RANGE_ALLOW_LIST => 'foobar'
        );
        $this->assertSame([], $this->configuration->getIpRangesAlwaysAllowed());
    }


}
