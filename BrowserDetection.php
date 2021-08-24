<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Matomo\Network\IP;
use Piwik\Cache as PiwikCache;
use Piwik\Common;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\IpRangeProviderInterface;
use Piwik\SettingsPiwik;
use Piwik\Tracker\Cache;

class BrowserDetection
{
    public function isHeadlessBrowser($userAgent)
    {
        if (empty($userAgent)) {
            return false;
        }

        $browsers = [
            'HeadlessChrome',
            'PhantomJS',
            'Electron',
            'ApacheBench', // Load test
            'Siege', // Load test
            'k6', // Load test
            'Radview', // Load test
            'Locust', // Load test
            'Cypress', //Testing Tool
            'Site24x7', //Site Monitoring
            'Pingdom' //Site Monitoring
        ];
        foreach ($browsers as $browser) {
            if (stripos($userAgent, $browser) !== false) {
                return true;
            }
        }
        return false;
    }

}
