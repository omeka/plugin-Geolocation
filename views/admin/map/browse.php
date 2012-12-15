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
    
    $title = "Browse Items on the Map (" . html_escape($totalItems) ." total)";

?>

<?php echo head(array('title'=>$title)); ?>

<div id="geolocation-browse">
	<div class="pagination">
	    <?php echo pagination_links(); ?>
	</div><!-- end pagination -->

	<div id="link_block">
	    <h2>Find An Item on the Map</h2>
	    <div id="map-links"></div><!-- Used by JavaScript -->
	</div><!-- end link_block -->

	<div id="map_block">
	    <?php echo $this->googleMap('map_browse', array('loadKml'=>true, 'list'=>'map-links'));?>
	</div><!-- end map_block -->

	<div id="search_block">
	    <?php echo items_search_form(array('id'=>'search'), $_SERVER['REQUEST_URI']); ?>
	</div><!-- end search_block -->
</div>


<?php foot(); ?>