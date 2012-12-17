<?php 
queue_js_url("http://maps.google.com/maps/api/js?sensor=false");
queue_js_file('map');


$css = "
            #map_browse {
                width: 569px;
                height: 436px;
        		float:left;
        		border:1px solid #ccc; border-width: 1px 1px 1px 0;
            }
        	.balloon {width:400px !important; font-size:1.2em;}
        	.balloon .title {font-weight:bold;margin-bottom:1.5em;}
        	.balloon .title, .balloon .description {float:left; width: 220px;margin-bottom:1.5em;}
        	.balloon img {float:right;display:block;}
        	.balloon .view-item {display:block; float:left; clear:left; font-weight:bold; text-decoration:none;}
        	#map-links ul { margin: 0;  float:left; padding:18px 18px 18px 27px; border:1px solid #ccc; border-width: 1px 0 1px 1px;width: 191px; height: 400px; overflow-y:auto; list-style:square;}
            #map-links a {
                display:block;
            }
            #search_block {
                clear: both;
            }";
queue_css_string($css);

echo head(array('title' => __('Browse Map'),'bodyid'=>'map','bodyclass' => 'browse')); ?>

<div id="primary">

<h1>Browse Items on the Map (<?php echo $totalItems; ?> total)</h1>

<nav class="items-nav navigation" id="secondary-nav">
    <?php echo public_nav_items(); ?>
</nav>

<div class="pagination">
    <?php echo pagination_links(); ?>
</div><!-- end pagination -->

<div id="map-block">
    <?php echo $this->googleMap('map_browse', array('loadKml'=>true, 'list'=>'map-links'));?>
</div><!-- end map_block -->

<div id="link_block">
    <div id="map-links"></div><!-- Used by JavaScript -->
</div><!-- end link_block -->

</div><!-- end primary -->

<?php echo foot(); ?>