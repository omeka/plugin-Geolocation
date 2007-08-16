<?php head(); ?>

<script type="text/javascript" charset="utf-8">
//<![CDATA[
	Event.observe(window, 'unload', GUnload);
	
//]]>	
</script>

<?php echo pagination_links(5, null,null,null,null, uri('map/browse/')); ?>

<?php google_map(400, 500, 'map', array('centerOverlay'=>true)); ?>

<?php foot(); ?>