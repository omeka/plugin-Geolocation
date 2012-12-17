<?php

class Geolocation_View_Helper_GoogleMap extends Zend_View_Helper_Abstract
{
    
    public function googleMap($divId = 'map', $options = array())
    {
        $ht = '';
        $ht .= '<div id="' . $divId . '" class="map"></div>';
        
        // Load this junk in from the plugin config
        $center = array(
                'latitude'=>  (double) get_option('geolocation_default_latitude'), 
                'longitude'=> (double) get_option('geolocation_default_longitude'), 
                'zoomLevel'=> (double) get_option('geolocation_default_zoom_level'));
        
        // The request parameters get put into the map options
        $params = array();
        if (!isset($options['params'])) {
            $params = array();
        }
        $params = array_merge($params, $_GET);
        
        if ($options['loadKml']) {
            unset($options['loadKml']);
            // This should not be a link to the public side b/c then all the URLs that
            // are generated inside the KML will also link to the public side.
            $options['uri'] = url('geolocation/map.kml');
        }
        
        // Merge in extra parameters from the controller
        if (Zend_Registry::isRegistered('map_params')) {
            $params = array_merge($params, Zend_Registry::get('map_params'));
        }
        
        // We are using KML as the output format
        $options['params'] = $params;
        
        $options = js_escape($options);
        $center = js_escape($center);
        $varDivId = Inflector::variablize($divId);
        $js = '';
        $js .= "var $varDivId" . "OmekaMapBrowse = new OmekaMapBrowse(" . js_escape($divId) .", $center, $options); ";
        $ht .= "<script type='text/javascript'>$js</script>";
        return $ht;
    }
}