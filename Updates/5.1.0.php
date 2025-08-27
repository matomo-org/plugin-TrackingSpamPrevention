<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Updater;
use Piwik\Updates as PiwikUpdates;
use Piwik\Updater\Migration\Factory as MigrationFactory;



/**
 * Update for version 5.1.0.
 */
class Updates_5_1_0 extends PiwikUpdates
{
    /**
     * @var MigrationFactory
     */
    private $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    /**
     * Return database migrations to be executed in this update.
     *
     * Database migrations should be defined here, instead of in `doUpdate()`, since this method is used
     * in the `core:update` command when displaying the queries an update will run. If you execute
     * migrations directly in `doUpdate()`, they won't be displayed to the user. Migrations will be executed in the
     * order as positioned in the returned array.
     *
     * @param Updater $updater
     * @return Migration\Db[]
     */

    /**
     * Perform the incremental version update.
     *
     * This method should perform all updating logic. If you define queries in the `getMigrations()` method,
     * you must call {@link Updater::executeMigrations()} here.
     *
     * @param Updater $updater
     */
    public function doUpdate(Updater $updater)
    {

        $cfg = \Piwik\Config::getInstance();
        $raw = $cfg->TrackingSpamPrevention['iprange_allowlist'] ?? [];
        if (!is_array($raw)) {
            $raw = [$raw];
        }

        $raw = array_values(array_filter(array_map(static function ($v) {
            return trim((string)$v);
        }, $raw), static function ($v) {
            return $v !== '';
        }));

        if (isset($cfg->TrackingSpamPrevention['iprange_allowlist'])) {
            unset($cfg->TrackingSpamPrevention['iprange_allowlist']);
            $cfg->forceSave();
        }

        if (!empty($raw)) {
            $rows = array_map(static function ($c) {
                return ['cidr' => $c];
            }, $raw);

            $ui = new \Piwik\Settings\Plugin\SystemSetting(
                'iprange_allowlist',
                [],
                \Piwik\Settings\FieldConfig::TYPE_ARRAY,
                'TrackingSpamPrevention'
            );
            $ui->setValue($rows);
            $ui->save();
        }
        
    }

}
