<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Config;
use Piwik\Plugins\TrackingSpamPrevention\Configuration;

class BlockGeoIpOrganisation extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('trackingspamprevention:block-geo-ip-organisation');
        $this->setDescription('Blocks a new GeoIP organisation. It will save the organisation in the config file.');
        $this->addRequiredValueOption('organisation-name', null, 'Name of the organisation to block:');
    }

    /**
     * @return int
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $this->checkAllRequiredOptionsAreNotEmpty();

        $config = Config::getInstance();
        $pluginConfig = $config->TrackingSpamPrevention;

        if (empty($pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS])) {
            $pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS] = [];
        }

        $name = $input->getOption('organisation-name');
        $pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS][] = mb_strtolower(trim($name));

        $pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS] = array_values(array_unique($pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS]));

        $config->TrackingSpamPrevention = $pluginConfig;
        $config->forceSave();

        return self::SUCCESS;
    }
}
