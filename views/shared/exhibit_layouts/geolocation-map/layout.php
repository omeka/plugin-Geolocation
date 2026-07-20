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
    $title = metadata($item, 'display_title', ['no_escape' => true]);
    $titleLink = exhibit_builder_link_to_exhibit_item(null, [], $item);

    if ($file):
        $body = $this->exhibitAttachment($attachment, [], [], true);
    else:
        $body = $this->exhibitAttachmentCaption($attachment);
    endif;

    $itemLocations = $locationTable->findBy(['item_id' => $item->id]);
    foreach ($itemLocations as $location):
        $headerText = $location->label ? html_escape($location->label) : html_escape($title);
        $html = '<div class="geolocation-popup">'
              . '<div class="geolocation-popup-header">' . $headerText . '</div>'
              . $body
              . '<div class="geolocation-popup-title">' . $titleLink . '</div>'
              . '</div>';
        $locations[] = [
            'geometry_json' => $location->geometry_json,
            'html' => $html,
        ];
    endforeach;
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
        geolocation_map.addLayerFromGeometry(JSON.parse(locationData.geometry_json), {}, locationData.html);
    }
    geolocation_map.fitLocations();
});
</script>
<div id="<?php echo $divId; ?>" class="geolocation-map exhibit-geolocation-map"></div>
