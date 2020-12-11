<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Common;

class Tasks extends \Piwik\Plugin\Tasks
{
    /**
     * @var SystemSettings
     */
    private $systemSettings;

    public function __construct(SystemSettings $systemSettings)
    {
        $this->systemSettings = $systemSettings;
    }

    public function schedule()
    {
        $this->daily('updateBlockedIpRanges');
    }

    /**
     * To test execute the following command:
     * `./console core:run-scheduled-tasks --force "Piwik\Plugins\TrackingSpamPrevention\Tasks.updateBlockedIpRanges"`
     *
     */
    public function updateBlockedIpRanges()
    {
        $ranges = new BlockedIpRanges();
        if ($this->systemSettings->block_clouds->getValue()) {
            $ranges->updateBlockedIpRanges();
        } else {
            $ranges->unsetAllIpRanges();
        }
    }

}
