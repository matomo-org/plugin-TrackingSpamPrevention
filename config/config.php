<?php
return array(

    'Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges' => DI\autowire()
        ->constructor(DI\get('trackingspam.iprangeproviders')),

    'trackingspam.iprangeproviders' => DI\add(array(
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Aws'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Azure'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\DigitalOcean'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Gcloud'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Oracle'),
    )),

    'Piwik\Plugins\TrackingSpamPrevention\BlockedGeoIp' => DI\autowire()
        ->constructor(DI\get('ini.TrackingSpamPrevention.block_geoip_organisations')),


    \Piwik\Config::class => DI\decorate(function ($previous) {
        $tsp = $previous->TrackingSpamPrevention;
        if (empty($tsp['block_geoip_organisations'])) {
            // prevent possible tracking error if this config is removed otherwise
            $tsp['block_geoip_organisations'] = [];
        }
        $previous->TrackingSpamPrevention = $tsp;

        return $previous;
    }),
);
