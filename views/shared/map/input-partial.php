<div class="field">
    <div id="location_form" class="two columns alpha">
        <label><?php echo html_escape($label); ?></label>
    </div>
    <div class="inputs five columns omega">
        <input type="text" name="geolocation[address]" id="geolocation_address" value="<?php echo $address; ?>">
        <button type="button" name="geolocation_find_location_by_address" id="geolocation_find_location_by_address"><?php echo __('Find'); ?></button>
    </div>
</div>
<div  id="omeka-map-form"></div>
