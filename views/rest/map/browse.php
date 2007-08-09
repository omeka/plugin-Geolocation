<?php head(); ?>

<items>
	<?php foreach ($items as $key => $item): ?>
		<item id="<?php echo $item->id ?>">
		<?php common('_item', compact('item'), 'items'); ?>
		
		<location>
			<?php 
				$location = $locations[$item->id];
			?>
			<latitude><?php echo $location['latitude']; ?></latitude>
			<longitude><?php echo $location['longitude']; ?></longitude>
			<zoom_level><?php echo $location['zoom_level']; ?></zoom_level>
			<zipcode><?php echo $location['zipcode']; ?></zipcode>
			<address><?php echo $location['address']; ?></address>
		</location>
		
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