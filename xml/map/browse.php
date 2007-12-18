<?php 
// the short <?= php syntax interferes with the xml header!!
echo '<?xml version="1.0" encoding="UTF-8"?>'; 
?>

<items>
	<?php foreach ($items as $key => $item): ?>
		<item id="<?php echo $item->id ?>">
		<?php //common('_item', compact('item'), 'items'); ?>
		
		<title><?php echo htmlspecialchars($item->title); ?></title>
		
		<?php 
				$location = $locations[$item->id];
		?>
		<?php include '_location.php'; ?>
		
		<thumbnail><![CDATA[
			<?php thumbnail($item); ?>
		]]></thumbnail>	
		
		<short_description><?php echo snippet($item->description, 0, 250); ?></short_description>
		
		<link_to_item><![CDATA[
			<?php link_to_item($item); ?>
		]]></link_to_item>
		
		</item>
	<?php endforeach ?>

</items>