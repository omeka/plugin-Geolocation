<?php head(); ?>
<style type="text/css" media="screen">
	#map-links {
		float:right;
		width:45%;
		padding-left: 25px;
	}

	#map_browse{
		float:left;
		width: 50%;
		height: 400px;
	}

</style>


<div id="pagination">
<?php echo pagination_links(
	5, null,null,null,null, uri('map/browse/') ); ?>
</div>

<h2 id="search-header" class="close">Search Items</h2>
<?php items_filter_form(array('id'=>'search'), $_SERVER['REQUEST_URI']); ?>

<div id="map-links"></div>

<?php 
	 google_map('map_browse', array('uri'=>$_SERVER['REQUEST_URI']));
?>



<?php foot(); ?>