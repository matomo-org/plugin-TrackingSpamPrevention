<?php
return array(

    'Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges' => DI\autowire()
        ->constructor(DI\get('trackingspam.iprangeproviders')),

    'trackingspam.iprangeproviders' => array(
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Aws'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Azure'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\DigitalOcean'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Gcloud'),
        DI\get('Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\Oracle'),
    ),

    'Piwik\Plugins\TrackingSpamPrevention\BlockedGeoIp' => DI\autowire()
        ->constructor(DI\get('trackingspam.geoipmatchproviders')),

    'trackingspam.geoipmatchproviders' => array(
        'alicloud', 'alibaba cloud'
    )
);
