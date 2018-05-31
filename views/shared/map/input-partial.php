<?php
$center = js_escape($center);
$options = js_escape($options);
?>

<input type="hidden" name="geolocation[latitude]" value="<?php echo $lat; ?>">
<input type="hidden" name="geolocation[longitude]" value="<?php echo $lng; ?>">
<input type="hidden" name="geolocation[zoom_level]" value="<?php echo $zoom; ?>">
<input type="hidden" name="geolocation[map_type]" value="Leaflet">

<div class="field">
    <div id="location_form" class="two columns alpha">
        <label><?php echo html_escape($label); ?></label>
    </div>
    <div class="inputs five columns omega">
        <input type="text" name="geolocation[address]" id="geolocation_address" value="<?php echo $address; ?>">
        <button type="button" name="geolocation_find_location_by_address" id="geolocation_find_location_by_address"><?php echo __('Find'); ?></button>
    </div>
</div>
<div id="omeka-map-form"></div>

<script type="text/javascript">
var omekaGeolocationForm = new OmekaMapForm('omeka-map-form', <?php echo $center; ?>, <?php echo $options; ?>);
jQuery(document).on('omeka:tabselected', function () {
    omekaGeolocationForm.resize();
});
</script>
