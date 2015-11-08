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

        $post = $_POST;

        // Fill the data to display map.
        $usePost = !empty($post)
            && !empty($post['geolocation'])
            && $post['geolocation']['latitude'] != ''
            && $post['geolocation']['longitude'] != '';
        // There is a good post.
        if ($usePost) {
            $address = html_escape($post['geolocation']['address']);
            $latitude = empty($post['geolocation']['latitude']) ? '' : (double) $post['geolocation']['latitude'];
            $longitude = empty($post['geolocation']['longitude']) ? '' : (double) $post['geolocation']['longitude'];
            $zoom = empty($post['geolocation']['zoom_level']) ? '' : (integer) $post['geolocation']['zoom_level'];
            $mapType = html_escape($post['geolocation']['map_type']);
        }
        // Use the first location.
        elseif ($location) {
            $address = html_escape($location['address']);
            $latitude = (double) $location['latitude'];
            $longitude = (double) $location['longitude'];
            $zoom = (integer) $location['zoom_level'];
            $mapType = html_escape($location['map_type']);
        }
        // Nothing, so initialize the values.
        else {
            $address = $latitude = $longitude = $zoom = $mapType = '';
        }

        // Prepare javascript.
        $options = array();
        $options['form'] = array(
            'id' => 'location_form',
            'posted' => $usePost,
        );
        // This option is kept for future evolution, but set false currently.
        // $options['confirmLocationChange'] = empty($location) ? false : $item->exists();
        $options['confirmLocationChange'] = false;
        if ($location or $usePost) {
            $options['point'] = array(
                'latitude' => $latitude,
                'longitude' => $longitude,
                'zoomLevel' => $zoom,
            );
        }

        $js = sprintf('var anOmekaMapForm = new OmekaMapForm(%s, %s, %s);',
            js_escape('omeka-map-form'), js_escape($center), js_escape($options));
        $js .= "
            jQuery(document).bind('omeka:tabselected', function () {
                anOmekaMapForm.resize();
            });
        ";

        // Prepare the output.
        $html = '<input type="hidden" name="geolocation[latitude]" value="' . $latitude . '" />';
        $html .= '<input type="hidden" name="geolocation[longitude]" value="' . $longitude . '" />';
        $html .= '<input type="hidden" name="geolocation[zoom_level]" value="' . $zoom . '" />';
        $html .= '<input type="hidden" name="geolocation[map_type]" value="' . $mapType . '" />';

        $html .= $view->partial('map/input-partial.php', array(
            'item' => $item,
            'address' => $address,
            'locations' => $locations,
        ));

        $html .= "<script type='text/javascript'>" . $js . "</script>";

        return $html;
    }

    protected function _getCenter()
    {
        return array(
            'latitude' => (double) get_option('geolocation_default_latitude'),
            'longitude' => (double) get_option('geolocation_default_longitude'),
            'zoomLevel' => (double) get_option('geolocation_default_zoom_level'),
        );
    }
}
