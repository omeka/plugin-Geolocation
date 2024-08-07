<?php

class Geolocation_View_Helper_GeolocationMapBrowse extends Zend_View_Helper_Abstract
{
    public function geolocationMapBrowse($divId = 'map', $options = array(), $attrs = array(), $center = array())
    {
        if (!$center) {
            $center = array(
                'latitude'  => (double) get_option('geolocation_default_latitude'),
                'longitude' => (double) get_option('geolocation_default_longitude'),
                'zoomLevel' => (double) get_option('geolocation_default_zoom_level')
            );
        }

        if (!array_key_exists('params', $options)) {
            $options['params'] = array();
        }

        if (!array_key_exists('uri', $options)) {
            // This should not be a link to the public side b/c then all the URLs that
            // are generated inside the KML will also link to the public side.
            $options['uri'] = url('geolocation/map.kml');
        }

        if (!array_key_exists('fitMarkers', $options)) {
            $options['fitMarkers'] = (bool) get_option('geolocation_auto_fit_browse');
        }

        $class = 'map geolocation-map';
        if (isset($attrs['class'])) {
            $class .= ' ' . $attrs['class'];
        }

        $options = $this->view->geolocationMapOptions($options);
        $center = js_escape($center);
        $varDivId = Inflector::variablize($divId);
        $divAttrs = array_merge($attrs, array(
            'id' => $divId,
            'class' => $class,
            'aria-role' => 'region',
            'aria-roledescription' => 'map',
            'aria-label' => 'Geolocation map'
        ));

        $html = '<div ' . tag_attributes($divAttrs) . '></div>';
        $js = "var $varDivId" . "OmekaMapBrowse = new OmekaMapBrowse(" . js_escape($divId) .", $center, $options); ";
        $html .= "<script type='text/javascript'>$js</script>";
        return $html;
    }
}
