<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration;

use Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges;
use Piwik\Plugins\TrackingSpamPrevention\Configuration;
use Piwik\Plugins\TrackingSpamPrevention\SystemSettings;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TrackingSpamPrevention
 * @group SystemSettingsTest
 * @group Plugins
 */
class SystemSettingsTest extends IntegrationTestCase
{
    /**
     * @var SystemSettings
     */
    private $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->settings = new SystemSettings();
    }

    public function test_max_actions_default()
    {
        $this->assertSame(0, $this->settings->max_actions->getValue());
    }

    public function test_block_cloud_default()
    {
        $this->assertSame(false, $this->settings->block_clouds->getValue());
    }

    public function test_block_cloud_enable_getOldValue()
    {
        $this->settings->block_clouds->setValue(1);
        $this->assertSame(true, $this->settings->block_clouds->getValue());
        $this->assertSame(false, $this->settings->block_clouds->getOldValue());
    }

    public function test_block_headless_default()
    {
        $this->assertSame(false, $this->settings->blockHeadless->getValue());
    }

    public function test_block_Headless_enable()
    {
        $this->settings->blockHeadless->setValue(1);
        $this->assertSame(true, $this->settings->blockHeadless->getValue());
    }

    public function test_notification_email_default()
    {
        $this->assertSame('', $this->settings->notification_email->getValue());
    }

    public function test_notification_email_errrosWhenNotValidEmail()
    {
        $this->expectException(\Exception::class);
        $this->settings->notification_email->setValue('foo');
    }

    public function test_notification_email_setValidEmail()
    {
        $this->settings->notification_email->setValue('foo@matomo.org');
        $this->assertSame('foo@matomo.org', $this->settings->notification_email->getValue());
    }

    public function test_notification_email_setEmptyValue()
    {
        $this->settings->notification_email->setValue('');
        $this->assertSame('', $this->settings->notification_email->getValue());
    }

    public function test_exclude_countries_default()
    {
        $this->assertSame([], $this->settings->excludedCountries->getValue());
    }

    public function test_exclude_getExcludedCountryCodes_default()
    {
        $this->assertSame([], $this->settings->getExcludedCountryCodes());
    }

    public function test_exclude_countries()
    {
        $this->settings->excludedCountries->setValue([
            ['country' => 'de'],['country' => 'fr'], ['country' => 'nz']
        ]);
        $this->assertSame(['de', 'fr', 'nz'], $this->settings->getExcludedCountryCodes());
    }

    public function test_excludeCountries_setInvalidValue()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid country code');
        $this->settings->excludedCountries->setValue([
            ['country' => 'de'],['country' => 'foo']
        ]);
    }

    public function test_include_countries_default()
    {
        $this->assertSame([], $this->settings->includedCountries->getValue());
    }

    public function test_include_countries()
    {
        $this->settings->includedCountries->setValue([
            ['country' => 'de'],['country' => 'fr'], ['country' => 'nz']
        ]);
        $this->assertSame(['de', 'fr', 'nz'], $this->settings->getIncludedCountryCodes());
    }

    public function test_includeCountries_setInvalidValue()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid country code');
        $this->settings->includedCountries->setValue([
            ['country' => 'de'],['country' => 'foo']
        ]);
    }

    public function test_include_getIncludedCountryCodes_default()
    {
        $this->assertSame([], $this->settings->getIncludedCountryCodes());
    }

    public function test_save_shouldSyncWhenEnabled()
    {
        $ranges = $this->makeRanges();
        $this->assertEmpty($ranges->getBlockedRanges());
        $this->settings->block_clouds->setValue(true);
        $this->settings->save();
        $this->assertNotEmpty($ranges->getBlockedRanges());
    }

    public function test_save_shouldEmptyRangesWhenDisabledButNoChange()
    {
        $ranges = $this->makeRanges();
        $ranges->updateBlockedIpRanges();
        $this->assertNotEmpty($ranges->getBlockedRanges());
        $this->settings->block_clouds->setValue(false);
        $this->settings->save();
        $this->assertNotEmpty($ranges->getBlockedRanges());
    }

    public function test_save_shouldEmptyRangesWhenDisabled()
    {
        $ranges = $this->makeRanges();
        $ranges->updateBlockedIpRanges();
        $this->assertNotEmpty($ranges->getBlockedRanges());
        $this->settings->block_clouds->setValue(true);// need to make it think there was a change
        $this->settings->block_clouds->setValue(false);
        $this->settings->save();
        $this->assertEmpty($ranges->getBlockedRanges());
    }

    private function makeRanges()
    {
        $ranges = [
            new BlockedIpRanges\VariableRange([
                '10.10.0.0/21',
            ]),
        ];

        return new BlockedIpRanges($ranges, new Configuration());
    }

}
