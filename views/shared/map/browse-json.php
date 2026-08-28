<?php
$output = [];
foreach (loop('item') as $item):
    $itemLocations = $locations[$item->id] ?? [];
    $title = metadata($item, 'display_title', ['no_escape' => true]);
    $thumbnailUrl = metadata($item, 'has thumbnail') ? record_image_url($item, 'thumbnail') : '';
    // Configured alt for the thumbnail's file (custom alt, else the site's alt element), used in
    // preference to the item title so it isn't overridden. Same file the thumbnail comes from.
    $fileAlt = ($file = $item->getFile()) ? $file->getAltText() : '';
    $snippet = (string) metadata($item, ['Dublin Core', 'Description'], ['snippet' => 150]);
    $itemUrl = record_url($item, 'show', true);
    foreach ($itemLocations as $location):
        $output[] = [
            'id'           => (int) $location->id,
            'latitude'     => (float) $location->latitude,
            'longitude'    => (float) $location->longitude,
            'zoom_level'   => (int) $location->zoom_level,
            'address'      => $location->address,
            'label'        => $location->label,
            'geometry_json' => $location->geometry_json,
            'title'        => $title,
            'thumbnailUrl' => $thumbnailUrl,
            'fileAlt'      => $fileAlt,
            'snippet'      => $snippet,
            'itemId'       => (int) $item->id,
            'itemUrl'      => $itemUrl,
        ];
    endforeach;
endforeach;
echo json_encode($output);
