<?php 
return array(

    'trackingspam.iprangeproviders' => array(
        new \Piwik\Plugins\TrackingSpamPrevention\BlockedIpRanges\VariableRange(['10.10.0.0/21', '200.200.0.0/21'])
    ),

    \Piwik\Config::class => DI\decorate(function ($previous) {
        $tsp = $previous->TrackingSpamPrevention;
        if (empty($tsp['block_geoip_organisations'])) {
            // prevent possible tracking error if this config is removed otherwise
            $tsp['block_geoip_organisations'] = [];
        }
        $previous->TrackingSpamPrevention = $tsp;

        return $previous;
    })
);
