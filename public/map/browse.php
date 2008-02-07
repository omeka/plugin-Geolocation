<?php head(); ?>

<style type="text/css" media="screen">
	#map_browse {
		width: 500px;
		height: 500px;
	}
</style>

<div id="primary">

<h1>Browse Items on the Map</h1>
<div class="pagination"><?php echo map_pagination(); ?></div>

<?php 
	 google_map('map_browse', array('uri'=>$_SERVER['REQUEST_URI']));
?>

<h2>Find An Item on the Map</h2>

<div id="map-links"></div>

</div>

<?php foot(); ?>