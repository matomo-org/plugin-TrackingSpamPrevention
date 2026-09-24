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
use Piwik\Settings\Storage\Factory;
use Piwik\Updater;
use Piwik\Updates as PiwikUpdates;

class Updates_5_0_11 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
        $pluginConfig = Config::getInstance()->TrackingSpamPrevention;

        if (is_array($pluginConfig) && array_key_exists('block_headless', $pluginConfig)) {
            return;
        }

        // the container's storage is the copy SystemSettings reads and saves, so the 5.1.0 and 5.2.0
        // updates that can follow in the same run keep this value. SystemSettings::save() itself is
        // avoided because it can start a cloud IP range sync.
        $storage = StaticContainer::get(Factory::class)->getPluginStorage('TrackingSpamPrevention', '');

        // a setting reads as its default when nothing is stored, so check the stored rows
        // (load() rather than loadValue(), which only exists from Matomo 5.9)
        if (array_key_exists('block_headless', $storage->getBackend()->load())) {
            return;
        }

        $storage->setValue('block_headless', false);
        $storage->save();
    }
}
