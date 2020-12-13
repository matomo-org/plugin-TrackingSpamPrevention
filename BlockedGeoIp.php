<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Plugins\UserCountry\LocationProvider;
use Piwik\Plugins\UserCountry\VisitorGeolocator;

class BlockedGeoIp
{
    /**
     * @var array
     */
    private $blockedProviders;

    public function __construct($blockedProviders)
    {
        $this->blockedProviders = $blockedProviders;
    }

    public function isExcluded($ip, $language)
    {
        $visitorLocator = new VisitorGeolocator();
        $info = array('lang' => $language, 'ip' => $ip);
        $result = $visitorLocator->getLocation($info, $useClassCache = true);

        if (!empty($result[LocationProvider::ORG_KEY])) {
            $org = $result[LocationProvider::ORG_KEY];
            foreach ($this->blockedProviders as $blockedProvider) {
                if (!empty($blockedProvider) && stripos($org, $blockedProvider) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

}
