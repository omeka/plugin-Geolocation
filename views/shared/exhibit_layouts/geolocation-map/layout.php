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
$sequenceMode = !empty($options['sequence']) && count($locations) > 0;
?>
<script type="text/javascript">
jQuery(window).on('load', function () {
    var geolocation_map = new OmekaMap(
        <?php echo json_encode($divId); ?>,
        <?php echo json_encode($center); ?>,
        <?php echo $this->geolocationMapOptions(); ?>);
    geolocation_map.initMap();
    var map_locations = <?php echo json_encode($locations); ?>;
<?php if ($sequenceMode): ?>
    var layers = [];
    for (var i = 0; i < map_locations.length; i++) {
        var locationData = map_locations[i];
        layers.push(geolocation_map.addLayerFromGeometry(JSON.parse(locationData.geometry_json), {}, locationData.html));
    }
    var currentIndex = 0;
    var total = layers.length;
    var prevBtn = jQuery('#<?php echo $divId; ?>-prev');
    var nextBtn = jQuery('#<?php echo $divId; ?>-next');
    var counter = jQuery('#<?php echo $divId; ?>-counter');
    function goToStep(index) {
        currentIndex = index;
        var layer = layers[currentIndex];
        var geometry = JSON.parse(map_locations[currentIndex].geometry_json);
        if (geometry.type === 'Point') {
            geolocation_map.map.flyTo(layer.getLatLng());
        } else {
            geolocation_map.map.fitBounds(layer.getBounds(), {padding: [25, 25]});
        }
        geolocation_map.map.once('moveend', function () {
            layer.openPopup();
        });
        counter.text((currentIndex + 1) + ' / ' + total);
        prevBtn.prop('disabled', currentIndex === 0);
        nextBtn.prop('disabled', currentIndex === total - 1);
    }
    prevBtn.on('click', function () {
        if (currentIndex > 0) goToStep(currentIndex - 1);
    });
    nextBtn.on('click', function () {
        if (currentIndex < total - 1) goToStep(currentIndex + 1);
    });
    goToStep(0);
<?php else: ?>
    for (var i = 0; i < map_locations.length; i++) {
        var locationData = map_locations[i];
        geolocation_map.addLayerFromGeometry(JSON.parse(locationData.geometry_json), {}, locationData.html);
    }
    geolocation_map.fitLocations();
<?php endif; ?>
});
</script>
<?php if ($sequenceMode): ?>
<div class="geolocation-sequence-nav" aria-label="<?php echo __('Location sequence navigation'); ?>">
    <button id="<?php echo $divId; ?>-prev" class="geolocation-sequence-prev" type="button" disabled><?php echo __('Previous'); ?></button>
    <span id="<?php echo $divId; ?>-counter" class="geolocation-sequence-counter">1 / <?php echo count($locations); ?></span>
    <button id="<?php echo $divId; ?>-next" class="geolocation-sequence-next" type="button"<?php if (count($locations) === 1): ?> disabled<?php endif; ?>><?php echo __('Next'); ?></button>
</div>
<?php endif; ?>
<div id="<?php echo $divId; ?>" class="geolocation-map exhibit-geolocation-map"></div>
