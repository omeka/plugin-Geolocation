<?php

class Geolocation_View_Helper_GeolocationMapOptions extends Zend_View_Helper_Abstract
{
    public function geolocationMapOptions($options = array())
    {
        if (!array_key_exists('basemap', $options)) {
            $options['basemap'] = get_option('geolocation_basemap');
        }

        if ($options['basemap'] === 'MapBox') {
            $options['basemapOptions']['accessToken'] = get_option('geolocation_mapbox_access_token');
            $mapId = get_option('geolocation_mapbox_map_id');
            if (!$mapId) {
                $mapId = 'mapbox.streets';
            }
            $options['basemapOptions']['id'] = $mapId;
        }

        return js_escape($options);
    }
}
