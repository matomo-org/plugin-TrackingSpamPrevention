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
use Piwik\Plugins\TrackingSpamPrevention\SystemSettings;
use Piwik\Plugins\TrackingSpamPrevention\Updates_5_1_0;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;

require_once PIWIK_INCLUDE_PATH . '/plugins/TrackingSpamPrevention/Updates/5.1.0.php';

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

    private function runUpdate()
    {
        $update = new Updates_5_1_0();
        $update->doUpdate(new Updater());
    }

    private function makeSettings(): SystemSettings
    {
        return new SystemSettings();
    }
}
