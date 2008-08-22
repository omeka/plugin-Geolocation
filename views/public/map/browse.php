<?php head(); ?>
<?php geolocation_scripts(); ?>

<style type="text/css" media="screen">
	#map_browse {
		width: 500px;
		height: 500px;
	}
	
	#map-links li {overflow:hidden; border-bottom:1px dotted #ccc;}
	#map-links li a {float:right; width:50%; text-decoration:none; }
</style>

<div id="primary">

<h1>Browse Items on the Map</h1>
<div class="pagination"><?php echo map_pagination(); ?></div>

<?php 
	 google_map('map_browse', array('uri'=>uri('map/browse'), 'list'=>'map-links'));
?>

<h2>Find An Item on the Map</h2>

<div id="map-links"></div>

</div>

<?php foot(); ?>