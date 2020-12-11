<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Container\StaticContainer;
use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\SettingsPiwik;
use Piwik\Validators\NotEmpty;

class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $max_actions;

    /** @var Setting */
    public $block_clouds;

    protected function init()
    {
        // System setting --> allows selection of a single value
        $this->max_actions = $this->createMaxActionsSetting();
        $this->block_clouds = $this->createBlockCloudsSetting();
    }

    private function createBlockCloudsSetting()
    {
        return $this->makeSetting('block_clouds', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = 'Block tracking requests from Cloud';
            $field->description = 'If enabled, it will block tracking requests that originated from Cloud providers like Azure, AWS and Google Cloud. If you are only tracking using the JavaScript tracker then this setting should be safe to enable as tracking requests from humans would not originate from these clouds. The setting applies to all your sites. Enabling this feature will cause your server to fetch the up to date list of IP ranges from Google, AWS and Azure servers.';
            if (!SettingsPiwik::isInternetEnabled()) {
                $field->description = 'As you have internet disabled in your config, this feature won\'t work. ' . $field->description;
            }
        });
    }

    private function createMaxActionsSetting()
    {
        return $this->makeSetting('max_actions_allowed', $default = 0, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = 'Max actions per visit to record';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = 'Define how many actions a visit should max have. Enter 0 to allow unlimited actions (default). Most sites have extremely rarely say more than say 200 actions per visit. It many cases it might be therefore to assume that if someone has more than a specific amount of actions, it might be actually tracking spam, or a bot, or something else unnatural causing these actions and it may be safe to stop recording further actions for that visit to have less inaccurate data and to reduce server load. The IP address of this visit will then be blocked for up to 24 hours.';
        });
    }

    public function save()
    {
        parent::save();

        $ranges = StaticContainer::get(BlockedIpRanges::class);
        if ($this->block_clouds->getValue()) {
            $ranges->updateBlockedIpRanges();
        } else {
            $ranges->unsetAllIpRanges();
        }
    }

}
