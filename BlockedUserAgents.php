<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

class BlockedUserAgents
{
    public function isBlockedUserAgent($userAgent, $blockedLists)
    {
        if (empty($userAgent)) {
            return false;
        }

        foreach ($blockedLists as $blockedList) {
            if (empty(strtolower($blockedList['blockUserAgent']))) {
                continue;
            }
            if (strtolower($blockedList['blockUserAgent']) === strtolower($userAgent)) {
                return true;
            }
        }

        return false;
    }
}