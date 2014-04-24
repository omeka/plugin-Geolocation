<?php

class Geolocation_View_Helper_GoogleMap extends Zend_View_Helper_Abstract
{
    public function googleMap($divId = 'map', $options = array())
    {
        // Load this junk in from the plugin config
        $center = array(
                'latitude'=>  (double) get_option('geolocation_default_latitude'), 
                'longitude'=> (double) get_option('geolocation_default_longitude'), 
                'zoomLevel'=> (double) get_option('geolocation_default_zoom_level'));
        
        // The request parameters get put into the map options
        if (!isset($options['params'])) {
            $options['params'] = array();
        }

        if (!array_key_exists('uri', $options)) {
            // This should not be a link to the public side b/c then all the URLs that
            // are generated inside the KML will also link to the public side.
            $options['uri'] = url('geolocation/map.kml');
        }

        if (!array_key_exists('mapType', $options)) {
            $options['mapType'] = get_option('geolocation_map_type');
        }
        
        $options = js_escape($options);
        $center = js_escape($center);
        $varDivId = Inflector::variablize($divId);

        $html = '<div id="' . $divId . '" class="map geolocation-map"></div>';
        $js = "var $varDivId" . "OmekaMapBrowse = new OmekaMapBrowse(" . js_escape($divId) .", $center, $options); ";
        $html .= "<script type='text/javascript'>$js</script>";
        return $html;
    }
}
