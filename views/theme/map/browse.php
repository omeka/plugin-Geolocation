<?php head(); ?>

<script type="text/javascript" charset="utf-8">
//<![CDATA[
	Event.observe(window, 'unload', GUnload);
	
//]]>	
</script>

<?php echo pagination_links(5, null,null,null,null, uri('map/browse/')); ?>

<?php google_map(700, 700, 'map', array('uri'=>'browse')); ?>

<div id="map-links"></div>

<form>
	
</form>



<?php 	
	$item = $items[0];
	
//	map_for_item($item, 400, 400);
?>

	<?php 
//		map_form($item, 600, 600); 
	?>


<?php foot(); ?>