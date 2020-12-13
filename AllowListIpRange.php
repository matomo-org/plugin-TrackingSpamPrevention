<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Matomo\Network\IP;

class AllowListIpRange
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isAllowed($ip)
    {
        $rangesAllowed = $this->configuration->getIpRangesAlwaysAllowed();

        if (!empty($rangesAllowed)) {
            $ip  = IP::fromStringIP($ip);
            return $ip->isInRanges($rangesAllowed);
        }

        return false;
    }

}
