<?php head(); ?>
<?php js('search'); ?>

<?php common('archive-nav'); ?>

<style type="text/css" media="screen">
	#map_browse{
		width: 500px;
		height: 500px;
	}

	#search_block {
		float:left;
		width:20%;
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
		
</style>


<div id="pagination">
<?php echo pagination_links(
	5, null,null,null,null, uri('items/map/') ); ?>
</div>

<div id="search_block">
<?php items_search_form(array('id'=>'search'), $_SERVER['REQUEST_URI']); ?>
</div>

<div id="map_block">
<?php 
	 google_map('map_browse', array('uri'=>$_SERVER['REQUEST_URI']));
?>
</div>

<div id="link_block">

<h2>Find An Item on the Map</h2>

<div id="map-links"></div>

</div>



<?php foot(); ?>