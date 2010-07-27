<?php head(); ?>
<?php echo geolocation_scripts(); ?>

<div id="primary">

<style type="text/css" media="screen">
#map_browse { 
    width: 569px; 
    height: 436px;
}

#map-links ul {
    margin-top: 20px;
}

#map-links li {
    overflow:hidden; 
    border-bottom:1px dotted #ccc;
}

#map-links li a {
    float:right; 
    text-decoration:none; 
}

#map_block { 
    float:right; 
    clear:both; 
}

#map-links {
    float:left;
}

#map-links a {
    display:block;
}

</style>

<h1>Browse Items on the Map (<?php echo $totalItems; ?> total)</h1>

<div class="pagination">
    <?php echo pagination_links(); ?>
</div><!-- end pagination -->

<div id="map_block">
    <?php echo geolocation_google_map('map_browse', array('loadKml'=>true, 'list'=>'map-links'));?>
</div><!-- end map_block -->

<div id="link_block">
    <h2>Find An Item on the Map</h2>
    <div id="map-links"></div><!-- Used by JavaScript -->
</div><!-- end link_block -->

</div><!-- end primary -->

<?php foot(); ?>