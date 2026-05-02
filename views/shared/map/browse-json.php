<?php
$output = [];
foreach (loop('item') as $item):
    $itemLocations = $locations[$item->id] ?? [];
    $rawTitle = metadata($item, 'display_title', ['no_escape' => true]);
    $thumbnailUrl = metadata($item, 'has thumbnail') ? record_image_url($item, 'thumbnail') : '';
    $snippet = (string) metadata($item, ['Dublin Core', 'Description'], ['snippet' => 150]);
    $itemUrl = record_url($item, 'show', true);
    foreach ($itemLocations as $location):
        $displayTitle = $location->label ? "$rawTitle — {$location->label}" : $rawTitle;
        $output[] = [
            'id'           => (int) $location->id,
            'latitude'     => (float) $location->latitude,
            'longitude'    => (float) $location->longitude,
            'zoom_level'   => (int) $location->zoom_level,
            'address'      => $location->address,
            'label'        => $location->label,
            'title'        => $displayTitle,
            'thumbnailUrl' => $thumbnailUrl,
            'snippet'      => $snippet,
            'itemId'       => (int) $item->id,
            'itemUrl'      => $itemUrl,
        ];
    endforeach;
endforeach;
echo json_encode($output);
