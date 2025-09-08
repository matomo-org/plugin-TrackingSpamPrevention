<?php

namespace Piwik\Plugins\TrackingSpamPrevention\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\TrackingSpamPrevention\SystemSettings;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

class SystemSettingsIpAllowlistTest extends IntegrationTestCase
{
    public function test_transformCidrsList_filtersEmpty_and_deduplicates()
    {
        /** @var SystemSettings $settings */
        $settings = StaticContainer::get(SystemSettings::class);

        $in = [
            ['cidr' => '10.0.0.0/8'],
            ['cidr' => ''],                        // drop
            ['cidr' => '10.0.0.0/8'],             // duplicate
            ['cidr' => '2001:db8::/32'],
            ['nope' => 'ignored'],                 // ignore
        ];
        $out = $settings->transformCidrsList($in);
        $this->assertSame(['10.0.0.0/8','2001:db8::/32'], $out);
    }

    public function test_save_accepts_valid_single_ip_ipv4_and_ipv6_cidrs()
    {
        /** @var SystemSettings $settings */
        $settings = StaticContainer::get(SystemSettings::class);

        $settings->iprange_allowlist->setValue([
            ['cidr' => '127.0.0.1'],      // single IP
            ['cidr' => '10.0.0.0/8'],     // IPv4 CIDR
            ['cidr' => '2001:db8::/32'],  // IPv6 CIDR
        ]);

        $settings->save();

        $saved = $settings->iprange_allowlist->getValue();

        $this->assertSame([
            ['cidr' => '127.0.0.1'],
            ['cidr' => '10.0.0.0/8'],
            ['cidr' => '2001:db8::/32'],
        ], $saved);
    }

    public function test_save_rejects_invalid_cidr()
    {
        /** @var SystemSettings $settings */
        $settings = StaticContainer::get(SystemSettings::class);

        $settings->iprange_allowlist->setValue([
            ['cidr' => 'not_an_ip'],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TrackingSpamPrevention_InvalidIPOrCIDRExceptionMessage');
        $settings->save();

    }

    public function test_save_ignores_blank_rows()
    {
        /** @var SystemSettings $settings */
        $settings = StaticContainer::get(SystemSettings::class);

        $settings->iprange_allowlist->setValue([
            ['cidr' => '   '],
            ['cidr' => '192.168.1.0/24'],
            ['cidr' => ''],
        ]);

        $settings->save();
        $saved = $settings->iprange_allowlist->getValue();
        $this->assertSame([['cidr' => '192.168.1.0/24']], $saved);
    }
}
