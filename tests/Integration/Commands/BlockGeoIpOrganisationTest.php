<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration\Commands;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\TrackingSpamPrevention\SystemSettings;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group TrackingSpamPrevention
 * @group BlockGeoIpOrganisationTest
 * @group Plugins
 */
class BlockGeoIpOrganisationTest extends ConsoleCommandTestCase
{
    public function test_addsOrganisationToSetting()
    {
        // the fixture is shared across the test methods of this class, and the local config file may still
        // contain a legacy `block_geoip_organisations` key, so assert relative to the observed state
        $organisationsBefore = $this->getBlockedOrganisations();
        $configBefore = Config::getInstance()->TrackingSpamPrevention;

        $exitCode = $this->applicationTester->run([
            'command' => 'trackingspamprevention:block-geo-ip-organisation',
            '--organisation-name' => ' My Spam Org ',
        ]);

        $this->assertSame(0, $exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expected = array_values(array_unique(array_merge($organisationsBefore, ['my spam org'])));
        $this->assertSame($expected, $this->getBlockedOrganisations());

        // the command no longer writes to the config file
        $this->assertEquals($configBefore, Config::getInstance()->TrackingSpamPrevention);
    }

    public function test_doesNotAddDuplicates()
    {
        // "contabo" is on the default list, so it is already present whatever state earlier tests left
        $organisationsBefore = $this->getBlockedOrganisations();

        $exitCode = $this->applicationTester->run([
            'command' => 'trackingspamprevention:block-geo-ip-organisation',
            '--organisation-name' => 'Contabo',
        ]);

        $this->assertSame(0, $exitCode, $this->getCommandDisplayOutputErrorMessage());

        $this->assertSame($organisationsBefore, $this->getBlockedOrganisations());
    }

    private function getBlockedOrganisations(): array
    {
        return StaticContainer::get(SystemSettings::class)->getBlockedOrganisations();
    }
}
