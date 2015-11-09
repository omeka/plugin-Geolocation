<?php

class Geolocation_View_Helper_ItemGoogleMap extends Zend_View_Helper_Abstract
{
    /**
     * Display the map of an item with markers and balloons.
     *
     * @param Item|null $item
     * @param boolean $onlyFirst Display only first location of the item.
     * @param string $width Css width of the block.
     * @param string $height Css height of the block.
     * @param boolean $hasBalloonForMarker
     * @param string $markerHtmlClassName Class name of the marker.
     * @return string Html.
     */
    public function itemGoogleMap($item = null, $onlyFirst = false, $width = '200px', $height = '200px', $hasBalloonForMarker = false, $markerHtmlClassName = 'geolocation_balloon')
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        $divId = "item-map-{$item->id}";
        // First location only.
        if ($onlyFirst) {
            $location = get_db()->getTable('Location')->findLocationsByItem($item, $onlyFirst, true);
            $locations = $location ? array($location) : array();
        }
        // Multiple locations.
        else {
            $locations = get_db()->getTable('Location')->findLocationsByItem($item, $onlyFirst, true);
            $location = reset($locations);
        }

        if (empty($locations)) {
            $html = '<p class="map-notification">'.__('This item has no location info associated with it.').'</p>';
            return;
        }

        // Define the main data.
        $center = array();
        $center['latitude'] = $location->latitude;
        $center['longitude'] = $location->longitude;
        $center['zoomLevel'] = $location->zoom_level;
        $center['show'] = true;
        if ($hasBalloonForMarker) {
            $titleLink = link_to_item(metadata($item, array('Dublin Core', 'Title'), array(), $item), array(), 'show', $item);
            $itemImage = item_image('thumbnail',array(), 0, $item);
            $thumbnailLink = $itemImage
                ? link_to_item($itemImage , array(), 'show', $item)
                : '';
            $description = metadata($item, array('Dublin Core', 'Description'), array('snippet'=>150), $item);
            $center['markerHtml'] = '<div class="' . $markerHtmlClassName . '">'
                . '<div class="geolocation_balloon_title">' . $titleLink . '</div>'
                . '<div class="geolocation_balloon_thumbnail">' . $thumbnailLink . '</div>'
                . '<p class="geolocation_balloon_description">' . $description . '</p></div>';
        }

        $options = array();
        $options['mapType'] = $location->map_type;
        // Here is the styling for the balloon that appears on the map. Values
        // "$[x]" will be replaced via javascript.
        $options['balloon'] = '<div class="geolocation_balloon">'
            . '<div class="geolocation_balloon_address">$[address]</div>'
            . '<div class="geolocation_balloon_description">$[description]</div>'
            . '</div>';

        // Only set the center of the map if this item actually has a location
        // associated with it.
        $points = array();
        foreach ($locations as $location) {
            $point = array();
            $point['address'] = $location->address;
            $point['latitude'] = $location->latitude;
            $point['longitude'] = $location->longitude;
            $point['description'] = $location->description;
            $points[] = $point;
        }

        $accessible_markup = get_option('geolocation_accessible_markup');
        if ($accessible_markup) {
            $html = '<figure>';
            $html .= sprintf('<div id="%s" class="map geolocation-map" style="width: %s; height: %s"></div>',
                $divId, $width, $height);
            if (count($points) == 1) {
                $point = reset($points);
                $figcaption = sprintf('<div class="geolocation-latitude accessible">%s}</div>', __('Latitude: %s', $point['latitude']));
                $figcaption .= sprintf('<div class="geolocation-longitude accessible">%s</div>', __('Longitude: %s', $point['longitude']));
                if (!empty($point['address'])) $figcaption .= sprintf('<div class="geolocation-address accessible">%s</div>', __('Address: %s', $point['address']));
                if (!empty($point['description'])) $figcaption .= sprintf('<div class="geolocation-description accessible">%s</div>', __('Description: %s', $point['description']));
                $html .= '<figcaption class="element-invisible">' . $figcaption . '</figcaption>';
            }
            else {
                foreach ($points as $key => $point) {
                    $figcaption = sprintf('<div class="geolocation-point accessible">%s</div>', __('Point #%d', $key + 1));
                    $figcaption .= sprintf('<div class="geolocation-latitude accessible">%s</div>', __('Latitude: %s', $point['latitude']));
                    $figcaption .= sprintf('<div class="geolocation-longitude accessible">%s</div>', __('Longitude: %s', $point['longitude']));
                    if (!empty($point['address'])) $figcaption .= sprintf('<div class="geolocation-address accessible">%s</div>', __('Address: %s', $point['address']));
                    if (!empty($point['description'])) $figcaption .= sprintf('<div class="geolocation-description accessible">%s</div>', __('Description: %s', $point['description']));
                    $html .= '<figcaption class="element-invisible">' . $figcaption . '</figcaption>';
                }
            }
            $html .= '</figure>';
        }

        // Normal display.
        else {
            $html = sprintf('<div id="%s" class="map geolocation-map" style="width: %s; height: %s"></div>',
                $divId, $width, $height);
        }

        $js = sprintf('var %s; omekaMapSingle = new OmekaMapSingle(%s, %s, %s, %s);',
            Inflector::variablize($divId), js_escape($divId), js_escape($center), js_escape($options), js_escape($points));

        $html .= "<script type='text/javascript'>$js</script>";

         return $html;
    }
}
