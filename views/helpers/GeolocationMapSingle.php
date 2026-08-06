<?php

class Geolocation_View_Helper_GeolocationMapSingle extends Zend_View_Helper_Abstract
{
    public function geolocationMapSingle($item = null, $width = '200px', $height = '200px')
    {
        $divId = "item-map-{$item->id}";
        $locations = get_db()->getTable('Location')->findBy(['item_id' => $item->id]);

        if (empty($locations)) {
            return '<p class="map-notification">' . __('This item has no location info associated with it.') . '</p>';
        }

        // For single-location items this sets the initial zoom correctly.
        // For multi-location items fitLocations() overrides the center after all points are added.
        $center = [
            'latitude'  => $locations[0]->latitude,
            'longitude' => $locations[0]->longitude,
            'zoomLevel' => $locations[0]->zoom_level,
        ];

        // Passed through as the marker's accessible-name fallback when a location
        // has no label, so an unlabeled marker is never a nameless button.
        $itemTitle = metadata($item, 'display_title', ['no_escape' => true]);

        $points = [];
        foreach ($locations as $loc) {
            $point = [
                'geometry_json' => $loc->geometry_json,
                'label'         => $loc->label,
                'itemTitle'     => $itemTitle,
            ];
            if ($loc->label !== '') {
                $point['popupHtml'] = '<div class="geolocation-popup">'
                                     . '<div class="geolocation-popup-header">' . html_escape($loc->label) . '</div>'
                                     . '</div>';
            }
            $points[] = $point;
        }

        $options = [];
        $options['basemap'] = get_option('geolocation_basemap');
        $options['locations'] = $points;
        $options = $this->view->geolocationMapOptions($options);
        $center = js_escape($center);
        $varDivId = Inflector::variablize($divId);

        $style = "width:$width;height:$height";
        $divAttrs = [
            'id'    => $divId,
            'class' => 'map geolocation-map',
            'style' => $style,
        ];

        $html = '<div ' . tag_attributes($divAttrs) . '></div>';
        $js = "var $varDivId" . "OmekaMapSingle = new OmekaMapSingle(" . js_escape($divId) . ", $center, $options); ";
        $html .= "<script type='text/javascript'>$js</script>";

        return $html;
    }
}
