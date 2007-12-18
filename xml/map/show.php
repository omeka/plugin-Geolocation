<?php 
// the short <?= php syntax interferes with the xml header!!
echo '<?xml version="1.0" encoding="UTF-8"?>'; 
?>

<item id="<?php echo $item->id; ?>">
	<?php 
	if($has_location) {
		//Include the location partial
		include '_location.php';
	}
	?>
</item>