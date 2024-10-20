<?php

return array(

    'trackingspam.iprangeproviders' => Piwik\DI::add(array(
        Piwik\DI::get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Aws'),
        Piwik\DI::get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Azure'),
        Piwik\DI::get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\DigitalOcean'),
        Piwik\DI::get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Gcloud'),
        Piwik\DI::get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Oracle'),
    )),

    'Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges' => Piwik\DI::autowire()
        ->constructorParameter('providers', Piwik\DI::get('trackingspam.iprangeproviders')),

    'Piwik\Plugins\TrackingSpamPrevention\BlockedGeoIp' => Piwik\DI::autowire()
        ->constructor(Piwik\DI::get('trackingspam.block_geoip_organisations')),

    'trackingspam.block_geoip_organisations' => function (\Piwik\Container\Container $c) {
        if ($c->has('ini.TrackingSpamPrevention.block_geoip_organisations')) {
            return $c->get('ini.TrackingSpamPrevention.block_geoip_organisations');
        }
            return [];
    },

    \Piwik\Config::class => Piwik\DI::decorate(function ($previous) {
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
