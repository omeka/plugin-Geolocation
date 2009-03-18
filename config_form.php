<div class="field">
	<label for="per_page">Number of Locations Per Page:</label>
	<div class="inputs">
	<input type="text" class="textinput"  name="per_page" size="4" value="<?php echo get_option('geolocation_per_page'); ?>" id="per_page" />
	</div>
</div>

<div class="field">
	<label for="default_latitude">Default Latitude:</label>
	<div class="inputs">
		<input type="text" class="textinput" name="default_latitude" size="8" value="<?php echo get_option('geolocation_default_latitude'); ?>" id="default_latitude" />
	</div>
</div>

<div class="field">
	<label for="default_longitude">Default Longitude:</label>
	<div class="inputs">
		<input type="text" class="textinput"  name="default_longitude" size="8" value="<?php echo get_option('geolocation_default_longitude'); ?>" id="default_longitude" />
	</div>
</div>

<div class="field">
	<label for="default_zoomlevel">Default Zoom Level:</label>
	<div class="inputs">
		<input type="text" class="textinput"  name="default_zoomlevel" size="3" value="<?php echo get_option('geolocation_default_zoom_level'); ?>" id="default_zoomlevel" />
	</div>
</div>

<div class="field">
	<label for="map_key">Google Maps API Key:</label>
	<div class="inputs">
		<input type="text" class="textinput"  name="map_key" size="60" value="<?php echo get_option('geolocation_gmaps_key'); ?>" id="map_key" />
	</div>
</div>