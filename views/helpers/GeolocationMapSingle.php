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

        $showList = (bool) get_option('geolocation_show_item_list');
        $listId = "$divId-links";

        $points = [];
        foreach ($locations as $loc) {
            $point = [
                'geometry_json' => $loc->geometry_json,
                'label'         => $loc->label,
                'itemTitle'     => $itemTitle,
                'itemId'        => (int) $item->id,
            ];
            // Every location gets a popup (unlabeled ones use the item title, matching the
            // marker's accessible name), so activating a location always opens something.
            $headerText = $loc->label !== '' ? $loc->label : $itemTitle;
            $point['popupHtml'] = '<div class="geolocation-popup">'
                                 . '<div class="geolocation-popup-header">' . html_escape($headerText) . '</div>'
                                 . '</div>';
            $points[] = $point;
        }

        $options = [];
        $options['basemap'] = get_option('geolocation_basemap');
        $options['locations'] = $points;
        if ($showList) {
            // Wires up the keyboard-accessible location list.
            $options['list'] = $listId;
        }
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
        if ($showList) {
            $html .= '<div id="' . html_escape($listId) . '" class="geolocation-item-links" role="group"'
                   . ' aria-label="' . html_escape(__('Locations')) . '"'
                   . ' data-location-string="' . html_escape(__('Location')) . '"></div>';
        }
        // Live region for the popup open/close announcements the map JS emits,
        // useful whether or not the list is shown.
        $html .= $this->view->geolocationSrAlerts($divId);
        $js = "var $varDivId" . "OmekaMapSingle = new OmekaMapSingle(" . js_escape($divId) . ", $center, $options); ";
        $html .= "<script type='text/javascript'>$js</script>";

        return $html;
    }
}
