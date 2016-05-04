<fieldset id="fieldset-geolocation-general">
    <legend><?php echo __('General Settings'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_default_latitude',
                __('Default Latitude')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __("Latitude of the map's initial center point, in degrees. Must be between -90 and 90."); ?>
            </p>
            <?php echo $this->formText('geolocation_default_latitude', get_option('geolocation_default_latitude')); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_default_longitude',
                __('Default Longitude')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __("Longitude of the map's initial center point, in degrees. Must be between -180 and 180.");?>
            </p>
            <?php echo $this->formText('geolocation_default_longitude', get_option('geolocation_default_longitude')); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_default_zoom_level',
                __('Default Zoom Level')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('An integer greater than or equal to 0, where 0 represents the most zoomed out scale.'); ?>
            </p>
            <?php echo $this->formText('geolocation_default_zoom_level', get_option('geolocation_default_zoom_level')); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_default_map_type',
                __('Map Type')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('The type of map to display'); ?>
            </p>
            <?php
            echo $this->formSelect('geolocation_default_map_type', get_option('geolocation_default_map_type'),
                array(), array(
                    'roadmap' => __('Roadmap'),
                    'satellite' => __('Satellite'),
                    'hybrid' =>__('Hybrid'),
                    'terrain' => __('Terrain'),
            ));
            ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="api_key"><?php echo __('API Key'); ?></label>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation"><?php echo __('Google API key for this project.'); ?></p>
            <?php echo $this->formText('api_key', get_option('geolocation_api_key')); ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-geolocation-browse">
    <legend><?php echo __('Browse Map Settings'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_per_page',
                __('Number of Locations Per Page')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('The number of locations displayed per page when browsing the map.'); ?>
            </p>
            <?php echo $this->formText('geolocation_per_page', get_option('geolocation_per_page')); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_auto_fit_browse',
                __('Auto-fit to Locations')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('If checked, the default location and zoom settings '
                    . 'will be ignored on the browse map. Instead, the map will '
                    . 'automatically pan and zoom to fit the locations displayed '
                    . 'on each page.');
                ?>
            </p>
            <?php
            echo $this->formCheckbox('geolocation_auto_fit_browse', true,
                array('checked' => (boolean) get_option('geolocation_auto_fit_browse')));
            ?>
        </div>
    </div>

    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_browse_append_search',
                __('Append Search to Map')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('If checked, the block advanced search will be appended to the view "map/browse", like in the admin interface.'); ?>
            </p>
            <?php
            echo $this->formCheckbox('geolocation_browse_append_search', true,
                array('checked' => (boolean) get_option('geolocation_browse_append_search')));
            ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_default_radius',
                __('Default Radius')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('The size of the default radius to use on the items advanced search page. See below for whether to measure in miles or kilometers.'); ?>
            </p>
            <?php echo $this->formText('geolocation_default_radius', get_option('geolocation_default_radius')); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_use_metric_distances',
                __('Use metric distances')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('Use metric distances in proximity search.'); ?>
            </p>
            <?php
            echo $this->formCheckbox('geolocation_use_metric_distances', true,
                array('checked' => (boolean) get_option('geolocation_use_metric_distances')));
            ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-geolocation-item-map">
    <legend><?php echo __('Item Map Settings'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_item_map_width',
                __('Width for Item Map')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('The width of the map displayed on your items/show page. If left blank, the default width of 100% will be used.'); ?>
            </p>
            <?php echo $this->formText('geolocation_item_map_width', get_option('geolocation_item_map_width')); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_item_map_height',
                __('Height for Item Map')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('The height of the map displayed on your items/show page. If left blank, the default height of 300px will be used.'); ?>
            </p>
            <?php echo $this->formText('geolocation_item_map_height', get_option('geolocation_item_map_height')); ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-geolocation-integration">
    <legend><?php echo __('Map Integration'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_link_to_nav',
                __('Add Link to Map on Items/Browse Navigation')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('Add a link to the items map on all the items/browse pages.'); ?>
            </p>
            <?php
            echo $this->formCheckbox('geolocation_link_to_nav', true,
                array('checked' => (boolean) get_option('geolocation_link_to_nav')));
            ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_add_map_to_contribution_form',
                __('Add Map To Contribution Form')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('If the Contribution plugin is installed and activated, Geolocation  will add a geolocation map field to the contribution form to associate a location to a contributed item.'); ?>
            </p>
            <?php
            echo $this->formCheckbox('geolocation_add_map_to_contribution_form', true,
                array('checked' => (boolean) get_option('geolocation_add_map_to_contribution_form')));
            ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-geolocation-accessibilty">
    <legend><?php echo __('Accessibility'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('geolocation_accessible_markup',
                __('Enable alternate format')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('Provide accessible markup and a link to a tabular version of the map content.'); ?>
            </p>
            <?php
            echo $this->formCheckbox('geolocation_accessible_markup', true,
                array('checked' => (boolean) get_option('geolocation_accessible_markup')));
            ?>
        </div>
    </div>
</fieldset>
