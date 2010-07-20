<div class="field">
	<label for="per_page">Number of Locations Per Page:</label>
	<div class="inputs">
	<input type="text" class="textinput"  name="per_page" size="4" value="<?php echo get_option('geolocation_per_page'); ?>" id="per_page" />
	<p class="explanation">The number of locations displayed per page.</p>
	</div>
</div>

<div class="field">
	<label for="default_latitude">Default Latitude:</label>
	<div class="inputs">
		<input type="text" class="textinput" name="default_latitude" size="8" value="<?php echo get_option('geolocation_default_latitude'); ?>" id="default_latitude" />
	    <p class="explanation">A number between -90 and 90.</p>
	</div>
</div>

<div class="field">
	<label for="default_longitude">Default Longitude:</label>
	<div class="inputs">
		<input type="text" class="textinput"  name="default_longitude" size="8" value="<?php echo get_option('geolocation_default_longitude'); ?>" id="default_longitude" />
        <p class="explanation">A number between -180 and 180.</p>
	</div>
</div>

<div class="field">
	<label for="default_zoomlevel">Default Zoom Level:</label>
	<div class="inputs">
		<input type="text" class="textinput"  name="default_zoomlevel" size="3" value="<?php echo get_option('geolocation_default_zoom_level'); ?>" id="default_zoomlevel" />
	    <p class="explanation">An integer greater than or equal to 0, where 0 represents the most zoomed out scale.</p>
	</div>
</div>

<div class="field">
    <label for="add_geolocation_field_to_contribution_form">Add Geolocation Field To Contribution Form:</label>
    <?php echo __v()->formCheckbox('add_geolocation_field_to_contribution_form', true, 
     array('checked'=>(boolean)get_option('add_geolocation_field_to_contribution_form'))); ?>
     <p class="explanation">If the Contribution plugin is installed and activated, Geolocation  will add a geolocation field to the contribution form to associate a geolocation to a contributed item.</p>
</div>