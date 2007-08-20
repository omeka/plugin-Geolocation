<?php head(); ?>

<item id="<?php echo $item->id; ?>">
	<?php //Include the location partial
	common('_location', compact('location'), 'map'); 
	?>
</item>