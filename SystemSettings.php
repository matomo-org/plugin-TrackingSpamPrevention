<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\TrackingSpamPrevention\Settings\BlockCloudsSetting;
use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\SettingsPiwik;

class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $max_actions;

    /** @var BlockCloudsSetting */
    public $block_clouds;

    protected function init()
    {
        // System setting --> allows selection of a single value
        $this->max_actions = $this->createMaxActionsSetting();
        $this->block_clouds = $this->createBlockCloudsSetting();
    }

    private function createBlockCloudsSetting()
    {
        $setting = new BlockCloudsSetting('block_clouds', false, FieldConfig::TYPE_BOOL, $this->pluginName);
        $setting->setConfigureCallback(function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingBlockCloudTitle');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->description = Piwik::translate('TrackingSpamPrevention_SettingBlockCloudDescription');
            if (!SettingsPiwik::isInternetEnabled()) {
                $field->description = Piwik::translate('TrackingSpamPrevention_BlockCloudNoteInternetDisabled') . $field->description;
            }
        });
        $this->addSetting($setting);
        return $setting;
    }

    private function createMaxActionsSetting()
    {
        return $this->makeSetting('max_actions_allowed', $default = 0, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingMaxActionsTitle');
            $field->description = Piwik::translate('TrackingSpamPrevention_SettingMaxActionsDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });
    }

    public function save()
    {
        parent::save();

        $ranges = StaticContainer::get(BlockedIpRanges::class);

        if ($this->block_clouds->getValue() && (bool) $this->block_clouds->getValue() !== (bool) $this->block_clouds->getOldValue()) {
            // was now enabled
            $ranges->updateBlockedIpRanges();
        } elseif (!$this->block_clouds->getValue()) {
            $ranges->unsetAllIpRanges();
        }
    }

}
