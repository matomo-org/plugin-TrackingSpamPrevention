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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BlockGeoIpOrganisation extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('trackingspamprevention:block-geo-ip-organisation');
        $this->setDescription('Blocks a new GeoIP organisation. It will save the organisation in the config file.');
        $this->addOption('organisation-name', null, InputOption::VALUE_REQUIRED, 'Name of the organisation to block:');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkAllRequiredOptionsAreNotEmpty($input);

        $config = Config::getInstance();
        $pluginConfig = $config->TrackingSpamPrevention;

        if (empty($pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS])) {
            $pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS] = [];
        }
        $pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS][] = mb_strtolower(trim($input->getOption('organisation-name')));
        $pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS] = array_values(array_unique($pluginConfig[Configuration::KEY_GEOIP_MATCH_PROVIDERS]));

        $config->TrackingSpamPrevention = $pluginConfig;
        $config->forceSave();
    }
}
