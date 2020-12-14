<?php
return array(

    'trackingspam.iprangeproviders' => DI\add(array(
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Aws'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Azure'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\DigitalOcean'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Gcloud'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Oracle'),
    )),

    'Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges' => DI\autowire()
        ->constructorParameter('providers', DI\get('trackingspam.iprangeproviders')),

    'Piwik\Plugins\TrackingSpamPrevention\BlockedGeoIp' => DI\autowire()
        ->constructor(DI\get('trackingspam.block_geoip_organisations')),

    'trackingspam.block_geoip_organisations' => function (\Psr\Container\ContainerInterface  $c) {
            if ($c->has('ini.TrackingSpamPrevention.block_geoip_organisations')) {
                return $c->get('ini.TrackingSpamPrevention.block_geoip_organisations');
            }
            return [];
    },

    \Piwik\Config::class => DI\decorate(function ($previous) {
        $tsp = $previous->TrackingSpamPrevention;
        if (empty($tsp)) {
            $tsp = array();
        }
        if (empty($tsp['block_geoip_organisations'])) {
            // prevent possible tracking error if this config is removed otherwise
            $tsp['block_geoip_organisations'] = [];
        }
        $previous->TrackingSpamPrevention = $tsp;

        return $previous;
    }),
);
