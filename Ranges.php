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
use Piwik\Http;
use Piwik\Option;
use Piwik\Tracker\Cache;

class Ranges
{
    const OPTION_KEY = 'TrackingSpamBlockedIpRanges';

    public function getBlockedRanges()
    {
        $range = Option::get(self::OPTION_KEY);
        if (empty($range)) {
            return [];
        }
        return json_decode($range, true);
    }

    /**
     * An array of blocked ranges
     * @param string[] $ranges
     */
    public function setBlockedRanges($ranges)
    {
        // we index them by first character for performance reasons see the excluded method
        $indexedRange = [];
        foreach ($ranges as $range) {
            $first = Common::mb_substr($range, 0, 1);
            if (empty($indexedRange[$first])) {
                $indexedRange[$first] = [];
            }
            $indexedRange[$first][] = $range;
        }

        Option::set(self::OPTION_KEY, json_encode($indexedRange));
    }

    public function isExcluded($ip)
    {
        if (empty($ip)) {
            return false;
        }

        // for performance reasons we index ranges by first character of ip. assuming this works in most cases.
        // so we compare less ranges as it is slow to compare many ranges
        $first = Common::mb_substr($ip, 0, 1);

        $cache = Cache::getCacheGeneral();
        if (empty($first) || empty($cache[self::OPTION_KEY][$first])) {
            return;
        }

        $ip  = IP::fromStringIP($ip);
        $key = 'TrackingSpamPreventionIsIpInRange' . $ip->toString();

        $cache = PiwikCache::getTransientCache();
        if ($cache->contains($key)) {
            $isInRanges = $cache->fetch($key);
        } else {
            $isInRanges = $ip->isInRanges($cache[self::OPTION_KEY][$first]);

            $cache->save($key, $isInRanges);
        }

        return $isInRanges;
    }

    public function updateBlockedIpRanges()
    {
        $ranges = [];

        $gcloud = Http::sendHttpRequest('https://www.gstatic.com/ipranges/cloud.json', 20);
        $gcloud = json_decode($gcloud, true);
        foreach ($gcloud['prefixes'] as $prefix) {
            if (isset($prefix['ipv4Prefix'])) {
                $ranges[] = $prefix['ipv4Prefix'];
            }
            if (isset($prefix['ipv6Prefix'])) {
                $ranges[] = $prefix['ipv6Prefix'];
            }
        }

        // TODO: Error handling below if structure changes or if request failed etc...

        // TODO get info somehow for up to date from url https://www.microsoft.com/en-us/download/confirmation.aspx?id=56519
        $content = Http::sendHttpRequest('https://www.microsoft.com/en-us/download/confirmation.aspx?id=56519', 20);
        $prefixUrl = 'href="';
        $posStart = strpos($content, $prefixUrl . 'https://download.microsoft.com/download/');
        $posEnd = strpos($content, '"', $posStart + strlen($prefixUrl)); // we don't want to match the " in href="
        $content = Common::mb_substr($content, $posStart - strlen($prefixUrl) + 2, $posEnd - $posStart - strlen($prefixUrl));
        $link = trim($content, '="' . "'");
        // TODO should probably assert that file ends in .json

        // see also https://docs.microsoft.com/en-us/azure/virtual-network/service-tags-overview .
        // The api itself we don't really want to use. We'd need a subscriptionId. Unless we fetch it on a matomo server and make it available through a JSON there but then not sure if we are allowed to do that
        // or can we get it differently? Like do they have a fixed URL or so?
        // seems we could maybe assume the URL always stays the same except for the date part but then we'd need to check which date works (I've seen older URLs with same strucutre only date different)
        // might be easiest to fetch "confirmation" page and then extract the URL from there?
        // should look like 'https://download.microsoft.com/download/7/1/D/71D86715-5596-4529-9B13-DA13A5DE5B63/ServiceTags_Public_20201207.json'

        $azure = Http::sendHttpRequest($link, 20);
        $azure = json_decode($azure, true);
        $azureValues = $azure['values'];
        foreach ($azureValues as $azureValue) {
            foreach ($azureValue['properties']['addressPrefixes'] as $ip) {
                $ranges[] = $ip;
            }
        }

        $aws = Http::sendHttpRequest('https://ip-ranges.amazonaws.com/ip-ranges.json', 20);
        $aws = json_decode($aws, true);
        foreach ($aws['prefixes'] as $range) {
            if (isset($range['ip_prefix'])) {
                $ranges[] = $range['ip_prefix'];
            }
        }
        foreach ($aws['ipv6_prefix'] as $range) {
            if (isset($range['ipv6_prefix'])) {
                $ranges[] = $range['ipv6_prefix'];
            }
        }
var_export($ranges);
        $this->setBlockedRanges($ranges);
    }

}
