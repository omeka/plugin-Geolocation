<?php
$output = [];
foreach (loop('item') as $item):
    $itemLocations = $locations[$item->id] ?? [];
    $rawTitle = metadata($item, 'display_title', ['no_escape' => true]);
    $thumbnail = metadata($item, 'has thumbnail')
        ? link_to_item(item_image('thumbnail', ['alt' => '']), ['class' => 'view-item'])
        : '';
    $snippet = (string) metadata($item, ['Dublin Core', 'Description'], ['snippet' => 150]);
    $itemUrl = record_url($item, 'show', true);
    foreach ($itemLocations as $location):
        $displayTitle = $location->label ? "$rawTitle — {$location->label}" : $rawTitle;
        $titleWithLink = link_to_item(html_escape($displayTitle), ['class' => 'view-item']);

        $markerHtml = '<div class="geolocation_balloon">'
            . '<div class="geolocation_balloon_title">' . $titleWithLink . '</div>'
            . ($thumbnail ? '<div class="geolocation_balloon_thumbnail">' . $thumbnail . '</div>' : '')
            . ($snippet !== '' ? '<p class="geolocation_balloon_description">' . $snippet . '</p>' : '')
            . '</div>';

        $output[] = [
            'id'         => (int) $location->id,
            'latitude'   => (float) $location->latitude,
            'longitude'  => (float) $location->longitude,
            'zoom_level' => (int) $location->zoom_level,
            'address'    => $location->address,
            'label'      => $location->label,
            'title'      => $displayTitle,
            'markerHtml' => $markerHtml,
            'itemId'     => (int) $item->id,
            'itemUrl'    => $itemUrl,
        ];
    endforeach;
endforeach;
echo json_encode($output);
