<?php
// geolocation-items-map.css is queued globally in GeolocationPlugin::_head().

$title = __('Browse Items on the Map') . ' ' . __('(%s total)', $totalItems);
echo head(['title' => $title, 'bodyclass' => 'map browse']);
?>

<h1><?php echo $title; ?></h1>

<nav class="items-nav navigation secondary-nav">
    <?php echo public_nav_items(); ?>
</nav>

<?php
echo item_search_filters();
echo pagination_links();
?>

<?php $showBrowseList = (bool) get_option('geolocation_show_browse_list'); ?>
<div id="geolocation-browse"<?php echo $showBrowseList ? '' : ' class="no-list"'; ?>>
    <?php
    $mapOptions = ['params' => $params];
    if ($showBrowseList) {
        $mapOptions['list'] = 'map-links';
    }
    echo $this->geolocationMapBrowse('map_browse', $mapOptions);
    ?>
    <?php if ($showBrowseList): ?>
    <div id="map-links" data-location-string="<?php echo __('Location'); ?>"><h2><?php echo __('Items'); ?></h2></div>
    <?php endif; ?>
    <?php echo $this->geolocationSrAlerts('map_browse'); ?>
</div>

<?php echo foot(); ?>
