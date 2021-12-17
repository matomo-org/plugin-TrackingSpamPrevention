<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Container\StaticContainer;
use Piwik\Intl\Data\Provider\RegionDataProvider;
use Piwik\Piwik;
use Piwik\Plugins\TrackingSpamPrevention\Settings\BlockCloudsSetting;
use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\SettingsPiwik;
use Piwik\Tracker\Cache;
use Piwik\Validators\Email;

class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $max_actions;

    /** @var Setting */
    public $notification_email;

    /** @var BlockCloudsSetting */
    public $block_clouds;

    /** @var Setting */
    public $excludedCountries;

    /** @var Setting */
    public $includedCountries;

    /** @var Setting */
    public $blockHeadless;

    /** @var Setting */
    public $blockServerSideLibraries;

    protected function init()
    {
        $this->block_clouds = $this->createBlockCloudsSetting();
        $this->blockHeadless = $this->createBlockHeadlessSettings();
        $this->blockServerSideLibraries = $this->createBlockServerSideLibrariesSetting();
        $this->max_actions = $this->createMaxActionsSetting();
        $this->notification_email = $this->createNotificationEmail();

        $this->excludedCountries = $this->createExcludedCountriesSetting();
        $this->includedCountries = $this->createIncludedCountriesSetting();
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


    private function createBlockHeadlessSettings()
    {
        return $this->makeSetting('block_headless', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingBlockHeadlessTitle');
            $field->description = Piwik::translate('TrackingSpamPrevention_SettingBlockHeadlessDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    private function createMaxActionsSetting()
    {
        return $this->makeSetting('max_actions_allowed', $default = 0, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingMaxActionsTitle');
            $field->description = Piwik::translate('TrackingSpamPrevention_SettingMaxActionsDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });
    }

    private function createNotificationEmail()
    {
        return $this->makeSetting('notification_email', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingNotificationEmailTitle');
            $field->description = Piwik::translate('TrackingSpamPrevention_SettingNotificationEmailDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->condition = 'max_actions_allowed>0';
            $field->validators[] = new Email();
        });
    }

    public function save()
    {
        parent::save();

        $ranges = StaticContainer::get(BlockedIpRanges::class);

        if ((bool) $this->block_clouds->getValue() !== (bool) $this->block_clouds->getOldValue()) {
            if ($this->block_clouds->getValue()) {
                // is now enabled, lets sync ip ranges
                $ranges->updateBlockedIpRanges();
            } else {
                // we also unset any IP that was banned recently
                $ranges->unsetAllIpRanges();
            }
            Cache::clearCacheGeneral();
        }
    }

    private function createExcludedCountriesSetting() {
        return $this->makeSetting('excluded_countries', [], FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingExcludedCountriesTitle');
            $field->description = Piwik::translate('TrackingSpamPrevention_SettingExcludedCountriesDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_MULTI_TUPLE;
            $field1 = new FieldConfig\MultiPair("Country", 'country', FieldConfig::UI_CONTROL_SINGLE_SELECT);
            $field1->availableValues = $this->listCountries();
            $field->uiControlAttributes['field1'] = $field1->toArray();

            $self = $this;
            $field->transform = function ($value) use ($self) {
                return $self->transformCountryList($value);
            };

            $field->validate = function ($value) use ($field1) {
                foreach ($value as $country) {
                    if (empty($country['country'])) {
                        continue;
                    }
                    if ($country['country'] === 'xx') {
                        continue; // valid,  country not detected
                    }
                    if (!isset($field1->availableValues[$country['country']])) {
                        throw new \Exception('Invalid country code');
                    }
                }
            };
        });
    }

    public function transformCountryList($value)
    {
        if (!empty($value) && is_array($value)) {
            $newVal = [];
            foreach ($value as $index => $val) {
                if (empty($val['country'])) {
                    continue;
                }
                $newVal[] = ['country' => $val['country']];
            }
            return $newVal;
        }
        return $value;
    }

    private function createIncludedCountriesSetting() {
        return $this->makeSetting('included_countries', [], FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingIncludedCountriesTitle');
            $field->description = Piwik::translate('TrackingSpamPrevention_SettingIncludedCountriesDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_MULTI_TUPLE;
            $field1 = new FieldConfig\MultiPair("Country", 'country', FieldConfig::UI_CONTROL_SINGLE_SELECT);
            $field1->availableValues = $this->listCountries();
            $field->uiControlAttributes['field1'] = $field1->toArray();

            $self = $this;
            $field->transform = function ($value) use ($self) {
                return $self->transformCountryList($value);
            };
            $field->validate = function ($value) use ($field1) {
                foreach ($value as $country) {
                    if (empty($country['country'])) {
                        continue;
                    }
                    if ($country['country'] === 'xx') {
                        continue; // valid,  country not detected
                    }
                    if (!isset($field1->availableValues[$country['country']])) {
                        throw new \Exception('Invalid country code');
                    }
                }
            };
        });
    }

    private function createBlockServerSideLibrariesSetting() {
        return $this->makeSetting('blockServerSideLibraries', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('TrackingSpamPrevention_SettingBlockServerSideLibrariesTitle');
            $field->inlineHelp = Piwik::translate('TrackingSpamPrevention_SettingBlockServerSideLibrariesDescription', array('<strong>','</strong>','<br>'));
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }


    private function listCountries() {
        $regionDataProvider = StaticContainer::get(RegionDataProvider::class);
        $countryList = $regionDataProvider->getCountryList();
        array_walk($countryList, function (&$item, $key) {
            $item = Piwik::translate('Intl_Country_' . strtoupper($key));
        });
        asort($countryList); //order by localized name
        return $countryList;
    }

    public function getExcludedCountryCodes()
    {
        return $this->settingToCountryCodes($this->excludedCountries);
    }

    public function getIncludedCountryCodes()
    {
        return $this->settingToCountryCodes($this->includedCountries);
    }

    private function settingToCountryCodes(Setting $setting) {
        $val = $setting->getValue();

        if (empty($val) || !is_array($val)) {
            return [];
        }

        $codes = [];
        foreach ($val as $value) {
            if (!empty($value['country'])) {
                $codes[] = $value['country'];
            }
        }
        return $codes;
    }

}
