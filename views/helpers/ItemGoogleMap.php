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
        }

        if (empty($locations)) {
            $html = '<p class="map-notification">'.__('This item has no location info associated with it.').'</p>';
            return;
        }

        // Only set the center of the map if this item actually has a location
        // associated with it.
        foreach ($locations as $location) {
            $center = array();
            $center['latitude'] = $location->latitude;
            $center['longitude'] = $location->longitude;
            $center['zoomLevel'] = $location->zoom_level;
            $center['show'] = true;
            if ($hasBalloonForMarker) {
                $titleLink = link_to_item(metadata($item, array('Dublin Core', 'Title'), array(), $item), array(), 'show', $item);
                $thumbnailLink = !(item_image('thumbnail')) ? '' : link_to_item(item_image('thumbnail',array(), 0, $item), array(), 'show', $item);
                $description = metadata($item, array('Dublin Core', 'Description'), array('snippet'=>150), $item);
                $center['markerHtml'] = '<div class="' . $markerHtmlClassName . '">'
                                      . '<div class="geolocation_balloon_title">' . $titleLink . '</div>'
                                      . '<div class="geolocation_balloon_thumbnail">' . $thumbnailLink . '</div>'
                                      . '<p class="geolocation_balloon_description">' . $description . '</p></div>';
            }
            $options = array();
            $options['mapType'] = get_option('geolocation_map_type');
            $center = js_escape($center);
            $options = js_escape($options);
            $style = "width: $width; height: $height";

            $accessible_markup = get_option('geolocation_accessible_markup');
            if ($accessible_markup) {
                $figcaption = '';
                if (isset($location->latitude) && !empty($location->latitude)) $figcaption .= "<div id='geolocation-latitude'>Latitude: {$location->latitude}</div>";
                if (isset($location->longitude) && !empty($location->latitude)) $figcaption .= "<div id='geolocation-longitude'>Longitude: {$location->longitude}</div>";
                if (isset($location->address) && !empty($location->address)) $figcaption .= "<div id='geolocation-address'>Address: {$location->address}</div>";
                $html = '<figure>';
                $html .= '<div id="' . $divId . '" class="map geolocation-map" style="' . $style . '">';
                $html .= '</div>';
                $html .= '<figcaption class="element-invisible">' . $figcaption . '</figcaption>';
                $html .= '</figure>';
            }
            else {
                $html = '<div id="' . $divId . '" class="map geolocation-map" style="' . $style . '"></div>';
            }
            $js = "var " . Inflector::variablize($divId) . ";";
            $js .= "OmekaMapSingle = new OmekaMapSingle(" . js_escape($divId) . ", $center, $options); ";
            $html .= "<script type='text/javascript'>$js</script>";
        }

         return $html;
    }
}
