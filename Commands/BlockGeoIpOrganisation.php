<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\TrackingSpamPrevention\SystemSettings;

class BlockGeoIpOrganisation extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('trackingspamprevention:block-geo-ip-organisation');
        $this->setDescription('Blocks a new GeoIP organisation. It will save the organisation in the "Organisation block list" system setting.');
        $this->addRequiredValueOption('organisation-name', null, 'Name of the organisation to block:');
    }

    protected function doExecute(): int
    {
        $this->checkAllRequiredOptionsAreNotEmpty();

        $name = mb_strtolower(trim($this->getInput()->getOption('organisation-name')));

        $settings = StaticContainer::get(SystemSettings::class);
        $setting = $settings->organisationBlockList;
        // the command runs without a session, and the setting also reports as unwritable when an
        // `organisation_block_list` config override exists, so force writability
        $setting->setIsWritableByCurrentUser(true);

        $organisations = $setting->getValue();
        if (!is_array($organisations)) {
            $organisations = [];
        }
        $organisations[] = $name;

        $setting->setValue(array_values(array_unique($organisations)));
        $settings->save();

        $this->getOutput()->writeln(sprintf('<info>Added "%s" to the organisation block list.</info>', $name));

        return self::SUCCESS;
    }
}
