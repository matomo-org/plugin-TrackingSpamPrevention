<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Matomo\Network\IP;
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

    public function setTrackerCacheGeneral(&$cache)
    {
        $systemSettings = StaticContainer::get(SystemSettings::class);
        if (!$systemSettings->block_clouds->getValue()) {
            return;
        }
        $range = new Ranges();
        $cache[Ranges::OPTION_KEY] = $range->getBlockedRanges();
    }

    public function isExcludedVisit(&$excluded, Request $request)
    {
        if (!$excluded) {
            return; // not needed to check
        }

        $visitExcluded = new VisitExcluded($request);
        $ip = IP::fromStringIP($request->getIpString());

        if ($visitExcluded->isChromeDataSaverUsed($ip)) {
            return;
        }

        $range = new Ranges();
        $excluded = $range->isExcluded($request->getIpString());
    }

    public function isTrackerPlugin()
    {
        return true;
    }

}
