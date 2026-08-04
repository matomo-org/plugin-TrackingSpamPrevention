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

/**
 * 5.2.0 migrations:
 *  - Migrates the `block_geoip_organisations` config value to the `organisation_block_list` system setting.
 *    The setting defaults to Configuration::DEFAULT_GEOIP_MATCH_PROVIDERS, so nothing is stored when the
 *    config list matches the defaults; such installs keep following the constant automatically.
 *
 * Note for future default-list additions: installs with a stored (customised) setting value no longer pick
 * up changes to the constant. Ship such additions as a migration that appends only the newly added entries
 * to a stored, non-empty setting value and leaves unset settings alone — re-merging the whole default list
 * would resurrect entries an admin deliberately removed in the UI.
 */
class Updates_5_2_0 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
        $this->migrateBlockedOrganisations();
    }

    private function migrateBlockedOrganisations(): void
    {
        $config = Config::getInstance();
        $pluginConfig = $config->TrackingSpamPrevention;

        if (!is_array($pluginConfig) || !array_key_exists(Configuration::KEY_GEOIP_MATCH_PROVIDERS, $pluginConfig)) {
            return;
        }

        $organisations = $this->getNormalizedOrganisations($pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS]);

        // an emptied config list is not migrated: the defaults apply again, matching how previous updates
        // (5.0.9, 5.1.0) always merged the default list back into an emptied config value
        if (!empty($organisations) && !$this->equalsDefaultList($organisations)) {
            $settings = StaticContainer::get(SystemSettings::class);
            // updates run as super user, but the setting also reports as unwritable when an
            // `organisation_block_list` config override already exists, so force writability to keep
            // the migration from failing
            $settings->organisationBlockList->setIsWritableByCurrentUser(true);

            if ($settings->organisationBlockList->getValue() === Configuration::DEFAULT_GEOIP_MATCH_PROVIDERS) {
                $settings->organisationBlockList->setValue($organisations);
                $settings->save();
            }
        }

        unset($pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS]);
        $config->TrackingSpamPrevention = $pluginConfig;

        try {
            $config->forceSave();
        } catch (\Exception $e) {
            // the config file might not be writable, the leftover key is no longer read anywhere
        }
    }

    private function getNormalizedOrganisations($configOrganisations): array
    {
        if (!is_array($configOrganisations)) {
            return [];
        }

        $organisations = [];
        foreach ($configOrganisations as $organisation) {
            $organisation = mb_strtolower(trim((string) $organisation));
            if ($organisation !== '') {
                $organisations[] = $organisation;
            }
        }

        return array_values(array_unique($organisations));
    }

    private function equalsDefaultList(array $organisations): bool
    {
        $defaults = Configuration::DEFAULT_GEOIP_MATCH_PROVIDERS;

        // compare order-insensitively: a never-customised pre-5.2.0 config holds the same entries in the
        // order older updates merged them, which need not match the constant's order
        sort($organisations);
        sort($defaults);

        return $organisations === $defaults;
    }
}
