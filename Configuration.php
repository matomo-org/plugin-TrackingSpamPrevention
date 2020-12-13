<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Config;

class Configuration
{
    const DEFAULT_RANGE_THROW_EXCEPTION = 0;
    const DEFAULT_RANGE_ALLOW_LIST = [''];

    const KEY_RANGE_THROW_EXCEPTION = 'iprange_sync_throw_exception_on_error';
    const KEY_RANGE_ALLOW_LIST = 'block_cloud_iprange_allowlist';

    public function install()
    {
        $config = $this->getConfig();

        $default = $config->TrackingSpamPrevention;
        if (empty($default)) {
            $default = array();
        }

        if (empty($default[self::KEY_RANGE_THROW_EXCEPTION])) {
            $default[self::KEY_RANGE_THROW_EXCEPTION] = self::DEFAULT_RANGE_THROW_EXCEPTION;
        }
        if (empty($default[self::KEY_RANGE_ALLOW_LIST])) {
            $default[self::KEY_RANGE_ALLOW_LIST] = self::DEFAULT_RANGE_ALLOW_LIST;
        }

        $config->TrackingSpamPrevention = $default;

        $config->forceSave();
    }

    public function uninstall()
    {
        $config = $this->getConfig();
        $config->TrackingSpamPrevention = array();
        $config->forceSave();
    }

    /**
     * @return bool
     */
    public function shouldThrowExceptionOnIpRangeSync()
    {
        $value = $this->getConfigValue(self::KEY_RANGE_THROW_EXCEPTION, self::DEFAULT_RANGE_THROW_EXCEPTION);

        if ($value === false || $value === '' || $value === null) {
            $value = self::KEY_RANGE_THROW_EXCEPTION;
        }

        return (bool) $value;
    }

    /**
     * @return array
     */
    public function getIpRangesAlwaysAllowed()
    {
        $value = $this->getConfigValue(self::KEY_RANGE_ALLOW_LIST, self::DEFAULT_RANGE_ALLOW_LIST);

        if (empty($value) || !is_array($value)) {
            $value = self::DEFAULT_RANGE_ALLOW_LIST;
        }

        $value = array_filter($value);

        return $value;
    }

    private function getConfig()
    {
        return Config::getInstance();
    }

    private function getConfigValue($name, $default)
    {
        $config = $this->getConfig();
        $attribution = $config->TrackingSpamPrevention;
        if (isset($attribution[$name])) {
            return $attribution[$name];
        }
        return $default;
    }
}
