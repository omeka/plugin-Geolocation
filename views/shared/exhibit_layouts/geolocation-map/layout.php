<?php
$divId = "geolocation_map_$index";
$center = [
    'latitude' => (float) get_option('geolocation_default_latitude'),
    'longitude' => (float) get_option('geolocation_default_longitude'),
    'zoomLevel' => (int) get_option('geolocation_default_zoom_level'),
];
$locationTable = get_db()->getTable('Location');
$locations = [];
foreach ($attachments as $attachment):
    $item = $attachment->getItem();
    $file = $attachment->getFile();
    $location = $locationTable->findLocationByItem($item, true);
    if ($location):
        $titleLink = exhibit_builder_link_to_exhibit_item(null, [], $item);

        // Manually print just the caption as body when there's no file to avoid
        // double-printing the title link.
        if ($file):
            $body = $this->exhibitAttachment($attachment, [], [], true);
        else:
            $body = $this->exhibitAttachmentCaption($attachment);
        endif;

        $html = '<div class="geolocation_balloon">'
              . '<div class="geolocation_balloon_title">' . $titleLink . '</div>'
              . $body
              . '</div>';
        $locations[] = [
            'lat' => $location->latitude,
            'lng' => $location->longitude,
            'html' => $html,
        ];
    endif;
endforeach;
?>
<script type="text/javascript">
jQuery(window).on('load', function () {
    var geolocation_map = new OmekaMap(
        <?php echo json_encode($divId); ?>,
        <?php echo json_encode($center); ?>,
        <?php echo $this->geolocationMapOptions(); ?>);
    geolocation_map.initMap();
    var map_locations = <?php echo json_encode($locations); ?>;
    for (var i = 0; i < map_locations.length; i++) {
        var locationData = map_locations[i];
        geolocation_map.addMarker(
            [locationData.lat, locationData.lng],
            {},
            locationData.html
        );
    }
    geolocation_map.fitMarkers();
});
</script>
<div id="<?php echo $divId; ?>" class="geolocation-map exhibit-geolocation-map"></div>
