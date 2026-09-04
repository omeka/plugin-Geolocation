<?php

/**
 * Helper used to preprocess options passed to the Geolocation JS.
 *
 * Specifies mandatory defaults if they're not present in the given options,
 * and outputs them in a format for use in JS.
 */
class Geolocation_View_Helper_GeolocationMapOptions extends Zend_View_Helper_Abstract
{
    public function geolocationMapOptions($options = [])
    {
        if (!array_key_exists('basemap', $options)) {
            $options['basemap'] = get_option('geolocation_basemap');
        }

        // The JS needs to know the default so it can fall back to it when the
        // stored basemap is one the tile provider library no longer defines.
        $options['defaultBasemap'] = GeolocationPlugin::DEFAULT_BASEMAP;

        // Carto and Mapbox each need the site's own credential, passed into the
        // provider URL template. Stadia needs none here: it authorizes by the
        // domain registered with the site's Stadia account, which requires no
        // request parameter at all.
        $credential = null;
        $basemapParts = explode('.', $options['basemap'], 2);
        switch ($basemapParts[0]) {
            case 'CartoDB':
                $credential = trim((string) get_option('geolocation_carto_api_key'));
                $options['basemapOptions']['apikey'] = $credential;
                break;
            case 'MapBox':
                $credential = trim((string) get_option('geolocation_mapbox_access_token'));
                $options['basemapOptions']['accessToken'] = $credential;

                $type = isset($options['mapType']) ? $options['mapType'] : null;
                $options['basemapOptions']['id'] = $this->_getMapboxMapId($type);
                break;
        }

        // Without its credential neither provider gives a usable map: Carto
        // watermarks the tiles and Mapbox serves none at all. Carto's watermark
        // still loads, so the browser cannot detect it, and Mapbox's provider is
        // defined so the fallback in map.js never fires — this is the only place
        // either problem is visible. Substitute the default, leaving the stored
        // setting alone so adding the credential restores the admin's choice.
        if ($credential === '') {
            $options['basemap'] = GeolocationPlugin::DEFAULT_BASEMAP;
            $options['basemapOptions'] = [];
        }

        if (!array_key_exists('cluster', $options)) {
            $options['cluster'] = (bool) get_option('geolocation_cluster');
        }

        $customMap = $options['custom_map'] = json_decode((string) get_option('geolocation_custom_map'), true);
        if (isset($customMap['attribution'])) {
            $customMap['attribution'] = html_escape($customMap['attribution']);
        }
        $options['custom_map'] = $customMap;

        $options['strings'] = [
            'fitAllLocations'     => __('Fit all locations'),
            'unlabeledLocation'   => __('Map location'),
            'label'               => __('Label'),
            'editLocations'       => __('Edit locations'),
            'noLocationsToEdit'   => __('No locations to edit'),
            'deleteLocations'     => __('Delete locations'),
            'noLocationsToDelete' => __('No locations to delete'),
        ];

        return js_escape($options);
    }

    private function _getMapboxMapId($mapType)
    {
        switch ($mapType) {
            case 'roadmap':
                return 'mapbox/streets-v11';
            case 'satellite':
                return 'mapbox/satellite-v9';
            case 'hybrid':
                return 'mapbox/satellite-streets-v11';
            case 'terrain':
                return 'mapbox/outdoors-v11';
            default:
                // empty case, fallthrough
        }

        $mapId = get_option('geolocation_mapbox_map_id');
        if (!$mapId) {
            $mapId = 'mapbox/streets-v11';
        }
        return $mapId;
    }
}
