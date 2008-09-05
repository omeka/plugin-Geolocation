<?php head(); ?>

<?php geolocation_scripts(); ?>

<div id="primary">

<style type="text/css" media="screen">
    #map_browse{
        width: 650px;
        height: 500px;
    }
    #map_block {
        float:left;
        margin-left: 2%;
        margin-top: 3%;
    }
    #link_block {
        float:left;
        width:20%;
        margin-left: 2%;
        margin-top: 3%;
    }
    #map-links a {
        padding-left: 15px;
        display:block;
    }
    #search_block {
        clear: both;
    }
</style>

<h1>Browse Items on the Map (<?php echo $totalItems; ?> items total)</h1>

<div class="pagination">
    <?php echo pagination_links(); ?>
</div><!-- end pagination -->

<div id="link_block">
    <h2>Find An Item on the Map</h2>
    <div id="map-links"></div><!-- Used by JavaScript -->
</div><!-- end link_block -->

<div id="map_block">
    <?php google_map('map_browse', array('loadKml'=>true, 'list'=>'map-links'));?>
</div><!-- end map_block -->

<div id="search_block">
    <?php echo items_search_form(array('id'=>'search'), $_SERVER['REQUEST_URI']); ?>
</div><!-- end search_block -->

</div><!-- end primary -->

<?php foot(); ?>