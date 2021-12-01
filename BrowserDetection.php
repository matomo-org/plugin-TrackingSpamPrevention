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
use Piwik\Container\StaticContainer;
use Piwik\DeviceDetector\DeviceDetectorFactory;
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
            'Siege/', // Load test
            'https://k6.io/', // Load test, Added full URL to handle case where UA can be fook6bar or fook6/
            'Radview', // Load test
            'Locust', // Load test
            'Cypress' //Testing Tool
        ];
        foreach ($browsers as $browser) {
            if (stripos($userAgent, $browser) !== false) {
                return true;
            }
        }
        return false;
    }

    public function isLibrary($userAgent)
    {
        if (empty($userAgent)) {
            return false;
        }
        $staticContainer = StaticContainer::get(DeviceDetectorFactory::class)->makeInstance($userAgent);

        return $staticContainer->isLibrary();
    }

}
