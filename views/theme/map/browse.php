<?php head(); ?>

<div id="pagination">
<?php echo pagination_links(/*
	5, null,null,null,null, uri('map/browse/')
*/	); ?>
</div>

<?php items_filter_form(array('id'=>'search'), uri('map/browse')); ?>

<div id="map-links"></div>

<?php 
	 google_map(700, 700, 'map_browse', array('uri'=>uri('map/browse')));
?>

<div id="permalink"></div>


<?php foot(); ?>