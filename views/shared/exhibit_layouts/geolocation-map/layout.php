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
        // Prefer the file's configured alt, falling back to the item title (matching
        // the browse map); file_image otherwise leaves alt blank.
        $alt = $file->getAltText() ?: $title;
        $thumbnail = exhibit_builder_link_to_exhibit_item(file_image('square_thumbnail', ['alt' => $alt], $file), [], $item);
    else:
        $thumbnail = '';
    endif;
    // The curator's caption takes the description's place after the title, so exhibit
    // popups follow the same order as the browse map's.
    $caption = $this->exhibitAttachmentCaption($attachment);

    $itemLocations = $locationTable->findBy(['item_id' => $item->id]);
    foreach ($itemLocations as $location):
        $header = $location->label !== ''
                ? '<div class="geolocation-popup-header">' . html_escape($location->label) . '</div>'
                : '';
        $html = '<div class="geolocation-popup">'
              . $header
              . $thumbnail
              . '<div class="geolocation-popup-title">' . $titleLink . '</div>'
              . $caption
              . '</div>';
        $locations[] = [
            'geometry_json' => $location->geometry_json,
            'html' => $html,
        ];
    endforeach;
endforeach;
$sequenceMode = !empty($options['sequence']) && count($locations) > 0;
?>
<?php if ($sequenceMode): ?>
<div class="geolocation-sequence-nav" aria-label="<?php echo __('Location sequence navigation'); ?>">
    <button id="<?php echo $divId; ?>-prev" class="geolocation-sequence-prev" type="button" disabled><?php echo __('Previous'); ?></button>
    <span id="<?php echo $divId; ?>-counter" class="geolocation-sequence-counter">1 / <?php echo count($locations); ?></span>
    <button id="<?php echo $divId; ?>-next" class="geolocation-sequence-next" type="button"<?php if (count($locations) === 1): ?> disabled<?php endif; ?>><?php echo __('Next'); ?></button>
</div>
<?php endif; ?>
<div id="<?php echo $divId; ?>" class="geolocation-map exhibit-geolocation-map"
     data-center="<?php echo html_escape(json_encode($center)); ?>"
     data-options="<?php echo html_escape($this->geolocationMapOptions()); ?>"
     data-locations="<?php echo html_escape(json_encode($locations)); ?>"<?php if ($sequenceMode): ?>
     data-sequence="1"<?php endif; ?>></div>
