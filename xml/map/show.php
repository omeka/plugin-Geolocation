<?php head(); ?>

<item id="<?php echo $item->id; ?>">
	<?php 
	if($has_location) {
		//Include the location partial
		common('_location', compact('location'), 'map'); 
	}
	?>
</item>