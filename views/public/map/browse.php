<?php 
queue_css_file('geolocation-items-map');

$title = __('Browse Items on the Map') . ' ' . __('(%s total)', $totalItems);
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
    <?php 
    $accessible_markup = get_option('geolocation_accessible_markup'); 
    $tabular_url = absolute_url('map/tabular');
    ?>
    <?php if ($accessible_markup):?>
    <figure aria-describedat="<?php echo $tabular_url;?>"> 
    <?php endif; ?>
    <?php echo $this->googleMap('map_browse', array('list' => 'map-links', 'params' => $params)); ?>
    <div id="map-links"><h2><?php echo __('Find An Item on the Map'); ?></h2></div>
    <?php if ($accessible_markup):?>
    <figcaption class="element-invisible">Map with geographic locations of items. <a href="<?php echo $tabular_url;?>">View as text</a></figcaption>
    </figure>
    <?php endif; ?>
</div>

<?php echo foot(); ?>
