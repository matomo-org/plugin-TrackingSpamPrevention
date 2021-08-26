<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Container\StaticContainer;
use Piwik\DeviceDetector\DeviceDetectorFactory;

class ServerSideLibraryDetection {

    public function isLibrary($userAgent) {
        if (empty($userAgent)) {
            return false;
        }
        $staticContainer = StaticContainer::get(DeviceDetectorFactory::class)->makeInstance($userAgent);

        return $staticContainer->isLibrary();
    }
}