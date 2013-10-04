<?php
$divId = "geolocation_map_$index";
$center = array(
    'latitude'=>  (double) get_option('geolocation_default_latitude'), 
    'longitude'=> (double) get_option('geolocation_default_longitude'), 
    'zoomLevel'=> (int) get_option('geolocation_default_zoom_level')
);
$locationTable = get_db()->getTable('Location');
$locations = array();
foreach ($attachments as $attachment):
    $item = $attachment->getItem();
    $location = $locationTable->findLocationByItem($item, true);
    if ($location):
        $titleLink = exhibit_builder_link_to_exhibit_item(null, array(), $item);
        $thumbnailAndCaption = $this->exhibitAttachment($attachment, array(), array(), true);
        $html = '<div class="geolocation-balloon">'
              . '<p class="geolocation_marker_title">' . $titleLink . '</p>'
              . $thumbnailAndCaption
              . '</div>';
        $locations[] = array(
            'lat' => $location->latitude,
            'lng' => $location->longitude,
            'html' => $html
        );
    endif;
endforeach;
?>
<script type="text/javascript">
google.maps.event.addDomListener(window, 'load', function () {
    var geolocation_map = new OmekaMap(
        <?php echo json_encode($divId); ?>,
        <?php echo json_encode($center); ?>,
        {}
    );
    geolocation_map.initMap();
    var map_locations = <?php echo json_encode($locations); ?>;
    var map_bounds = new google.maps.LatLngBounds();
    for (var i = 0; i < map_locations.length; i++) {
        var locationData = map_locations[i];
        geolocation_map.addMarker(
            locationData.lat,
            locationData.lng,
            {},
            locationData.html
        );
        map_bounds.extend(new google.maps.LatLng(locationData.lat, locationData.lng));
    }
    if (map_locations.length > 1) {
        geolocation_map.map.fitBounds(map_bounds);
    } else if (map_locations.length = 1) {
        geolocation_map.map.setCenter(new google.maps.LatLng(map_locations[0].lat, map_locations[0].lng));
    }
});
</script>
<div id="<?php echo $divId; ?>" class="exhibit-geolocation-map"></div>
