<?php
queue_css_file('geolocation-items-map');

$center = array();
if (isset($item)) {
    $radius = isset($params['geolocation-radius']) ? $params['geolocation-radius'] : get_option('geolocation_default_radius');
    $unit = get_option('geolocation_use_metric_distances') ? __('kilometers') :__('miles');
    $title = __('Browse Items around Item "%s" (%d total, %s %s radius)', link_to_item(null, array(), 'show', $item), $totalItems, $radius, $unit);
    $center['latitude'] = (double) $location->latitude;
    $center['longitude'] = (double) $location->longitude;
    $center['zoomLevel'] = (double) get_option('geolocation_default_zoom_level');
}
else {
    $title = __('Browse Items on the Map (%s total)', $totalItems);
}

echo head(array('title' => $title, 'bodyclass' => 'map browse'));
?>

<h1><?php echo $title; ?></h1>

<nav class="items-nav navigation secondary-nav">
    <?php echo public_nav_items(); ?>
</nav>

<?php
echo item_search_filters();
echo pagination_links();
?>

<div id="geolocation-browse">
    <?php echo $this->googleMap('map_browse', array('list' => 'map-links', 'params' => $params), array(), $center); ?>
    <div id="map-links"><h2><?php echo __('Find An Item on the Map'); ?></h2></div>
</div>

<?php echo foot(); ?>
