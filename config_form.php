<div class="field">
    <div class="two columns alpha">
        <label for="per_page">Number of Locations Per Page:</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">The number of locations displayed per page when browsing the map. (Maximum is <?php echo GEOLOCATION_MAX_LOCATIONS_PER_PAGE; ?>).</p>
        <div class="input-block">        
        <input type="text" class="textinput"  name="per_page" size="4" value="<?php echo get_option('geolocation_per_page'); ?>" id="per_page" />        
        </div>
    </div>
</div>


<div class="field">
    <div class="two columns alpha">
        <label for="default_latitude">Default Latitude</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">Latitude of the map's initial center point, in degrees. Must be between -90 and 90.</p>
        <div class="input-block">        
            <input type="text" class="textinput" name="default_latitude" size="8" value="<?php echo get_option('geolocation_default_latitude'); ?>" id="default_latitude" />        
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="default_longitude">Default Longitude</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">Longitude of the map's initial center point, in degrees. Must be between -180 and 180.</p>
        <div class="input-block">        
            <input type="text" class="textinput"  name="default_longitude" size="8" value="<?php echo get_option('geolocation_default_longitude'); ?>" id="default_longitude" />        
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="default_zoomlevel">Default Zoom Level</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">An integer greater than or equal to 0, where 0 represents the most zoomed out scale.</p>
        <div class="input-block">        
            <input type="text" class="textinput"  name="default_zoomlevel" size="4" value="<?php echo get_option('geolocation_default_zoom_level'); ?>" id="default_zoomlevel" />        
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="item_map_width">Width for Item Map</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">The width of the map displayed on your items/show page. If left blank, the default width of 100% will be used.</p>
        <div class="input-block">        
            <input type="text" class="textinput"  name="item_map_width" size="8" value="<?php echo get_option('geolocation_item_map_width'); ?>" id="item_map_width" />        
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="item_map_height">Height for Item Map</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">The height of the map displayed on your items/show page. If left blank, the default height of 300px will be used.</p>
        <div class="input-block">        
            <input type="text" class="textinput"  name="item_map_height" size="8" value="<?php echo get_option('geolocation_item_map_height'); ?>" id="item_map_height" />        
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="geolocation_link_to_nav">Add Link to Map on Items/Browse Navigation</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">Add a link to the items map on all the items/browse pages.</p>
        <div class="input-block">        
        <?php echo get_view()->formCheckbox('geolocation_link_to_nav', true, 
         array('checked'=>(boolean)get_option('geolocation_link_to_nav'))); ?>        
        </div>
    </div>
</div>


<div class="field">
    <div class="two columns alpha">
        <label for="geolocation_add_map_to_contribution_form">Add Map To Contribution Form</label>    
    </div>    
    <div class="inputs five columns omega">
        <p class="explanation">If the Contribution plugin is installed and activated, Geolocation  will add a geolocation map field to the contribution form to associate a location to a contributed item.</p>
        <div class="input-block">        
        <?php echo get_view()->formCheckbox('geolocation_add_map_to_contribution_form', true, 
         array('checked'=>(boolean)get_option('geolocation_add_map_to_contribution_form'))); ?>        
        </div>
    </div>
</div>
