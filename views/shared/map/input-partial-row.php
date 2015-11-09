<?php
// Add a new location.
if (empty($location)) {
    $id = time();
    $latitude = '';
    $longitude = '';
    $zoom_level = get_option('geolocation_default_zoom_level');
    $map_type = get_option('geolocation_default_map_type');
    $address = '';
    $description = '';
}
// Existing location.
else {
    $id = $location->id;
    $latitude = $location->latitude;
    $longitude = $location->longitude;
    $zoom_level = $location->zoom_level;
    $map_type = $location->map_type;
    $address = $location->address;
    $description = $location->description;
}

$baseField = 'locations[' . $id . ']';
$mapTypes = array(
    'roadmap' => __('Roadmap'),
    'satellite' => __('Satellite'),
    'hybrid' => __('Hybrid'),
    'terrain' => __('Terrain'),
);
$zoomLevels = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21);
?>
<tr id="geolocation-location-<?php echo $id; ?>" class="geolocation-location <?php echo $key%2 ? 'odd' : 'even'; ?>">
    <td>
        <button type="button" class="geolocation-display button small green" id="locations-<?php echo $id; ?>-display" name="<?php echo $baseField; ?>[display]" title="<?php echo __('Display this location'); ?>">O</button>
        <button type="button" class="geolocation-remove button small red" id="locations-<?php echo $id; ?>-remove" name="<?php echo $baseField; ?>[remove]" title="<?php echo __('Remove this location'); ?>">X</button>
    </td>
    <td colspan="4">
    <div>
        <span><?php
        echo $this->formText($baseField . '[latitude]',
            $latitude,
            array(
                'placeholder' => __('Latitude'),
                'maxlength' => '15',
                'class' => 'geolocation-latitude',
            ));
        ?></span>
        <span><?php
        echo $this->formText($baseField . '[longitude]',
            $longitude,
            array(
                'placeholder' => __('Longitude'),
                'maxlength' => '15',
                'class' => 'geolocation-longitude',
            ));
        ?></span>
        <span><?php
        echo $this->formSelect($baseField . '[zoom_level]',
            $zoom_level,
            array('class' => 'geolocation-zoom-level'),
            $zoomLevels);
        ?></span>
        <span><?php
        echo $this->formSelect($baseField . '[map_type]',
            $map_type,
            array('class' => 'geolocation-map-type'),
            $mapTypes);
        ?></span>
    </div>
    <div>
        <span><?php
        echo $this->formText($baseField . '[address]',
            $address,
            array(
                'placeholder' => __('Address'),
                'class' => 'geolocation-address',
            ));
        ?></span>
        <span><?php
        echo $this->formText($baseField . '[description]',
            $description,
            array(
                'placeholder' => __('Description'),
                'class' => 'geolocation-description',
            ));
        ?></span>
    </div>
    </td>
</tr>
