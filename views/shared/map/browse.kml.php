<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<kml xmlns="http://earth.google.com/kml/2.0">
    <Document>
        <name>Omeka Items KML</name>
        <?php /* Here is the styling for the balloon that appears on the map */ ?>
        <Style id="item-info-balloon">
            <BalloonStyle>
                <text><![CDATA[
                    <div class="balloon">
                        <div class="title">$[name]</div>
                        <div class="description">$[Snippet]</div>
                        <div class="body">$[description]</div>
                    </div>
                ]]></text>
            </BalloonStyle>
        </Style>
        <?php
        while(loop_items()):
        $item = get_current_item();
        $location = $locations[$item->id];
        ?>
        <Placemark>
            <name><![CDATA[<?php echo item('Dublin Core', 'Title');
            ?>]]></name>
            <Snippet maxLines="2"><![CDATA[foo<?php
            echo htmlentities(item('Dublin Core', 'Description', array('snippet' => 150)));
            ?>]]></Snippet>    
            <description><![CDATA[<?php 
            // @since 3/26/08: movies do not display properly on the map in IE6, 
            // so can't use display_files(). Description field contains the HTML 
            // for displaying the first file (if possible)
            //echo display_files($item->Files[0]);
            echo thumbnail($item->Files[0]);
            echo link_to_item('View Item');
            ?>]]></description>
            <Point>
                <coordinates><?php echo $location['longitude']; ?>,<?php echo $location['latitude']; ?></coordinates>
            </Point>
            <?php if ($location['address']): ?>
            <address><?php echo htmlentities($location['address']); ?></address>
            <?php endif; ?>
        </Placemark>
        <?php endwhile; ?>
    </Document>
</kml>