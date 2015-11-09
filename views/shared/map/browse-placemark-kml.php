<Placemark id="item-<?php echo $item->id . '-' . (empty($indexLocation) ? '1' : $indexLocation); ?>">
<?php if (empty($byFolder)): ?>
    <name><![CDATA[<?php
    $title = metadata($item, array('Dublin Core', 'Title'));
    echo $title;
    ?>]]></name>
    <namewithlink><![CDATA[<?php echo link_to_item($title, array('class' => 'view-item')); ?>]]></namewithlink>
<?php endif; ?>
    <Snippet maxLines="2"><![CDATA[<?php
if (empty($byFolder)):
    if (isset($countLocations) && $countLocations > 1):
        echo __('[Point %d/%d]', $indexLocation, $countLocations) . ' ';
    endif;
    echo metadata($item, array('Dublin Core', 'Description'), array('snippet' => 150));
    if (!empty($location['description'])): ?>
        <em><?php echo $location['description']; ?></em>
    <?php endif;
else:
    if (isset($indexLocation) && $countLocations > 1):
        echo __('[Point %d/%d]', $indexLocation, $countLocations) . ' ';
        echo metadata($item, array('Dublin Core', 'Description'), array('snippet' => 150));
        if (!empty($location['description'])): ?>
            <em><?php echo $location['description']; ?></em>
        <?php endif;
    endif;
endif;
    ?>]]></Snippet>
<?php if (empty($byFolder)): ?>
    <description><![CDATA[<?php
    // @since 3/26/08: movies do not display properly on the map in IE6,
    // so can't use display_files(). Description field contains the HTML
    // for displaying the first file (if possible).
    if (metadata($item, 'has thumbnail')):
        echo link_to_item(item_image('thumbnail', array(), 0, $item), array('class' => 'view-item'));
    endif;
    ?>]]></description>
<?php endif; ?>
    <Point>
        <coordinates><?php echo $location['longitude']; ?>,<?php echo $location['latitude']; ?></coordinates>
    </Point>
    <?php if (!empty($location['address'])): ?>
    <address><![CDATA[<?php echo $location['address']; ?>]]></address>
    <?php endif; ?>
</Placemark>
