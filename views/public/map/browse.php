<?php head(); ?>

<?php geolocation_scripts(); ?>

<div id="primary">

<style type="text/css" media="screen">
#map_browse { width: 500px; height: 500px;}
#map-links li {overflow:hidden; border-bottom:1px dotted #ccc;}
#map-links li a {float:right; width:50%; text-decoration:none; }
</style>

<h1>Browse Items on the Map (<?php echo $totalItems; ?> items total)</h1>

<div class="pagination">
    <?php echo pagination(); ?>
</div><!-- end pagination -->

<div id="map_block">
    <?php google_map('map_browse', array('loadKml'=>true, 'list'=>'map-links'));?>
</div><!-- end map_block -->

<div id="link_block">
    <h2>Find An Item on the Map</h2>
    <div id="map-links"></div><!-- Used by JavaScript -->
</div><!-- end link_block -->

</div><!-- end primary -->

<?php foot(); ?>