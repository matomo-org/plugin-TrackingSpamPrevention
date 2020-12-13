<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Matomo\Network\IP;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Tracker\Request;
use Piwik\Tracker\VisitExcluded;

class TrackingSpamPrevention extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'Tracker.isExcludedVisit' => 'isExcludedVisit',
            'Tracker.setTrackerCacheGeneral' => 'setTrackerCacheGeneral',
        ];
    }

    public function install()
    {
        $config = new Configuration();
        $config->install();
    }

    public function uninstall()
    {
        $config = new Configuration();
        $config->uninstall();
    }

    public function setTrackerCacheGeneral(&$cache)
    {
        $systemSettings = $this->getSystemSettings();
        if (!$systemSettings->block_clouds->getValue()) {
            $cache[BlockedIpRanges::OPTION_KEY] = [];
            return;
        }
        $ranges = $this->getBlockedIpRanges();
        $cache[BlockedIpRanges::OPTION_KEY] = $ranges->getBlockedRanges();
    }

    public function isExcludedVisit(&$excluded, Request $request)
    {
        if ($excluded) {
            return; // already excluded, not needed to check
        }

        if (!$this->getSystemSettings()->block_clouds->getValue()) {
            return;
        }

        $visitExcluded = new VisitExcluded($request);
        $ipString = $request->getIpString();

        $ip = IP::fromStringIP($ipString);
        if ($visitExcluded->isChromeDataSaverUsed($ip)) {
            Common::printDebug("Not excluding visit as chrome data saver is used");
            return;
        }

        if (StaticContainer::get(AllowListIpRange::class)->isAllowed($ipString)) {
            Common::printDebug("Not excluding visit as it matches an IP range that is always allowed");
            return;
        }

        if (StaticContainer::get(BlockedGeoIp::class)->isExcluded($ipString, $request->getBrowserLanguage())) {
            Common::printDebug("Excluding visit as geoip detects a cloud provider");
            $excluded = true;
            return;
        }

        if ($this->getBlockedIpRanges()->isExcluded($ipString)) {
            Common::printDebug("Excluding visit as IP originates from a cloud provider");
            $excluded = true;
            return;
        }
    }

    private function getSystemSettings()
    {
        return StaticContainer::get(SystemSettings::class);
    }

    private function getBlockedIpRanges()
    {
        return StaticContainer::get(BlockedIpRanges::class);
    }

    public function isTrackerPlugin()
    {
        return true;
    }

}
