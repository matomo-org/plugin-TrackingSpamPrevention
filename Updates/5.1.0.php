<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Updater;
use Piwik\Updates as PiwikUpdates;
use Piwik\Validators\IpRanges;

/**
 * Migrates the `iprange_allowlist` config value to the `ip_allow_list` system setting.
 */
class Updates_5_1_0 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
        $config = Config::getInstance();
        $pluginConfig = $config->TrackingSpamPrevention;

        if (!is_array($pluginConfig) || !array_key_exists(Configuration::KEY_RANGE_ALLOW_LIST, $pluginConfig)) {
            return;
        }

        $ranges = $this->getValidRanges($pluginConfig[Configuration::KEY_RANGE_ALLOW_LIST]);

        if (!empty($ranges)) {
            $settings = StaticContainer::get(SystemSettings::class);
            $settings->ipAllowList->setIsWritableByCurrentUser(true);

            if (empty($settings->ipAllowList->getValue())) {
                $settings->ipAllowList->setValue($ranges);
                $settings->save();
            }
        }

        unset($pluginConfig[Configuration::KEY_RANGE_ALLOW_LIST]);
        $config->TrackingSpamPrevention = $pluginConfig;

        try {
            $config->forceSave();
        } catch (\Exception $e) {
            // the config file might not be writable, the leftover key is no longer read anywhere
        }
    }

    private function getValidRanges($configRanges): array
    {
        if (!is_array($configRanges)) {
            return [];
        }

        $validator = new IpRanges();

        $ranges = [];
        foreach ($configRanges as $range) {
            $range = trim((string) $range);
            if ($range === '') {
                continue;
            }
            try {
                $validator->validate([$range]);
                $ranges[] = $range;
            } catch (\Exception $e) {
                // drop invalid entries, they never matched any request anyway
            }
        }

        return array_values(array_unique($ranges));
    }
}
