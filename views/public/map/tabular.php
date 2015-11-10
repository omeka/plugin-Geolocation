<?php 
queue_css_file('geolocation-items-map');

$title = __('Browse Items on the Map') . ' ' . __('(%s total)', $totalItems);
echo head(array('title' => $title, 'bodyclass' => 'map browse_tabular'));
?>

<h1><?php echo $title; ?></h1>

<nav class="items-nav navigation secondary-nav">
    <?php echo public_nav_items(); ?>
</nav>

<div id="geolocation-tabular">
    <table>
        <tr>
            <th scope="col">Title</th>
            <th scope="col">Longitude</th>
            <th scope="col">Latitude</th>
            <th scope="col">Address</th>
        </tr>
        <?php foreach($this->items as $item): ?>
        <?php $title = metadata($item, array("Dublin Core", "Title")); ?>
        <?php $item_link = link_to_item($title, array(), 'show', $item);  ?>
        <tr>
            <td><?php echo $item_link; ?></td>
            <td><?php echo $item->longitude; ?></td>
            <td><?php echo $item->latitude; ?></td>
            <td><?php echo $item->address; ?></td>
        </tr>    
        <?php endforeach ?>
    </table>
    <p><?php
        $map_url = absolute_url('items/map');
        echo "<a href='{$map_url}'>View as a map</a>"; 
    ?></p>
</div>

<?php echo foot(); ?>
