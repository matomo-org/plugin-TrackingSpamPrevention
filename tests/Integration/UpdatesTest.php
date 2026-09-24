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
use Piwik\Plugins\TrackingSpamPrevention\Updates_5_0_11;
use Piwik\Plugins\TrackingSpamPrevention\Updates_5_1_0;
use Piwik\Plugins\TrackingSpamPrevention\Updates_5_2_0;
use Piwik\Settings\Storage\Factory;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;

require_once PIWIK_INCLUDE_PATH . '/plugins/TrackingSpamPrevention/Updates/5.0.11.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/TrackingSpamPrevention/Updates/5.1.0.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/TrackingSpamPrevention/Updates/5.2.0.php';

/**
 * @group TrackingSpamPrevention
 * @group UpdatesTest
 * @group Plugins
 */
class UpdatesTest extends IntegrationTestCase
{
    public function test_update_migratesConfigValuesToSystemSetting()
    {
        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_RANGE_ALLOW_LIST => [' 10.10.0.0/21 ', '', 'foobar', '12.14.15.16', '10.10.0.0/21', 'f::f'],
        ];

        $this->runUpdate();

        $this->assertSame(['10.10.0.0/21', '12.14.15.16', 'f::f'], $this->makeSettings()->getAllowedIpRanges());
        $this->assertArrayNotHasKey(Configuration::KEY_RANGE_ALLOW_LIST, Config::getInstance()->TrackingSpamPrevention);
    }

    public function test_update_installDefaultOnly_doesNotWriteSettingButRemovesKey()
    {
        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_RANGE_ALLOW_LIST => Configuration::DEFAULT_RANGE_ALLOW_LIST,
        ];

        $this->runUpdate();

        $this->assertSame([], $this->makeSettings()->getAllowedIpRanges());
        $this->assertArrayNotHasKey(Configuration::KEY_RANGE_ALLOW_LIST, Config::getInstance()->TrackingSpamPrevention);
    }

    public function test_update_configKeyAbsent_isNoOp()
    {
        Config::getInstance()->TrackingSpamPrevention = [];

        $this->runUpdate();

        $this->assertSame([], $this->makeSettings()->getAllowedIpRanges());
    }

    public function test_update_doesNotOverwriteExistingSettingValue()
    {
        $settings = $this->makeSettings();
        $settings->ipAllowList->setValue(['20.20.0.0/21']);
        $settings->save();

        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_RANGE_ALLOW_LIST => ['10.10.0.0/21'],
        ];

        $this->runUpdate();

        $this->assertSame(['20.20.0.0/21'], $this->makeSettings()->getAllowedIpRanges());
        $this->assertArrayNotHasKey(Configuration::KEY_RANGE_ALLOW_LIST, Config::getInstance()->TrackingSpamPrevention);
    }

    public function test_update5011_storesBlockHeadlessDisabledAndKeepsOtherSettings()
    {
        $settings = $this->makeSettings();
        $settings->ipAllowList->setValue(['20.20.0.0/21']);
        $settings->save();
        Config::getInstance()->TrackingSpamPrevention = [];

        $this->runUpdate5011();

        $stored = $this->loadStoredSettings();
        $this->assertSame('0', $stored['block_headless'] ?? null);
        $this->assertSame(['20.20.0.0/21'], $stored['ip_allow_list']);
    }

    public function test_update5011_thenUpdate510_keepsBlockHeadlessDisabled()
    {
        // loads the container's copy before the updates run, as an earlier read in the request would
        $this->makeSettings()->blockHeadless->getValue();
        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_RANGE_ALLOW_LIST => ['10.10.0.0/21'],
        ];

        $this->runUpdate5011();
        $this->runUpdate();

        $stored = $this->loadStoredSettings();
        $this->assertSame('0', $stored['block_headless'] ?? null);
        $this->assertSame(['10.10.0.0/21'], $stored['ip_allow_list']);
    }

    public function test_update5011_doesNotSyncCloudIpRanges()
    {
        $storage = StaticContainer::get(Factory::class)->getPluginStorage('TrackingSpamPrevention', '');
        $storage->setValue('block_clouds', true);
        $storage->save();
        $ranges = $this->createMock(BlockedIpRanges::class);
        $ranges->expects($this->never())->method('updateBlockedIpRanges');
        StaticContainer::getContainer()->set(BlockedIpRanges::class, $ranges);
        Config::getInstance()->TrackingSpamPrevention = [];

        $this->runUpdate5011();

        $this->assertSame('0', $this->loadStoredSettings()['block_headless'] ?? null);
    }

    public function test_update5011_doesNotOverwriteStoredBlockHeadless()
    {
        $settings = $this->makeSettings();
        $settings->blockHeadless->setValue(true);
        $settings->save();
        Config::getInstance()->TrackingSpamPrevention = [];

        $this->runUpdate5011();

        $this->assertSame('1', $this->loadStoredSettings()['block_headless'] ?? null);
    }

    public function test_update520_migratesCustomOrganisationsToSystemSetting()
    {
        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_GEOIP_MATCH_PROVIDERS => [' My Custom Org ', '', 'ANOTHER ORG', 'my custom org'],
        ];

        $this->runUpdate520();

        $this->assertSame(['my custom org', 'another org'], $this->makeSettings()->getBlockedOrganisations());
        $this->assertArrayNotHasKey(Configuration::KEY_GEOIP_MATCH_PROVIDERS, Config::getInstance()->TrackingSpamPrevention);
    }

    public function test_update520_defaultListOnly_doesNotStoreSettingButRemovesKey()
    {
        // older updates merged the defaults into the config in a different order than the constant's
        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_GEOIP_MATCH_PROVIDERS => array_reverse(Configuration::DEFAULT_GEOIP_MATCH_PROVIDERS),
        ];

        $this->runUpdate520();

        // nothing stored, the setting keeps following the default list
        $this->assertSame(Configuration::DEFAULT_GEOIP_MATCH_PROVIDERS, $this->makeSettings()->organisationBlockList->getValue());
        $this->assertArrayNotHasKey(Configuration::KEY_GEOIP_MATCH_PROVIDERS, Config::getInstance()->TrackingSpamPrevention);
    }

    public function test_update520_emptiedList_storesEmptyListToKeepBlockingDisabled()
    {
        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_GEOIP_MATCH_PROVIDERS => ['', ' '],
        ];

        $this->runUpdate520();

        // stored empty list, not an unset setting falling back to the defaults
        $this->assertSame([], $this->makeSettings()->organisationBlockList->getValue());
        $this->assertSame([], $this->makeSettings()->getBlockedOrganisations());
        $this->assertArrayNotHasKey(Configuration::KEY_GEOIP_MATCH_PROVIDERS, Config::getInstance()->TrackingSpamPrevention);
    }

    public function test_update520_configKeyAbsent_isNoOp()
    {
        Config::getInstance()->TrackingSpamPrevention = [];

        $this->runUpdate520();

        $this->assertSame(Configuration::DEFAULT_GEOIP_MATCH_PROVIDERS, $this->makeSettings()->organisationBlockList->getValue());
    }

    public function test_update520_configOverrideForNewSetting_storesNothingButRemovesOldKey()
    {
        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_GEOIP_MATCH_PROVIDERS => ['config org'],
            'organisation_block_list' => ['override org'],
        ];

        // must not throw: an override makes the setting unwritable, which would fail the update
        $this->runUpdate520();

        $config = Config::getInstance()->TrackingSpamPrevention;
        $this->assertArrayNotHasKey(Configuration::KEY_GEOIP_MATCH_PROVIDERS, $config);
        $this->assertSame(['override org'], $config['organisation_block_list']);
        $this->assertSame(['override org'], $this->makeSettings()->getBlockedOrganisations());
    }

    public function test_update520_doesNotOverwriteExistingSettingValue()
    {
        $settings = $this->makeSettings();
        $settings->organisationBlockList->setValue(['stored org']);
        $settings->save();

        Config::getInstance()->TrackingSpamPrevention = [
            Configuration::KEY_GEOIP_MATCH_PROVIDERS => ['config org'],
        ];

        $this->runUpdate520();

        $this->assertSame(['stored org'], $this->makeSettings()->getBlockedOrganisations());
        $this->assertArrayNotHasKey(Configuration::KEY_GEOIP_MATCH_PROVIDERS, Config::getInstance()->TrackingSpamPrevention);
    }

    private function runUpdate()
    {
        $update = new Updates_5_1_0();
        $update->doUpdate(new Updater());
    }

    private function runUpdate5011()
    {
        $update = new Updates_5_0_11();
        $update->doUpdate(new Updater());
    }

    private function runUpdate520()
    {
        $update = new Updates_5_2_0();
        $update->doUpdate(new Updater());
    }

    /**
     * Reads the stored rows: a setting returns its default when nothing is stored, so it cannot show
     * whether the update wrote a value.
     */
    private function loadStoredSettings(): array
    {
        return (new Factory())->getPluginStorage('TrackingSpamPrevention', '')->getBackend()->load();
    }

    private function makeSettings(): SystemSettings
    {
        return new SystemSettings();
    }
}
