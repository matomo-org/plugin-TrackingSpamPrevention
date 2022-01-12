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

    public function test_detectLocation()
    {
        $this->assertEquals([  'country_code' => 'xx',
    'continent_code' => 'unk',
    'continent_name' => 'General_Unknown',
    'country_name' => 'General_Unknown'], $this->blockedGeoIp->detectLocation('127.0.0.1', 'en'));
    }

    public function test_isExcludedCountry_noCountriesGiven()
    {
        $this->assertFalse($this->blockedGeoIp->isExcludedCountry('127.0.0.1', 'en', [], []));
    }

    public function test_isExcludedCountry_excludedCountriesGiven()
    {
        // this IP matches country "xx"
        $this->assertTrue($this->blockedGeoIp->isExcludedCountry('127.0.0.1', 'en', ['fr', 'xx'], []));
        $this->assertFalse($this->blockedGeoIp->isExcludedCountry('127.0.0.1', 'en', ['de', 'nz'], []));
    }

    public function test_isExcludedCountry_includedCountriesGiven()
    {
        // this IP matches country "xx"
        $this->assertFalse($this->blockedGeoIp->isExcludedCountry('127.0.0.1', 'en', [], ['fr', 'xx']));
        $this->assertTrue($this->blockedGeoIp->isExcludedCountry('127.0.0.1', 'en', [], ['de', 'nz']));
    }

    public function test_isExcluded()
    {
        $this->assertFalse($this->blockedGeoIp->isExcludedProvider('127.0.0.1', 'en'));
    }

    public function test_isExcluded_When_UserCountryPluginIsDisabled()
    {
        \Piwik\Plugin\Manager::getInstance()->deactivatePlugin('UserCountry');
        $this->assertFalse($this->blockedGeoIp->isExcludedProvider('127.0.0.1', 'en'));
    }

}
