<?php $view = get_view(); ?>
<fieldset>
    <legend><?php echo __('General Settings'); ?></legend>
    <div class="field">
    <div class="two columns alpha">
        <label for="default_latitude"><?php echo __('Default Latitude'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Latitude of the map's initial center point, in degrees. Must be between -90 and 90."); ?></p>
        <?php echo $view->formText('default_latitude', get_option('geolocation_default_latitude')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="default_longitude"><?php echo __('Default Longitude'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Longitude of the map's initial center point, in degrees. Must be between -180 and 180.");?></p>
        <?php echo $view->formText('default_longitude', get_option('geolocation_default_longitude')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="default_zoomlevel"><?php echo __('Default Zoom Level'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('An integer greater than or equal to 0, where 0 represents the most zoomed out scale.'); ?></p>
        <?php echo $view->formText('default_zoomlevel', get_option('geolocation_default_zoom_level')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="map_type"><?php echo __('Map Type'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The type of map to display'); ?></p>
        <?php
        echo $view->formSelect('map_type', get_option('geolocation_map_type'), array(), array(
            'roadmap' => __('Roadmap'),
            'satellite' => __('Satellite'),
            'hybrid' =>__('Hybrid'),
            'terrain' => __('Terrain')
            )
        );
        ?>
    </div>
</div>
</fieldset>

<fieldset>
<legend><?php echo __('Browse Map Settings'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <label for="per_page"><?php echo __('Number of Locations Per Page'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The number of locations displayed per page when browsing the map.'); ?></p>
        <?php echo $view->formText('per_page', get_option('geolocation_per_page')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label for="auto_fit_browse"><?php echo __('Auto-fit to Locations'); ?></label>
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
        echo $view->formCheckbox('auto_fit_browse', true,
            array('checked' => (boolean) get_option('geolocation_auto_fit_browse')));
        ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label for="per_page"><?php echo __('Default Radius'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The size of the default radius to use on the items advanced search page. See below for whether to measure in miles or kilometers.'); ?></p>
        <?php echo $view->formText('geolocation_default_radius', get_option('geolocation_default_radius')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label for="geolocation_use_metric_distances"><?php echo __('Use metric distances'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Use metric distances in proximity search.'); ?></p>
        <?php
        echo $view->formCheckbox('geolocation_use_metric_distances', true,
            array('checked' => (boolean) get_option('geolocation_use_metric_distances')));
        ?>
    </div>
</div>
</fieldset>

<fieldset>
<legend><?php echo __('Item Map Settings'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <label for="item_map_width"><?php echo __('Width for Item Map'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The width of the map displayed on your items/show page. If left blank, the default width of 100% will be used.'); ?></p>
        <?php echo $view->formText('item_map_width', get_option('geolocation_item_map_width')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="item_map_height"><?php echo __('Height for Item Map'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The height of the map displayed on your items/show page. If left blank, the default height of 300px will be used.'); ?></p>
        <?php echo $view->formText('item_map_height', get_option('geolocation_item_map_height')); ?>
    </div>
</div>
</fieldset>

<fieldset>
<legend><?php echo __('Map Integration'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <label for="geolocation_link_to_nav"><?php echo __('Add Link to Map on Items/Browse Navigation'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Add a link to the items map on all the items/browse pages.'); ?></p>
        <?php
        echo get_view()->formCheckbox('geolocation_link_to_nav', true,
            array('checked' => (boolean) get_option('geolocation_link_to_nav')));
        ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="geolocation_add_map_to_contribution_form"><?php echo __('Add Map To Contribution Form'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('If the Contribution plugin is installed and activated, Geolocation  will add a geolocation map field to the contribution form to associate a location to a contributed item.'); ?></p>
        <?php
        echo get_view()->formCheckbox('geolocation_add_map_to_contribution_form', true,
            array('checked' => (boolean) get_option('geolocation_add_map_to_contribution_form')));
        ?>
    </div>
</div>
</fieldset>
