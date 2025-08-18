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
    public const DEFAULT_RANGE_THROW_EXCEPTION = 0;
    public const DEFAULT_RANGE_ALLOW_LIST = [''];
    public const DEFAULT_GEOIP_MATCH_PROVIDERS = ['alicloud', 'alibaba cloud', 'digitalocean', 'digital ocean'];

    public const KEY_RANGE_THROW_EXCEPTION = 'block_cloud_sync_throw_exception_on_error';
    public const KEY_RANGE_ALLOW_LIST = 'iprange_allowlist';
    public const KEY_GEOIP_MATCH_PROVIDERS = 'block_geoip_organisations';

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
    public function getIpRangesAlwaysAllowed(): array
{
    // existing: read from config.ini.php
    $value = $this->getConfigValue(self::KEY_RANGE_ALLOW_LIST, self::DEFAULT_RANGE_ALLOW_LIST);
    if (empty($value) || !is_array($value)) {
        $value = self::DEFAULT_RANGE_ALLOW_LIST;
    }
    $fromConfig = array_values(array_filter($value));

    // normalize: add /32 or /128 if user provided single IP
    $fromConfig = array_map(function ($range) {
        $range = trim($range);
        if ($range === '') return $range;
        if (strpos($range, '/') === false) {
            if (strpos($range, '.') !== false) {
                $range .= '/32';
            } elseif (strpos($range, ':') !== false) {
                $range .= '/128';
            }
        }
        return $range;
    }, $fromConfig);

    // NEW: read from SystemSettings textarea (UI)
    $fromUi = [];
    try {
        /** @var \Piwik\Plugins\TrackingSpamPrevention\SystemSettings $settings */
        $settings = \Piwik\Container\StaticContainer::get(\Piwik\Plugins\TrackingSpamPrevention\SystemSettings::class);
        $raw = (string)$settings->iprange_allowlist_raw->getValue();
        if ($raw !== '') {
            foreach (preg_split('/\R+/', $raw) as $line) {
                $cidr = trim($line);
                if ($cidr !== '') {
                    // same normalization as above
                    if (strpos($cidr, '/') === false) {
                        if (strpos($cidr, '.') !== false) {
                            $cidr .= '/32';
                        } elseif (strpos($cidr, ':') !== false) {
                            $cidr .= '/128';
                        }
                    }
                    $fromUi[] = $cidr;
                }
            }
        }
    } catch (\Throwable $e) {
        // ok in CLI/tests where settings container isn't available
    }

    // merge + dedupe, preserve order (config first, then UI)
    $all = array_values(array_unique(array_merge($fromConfig, $fromUi)));

    return $all;
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
