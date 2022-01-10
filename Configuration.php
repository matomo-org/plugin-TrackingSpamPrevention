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
    const DEFAULT_GEOIP_MATCH_PROVIDERS = ['alicloud', 'alibaba cloud', 'digitalocean', 'digital ocean'];

    const KEY_RANGE_THROW_EXCEPTION = 'block_cloud_sync_throw_exception_on_error';
    const KEY_RANGE_ALLOW_LIST = 'iprange_allowlist';
    const KEY_GEOIP_MATCH_PROVIDERS = 'block_geoip_organisations';

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
        if (empty($default[self::KEY_GEOIP_MATCH_PROVIDERS])) {
            $default[self::KEY_GEOIP_MATCH_PROVIDERS] = self::DEFAULT_GEOIP_MATCH_PROVIDERS;
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

        $value = array_values(array_filter($value));
        $value = array_map(function ($range) {
            if (strpos($range, '/') === false) {
                // we assume user did not enter a range so we make it one that matches that one ip
                if (strpos($range, '.') !== false) {
                    $range .= '/32';
                } elseif (strpos($range, ':') !== false) {
                    $range .= '/128';
                }
            }
            return $range;
        }, $value);

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
