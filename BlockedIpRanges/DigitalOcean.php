<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges;

use Piwik\Http;

class DigitalOcean implements IpRangeProviderInterface
{
    public function getRanges(): array
    {
        $ranges = [];

        $digitalOcean = Http::sendHttpRequest('https://digitalocean.com/geo/google.csv', 120, null, null, 0, false, false, true);

        if (empty($digitalOcean) || empty($digitalOcean['status']) || $digitalOcean['status'] != 200) {
            throw new \Exception('Failed to retrieve digital ocean IP ranges');
        }

        if (empty($digitalOcean['data'])) {
            return [];
        }

        $digitalOcean = str_getcsv($digitalOcean['data'], ',', '');

        if (empty($digitalOcean)) {
            throw new \Exception('Failed to parse digital ocean IP ranges');
        }

        foreach ($digitalOcean as $block) {
            $ranges[] = $block[0];
        }

        if (empty($ranges)) {
            throw new \Exception('Failed to retrieve any digital ocean IP range.');
        }

        return $ranges;
    }

}
