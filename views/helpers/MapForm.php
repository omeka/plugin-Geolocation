<?php
/**
 * Helper to display a Google Map Form.
 */
class Geolocation_View_Helper_MapForm extends Zend_View_Helper_Abstract
{

    /**
     * Returns the form code for geographically searching for items.
     *
     * @param Item $item
     * @return string Html string.
     */
    public function mapForm($item)
    {
        $view = $this->view;
        $db = get_db();

        $center = $this->_getCenter();
        $center['show'] = false;

        $locations = $db->getTable('Location')->findLocationsByItem($item);
        // The first location is used to prepare the map (center, zoom, type).
        $location = reset($locations);

        $latitude = $longitude = $address = $description = '';
        $zoomLevel = (integer) get_option('geolocation_default_zoom_level');
        $mapType = get_option('geolocation_default_map_type');

        // Prepare javascript.
        $options = array();
        $options['form'] = array(
            'id' => 'location_form',
        );
        $options['mapType'] = $mapType;

        // This option is kept for future evolution, but set false currently.
        // $options['confirmLocationChange'] = empty($location) ? false : $item->exists();
        $options['confirmLocationChange'] = false;

        // Prepare the output.
        $html = '<input type="hidden" name="geolocation[latitude]" value="' . $latitude . '" />';
        $html .= '<input type="hidden" name="geolocation[longitude]" value="' . $longitude . '" />';
        $html .= '<input type="hidden" name="geolocation[zoom_level]" value="' . $zoomLevel . '" />';
        $html .= '<input type="hidden" name="geolocation[map_type]" value="' . $mapType . '" />';
        $html .= '<input type="hidden" name="geolocation[description]" value="' . $description . '" />';

        $html .= $view->partial('map/input-partial.php', array(
            'item' => $item,
            'address' => $address,
            'locations' => $locations,
        ));

        $js = sprintf('var anOmekaMapForm = new OmekaMapForm(%s, %s, %s);',
            js_escape('omeka-map-form'), js_escape($center), js_escape($options));
        $js .= "
            jQuery(document).bind('omeka:tabselected', function () {
                anOmekaMapForm.displayLocations();
            });
        ";

        $html .= "<script type='text/javascript'>" . $js . "</script>";

        return $html;
    }

    protected function _getCenter()
    {
        return array(
            'latitude' => (double) get_option('geolocation_default_latitude'),
            'longitude' => (double) get_option('geolocation_default_longitude'),
            'zoomLevel' => (integer) get_option('geolocation_default_zoom_level'),
        );
    }
}
