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
		$settings = new \Piwik\Plugins\TrackingSpamPrevention\SystemSettings();
		$raw      = $this->settingToCidrs($settings->iprange_allowlist);

		$normalized = array_map(static function ($r) {
			$r = trim((string) $r);
			if ($r === '') {
				return '';
			}
			if (strpos($r, '/') === false) {
				return (strpos($r, ':') !== false) ? ($r . '/128') : ($r . '/32');
			}
			return $r;
		}, $raw);

		return array_values(array_unique(array_filter($normalized, static fn($x) => $x !== '')));
	}

	private function settingToCidrs(\Piwik\Settings\Setting $setting): array
	{
		$val = $setting->getValue();
		if (empty($val) || !is_array($val)) {
			return [];
		}
		$list = [];
		foreach ($val as $row) {
			if (is_array($row) && !empty($row['cidr'])) {
				$list[] = trim((string) $row['cidr']);
			} elseif (is_string($row)) {
				$list[] = trim($row);
			}
		}
		return array_values(array_filter($list, fn($v) => $v !== ''));
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
