<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration;

use Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges;
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

    public function test_save_shouldSyncWhenEnabled()
    {
        $ranges = $this->makeRanges();
        $this->assertEmpty($ranges->getBlockedRanges());
        $this->settings->block_clouds->setValue(true);
        $this->settings->save();
        $this->assertNotEmpty($ranges->getBlockedRanges());
    }

    public function test_save_shouldEmptyRangesWhenDisabled()
    {
        $ranges = $this->makeRanges();
        $ranges->updateBlockedIpRanges();
        $this->assertNotEmpty($ranges->getBlockedRanges());
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

        return new BlockedIpRanges($ranges);
    }

}
