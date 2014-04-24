<?php 
queue_css_file('geolocation-items-map');

echo head(array('title' => __('Browse Map'),'bodyid'=>'map','bodyclass' => 'browse'));
?>

<h1><?php echo __('Browse Items on the Map');?> (<?php echo $totalItems; ?> <?php echo __('total');?>)</h1>

<nav class="items-nav navigation secondary-nav">
    <?php echo public_nav_items(); ?>
</nav>

<?php echo pagination_links(); ?>

<div id="primary">

<div id="geolocation-browse">
    <?php echo $this->googleMap('map_browse', array('list' => 'map-links', 'params' => $params)); ?>
    <div id="map-links"><h2><?php echo __('Find An Item on the Map'); ?></h2></div>
</div>

</div><!-- end primary -->

<?php echo foot(); ?>
