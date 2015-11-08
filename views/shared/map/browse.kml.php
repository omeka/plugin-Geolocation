<?php
// A "Folder" groups all locations of one item. It can be set systematically
// even when only the first location of each item is displayed. Else, all
// placemarks can be set at the same level and each point will contain all the
// metadata of the item. This option can be modified inside the theme.
// The main purpose of this choice is related to the possibility to reuse the
// kml data somewhere outside of Omeka.
$byFolder = true;

echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<kml xmlns="http://earth.google.com/kml/2.0">
    <Document>
        <name>Omeka Items KML</name>
        <?php
        // Here is the styling for the balloon that appears on the map. Values
        // "$[x]" will be replaced via javascript.
        ?>
        <Style id="item-info-balloon">
            <BalloonStyle>
                <text><![CDATA[
                    <div class="geolocation_balloon">
                        <div class="geolocation_balloon_title">$[namewithlink]</div>
                        <div class="geolocation_balloon_thumbnail">$[description]</div>
                        <p class="geolocation_balloon_description">$[Snippet]</p>
                    </div>
                ]]></text>
            </BalloonStyle>
        </Style>
        <?php

        if ($byFolder):
            foreach(loop('item') as $item): ?>
            <Folder id="item-<?php echo $item->id; ?>">
                <name><![CDATA[<?php
                    echo metadata($item, array('Dublin Core', 'Title'));
                ?>]]></name>
                <namewithlink><![CDATA[<?php
                    echo link_to_item(metadata($item , array('Dublin Core', 'Title')), array('class' => 'view-item'));
                ?>]]></namewithlink>
                <Snippet maxLines="2"><![CDATA[<?php
                    echo metadata($item, array('Dublin Core', 'Description'), array('snippet' => 150));
                ?>]]></Snippet>
                <description><![CDATA[<?php
                // @since 3/26/08: movies do not display properly on the map in IE6,
                // so can't use display_files(). Description field contains the HTML
                // for displaying the first file (if possible).
                if (metadata($item, 'has thumbnail')):
                    echo link_to_item(item_image('thumbnail', array(), 0, $item), array('class' => 'view-item'));
                endif;
                ?>]]></description>
                <?php
                // One or multiple locations can be displayed by item.
                if (is_object($locations[$item->id])):
                    $location = $locations[$item->id];
                    echo $this->partial('map/browse-placemark-kml.php', array(
                        'item' => $item,
                        'location' => $location,
                        'byFolder' => true,
                    ));
                // Multiple locations.
                else:
                    $itemLocations = $locations[$item->id];
                    foreach ($itemLocations as $key => $location):
                        echo $this->partial('map/browse-placemark-kml.php', array(
                            'item' => $item,
                            'location' => $location,
                            // Return a one based number.
                            'indexLocation' => $key + 1,
                            'countLocations' => count($itemLocations),
                            'byFolder' => true,
                        ));
                    endforeach;
                endif;
                ?>
                </Folder>
            <?php endforeach;

        // All placemarks together, without the item/folder level.
        else:
            foreach(loop('item') as $item):
                // One or multiple locations can be displayed by item.
                if (is_object($locations[$item->id])):
                    $location = $locations[$item->id];
                    echo $this->partial('map/browse-placemark-kml.php', array(
                        'item' => $item,
                        'location' => $location,
                    ));
                // Multiple locations.
                else:
                    $itemLocations = $locations[$item->id];
                    foreach ($itemLocations as $key => $location):
                        echo $this->partial('map/browse-placemark-kml.php', array(
                            'item' => $item,
                            'location' => $location,
                            // Return a one based number.
                            'indexLocation' => $key + 1,
                            'countLocations' => count($itemLocations),
                        ));
                    endforeach;
                endif;
            endforeach;
        endif;
        ?>
    </Document>
</kml>
