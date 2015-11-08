<?php

/**
 * The Geolocation plugin.
 *
 * @package Omeka\Plugins\Geolocation
 */
class GeolocationPlugin extends Omeka_Plugin_AbstractPlugin
{
    const GOOGLE_MAPS_API_VERSION = '3.x';

    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'initialize',
        'install',
        'upgrade',
        'uninstall',
        'uninstall_message',
        'config_form',
        'config',
        'define_acl',
        'define_routes',
        'after_save_item',
        'admin_items_show_sidebar',
        'public_items_show',
        'admin_items_search',
        'public_items_search',
        'items_browse_sql',
        'public_head',
        'admin_head',
        'contribution_type_form',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        'admin_navigation_main',
        'public_navigation_main',
        'response_contexts',
        'action_contexts',
        'admin_items_form_tabs',
        'public_navigation_items',
        'api_resources',
        'api_extend_items',
        'exhibit_layouts',
        'api_import_omeka_adapters',
        'item_search_filters',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'geolocation_default_latitude' => '38',
        'geolocation_default_longitude' => '-77',
        'geolocation_default_zoom_level' => '5',
        'geolocation_default_map_type' => 'roadmap',
        'geolocation_per_page' => 10,
        'geolocation_auto_fit_browse' => false,
        'geolocation_browse_append_search' => false,
        'geolocation_default_radius' => 10,
        'geolocation_use_metric_distances' => false,
        'geolocation_item_map_width' => '',
        'geolocation_item_map_height' => '',
        'geolocation_link_to_nav' => false,
        'geolocation_add_map_to_contribution_form' => false,
        'geolocation_accessible_markup' => false,
    );

    /**
     * Add the translations.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
        add_shortcode( 'geolocation', array($this, 'geolocationShortcode'));
    }

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        $db = get_db();
        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->Location` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `item_id` BIGINT UNSIGNED NOT NULL ,
        `latitude` DOUBLE NOT NULL ,
        `longitude` DOUBLE NOT NULL ,
        `zoom_level` INT NOT NULL ,
        `map_type` VARCHAR( 255 ) NOT NULL ,
        `address` TEXT NOT NULL ,
        INDEX (`item_id`)) ENGINE = InnoDB";
        $db->query($sql);

        $this->_installOptions();
    }

    public function hookUpgrade($args)
    {
        if (version_compare($args['old_version'], '1.1', '<')) {
            // If necessary, upgrade the plugin options
            // Check for old plugin options, and if necessary, transfer to new options
            $options = array(
                'default_latitude',
                'default_longitude',
                'default_zoom_level',
                'per_page',
            );
            foreach ($options as $option) {
                $oldOptionValue = get_option('geo_' . $option);
                if ($oldOptionValue != '') {
                    set_option('geolocation_' . $option, $oldOptionValue);
                    delete_option('geo_' . $option);
                }
            }
            delete_option('geo_gmaps_key');
        }

        if (version_compare($args['old_version'], '2.2.3', '<')) {
            set_option('geolocation_default_radius', 10);
        }
    }

    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        $db = get_db();
        $db->query("DROP TABLE IF EXISTS `$db->Location`");

        $this->_uninstallOptions();

        // This is for older versions of Geolocation, which used to store a Google Map API key.
        delete_option('geolocation_gmaps_key');
    }

    /**
     * Display the uninstall message.
     */
    public function hookUninstallMessage()
    {
        echo __('%sWarning%s: This will remove all the geolocations added by this plugin.%s', '<p><strong>', '</strong>', '</p>');
    }

    /**
     * Shows plugin configuration page.
     *
     * @return void
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial(
            'plugins/geolocation-config-form.php'
        );
    }

    /**
     * Processes the configuration form.
     *
     * @return void
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }
    }

    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        $acl->addResource('Locations');
        $acl->allow(null, 'Locations');
    }

    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        // Map based on an item to get all other items around.
        $mapRoute = new Zend_Controller_Router_Route(
            'items/map/:item_id/:page',
            array(
                'module' => 'geolocation',
                'controller' => 'map',
                'action' => 'browse',
                'page' => 1,
            ),
            array(
                'item_id' => '\d+',
                'page' => '\d+',
        ));
        $router->addRoute('items_map_item', $mapRoute);

        $mapRoute = new Zend_Controller_Router_Route(
            'items/map/:pager/:page',
            array(
                'module' => 'geolocation',
                'controller' => 'map',
                'action' => 'browse',
                'pager' => 'page',
                'page' => 1,
            ),
            array(
                'pager' => 'page',
                'page' => '\d+',
        ));
        $router->addRoute('items_map_page', $mapRoute);

        // Trying to make the route look like a KML file so google will eat it.
        // @todo Include page parameter if this works.
        $kmlRoute = new Zend_Controller_Router_Route_Regex(
            'geolocation/map\.kml',
            array(
                'module' => 'geolocation',
                'controller' => 'map',
                'action' => 'browse',
                'output' => 'kml',
        ));
        $router->addRoute('map_kml', $kmlRoute);

        $tabularRoute = new Zend_Controller_Router_Route(
            'items/map/tabular',
            array(
                'module' => 'geolocation',
                'controller' => 'map',
                'action' => 'tabular',
        ));
        $router->addRoute('items_map_tabular', $tabularRoute);
    }

    public function hookAdminHead($args)
    {
        queue_css_file('geolocation-marker');
        queue_js_url('//maps.google.com/maps/api/js');
        queue_js_file('map');
    }

    public function hookPublicHead($args)
    {
        queue_css_file('geolocation-marker');
        queue_js_url('//maps.google.com/maps/api/js');
        queue_js_file('map');
    }

    public function hookAfterSaveItem($args)
    {
        if (!($post = $args['post'])) {
            return;
        }

        // If we don't have the geolocation form on the page, don't do anything!
        if (!isset($post['geolocation'])) {
            return;
        }

        $item = $args['record'];

        // Find the current location objects for the item, by location id.
        $locations = $this->_db->getTable('Location')->findLocationsByItem($item);
        $existingLocations = array();
        foreach ($locations as $location) {
            $existingLocations[$location->id] = $location;
        }
        unset($locations);

        // Get post values.
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $locations = $request->getParam('locations');

        // If we have filled out info for the geolocation, then submit to the db.
        foreach ($locations as $id => $values) {
            $values = array_map('trim', $values);
            // Check the values: minimal is a latitude and a longitude.
            // TODO Add a Zend validator (currently none).
            if (empty($values) || strlen($values['latitude']) == 0 || strlen($values['longitude']) == 0) {
                continue;
            }

            // New location.
            if (strpos($id, 'new-') === 0
                    // Should not be possible.
                    || !isset($existingLocations[$id])
                ) {
                $location = new Location;
                $location->item_id = $item->id;
            }
            // Existing locations.
            else {
                $location = $existingLocations[$id];
                unset($existingLocations[$id]);
            }
            // Update the location.
            $location->setPostData($values);
            $location->save();
        }

        // If the form is empty, then we want to delete whatever location is
        // currently stored.
        foreach ($existingLocations as $location) {
            $location->delete();
        }
    }

    public function hookAdminItemsShowSidebar($args)
    {
        $view = $args['view'];
        $item = $args['item'];
        $location = $this->_db->getTable('Location')->findLocationByItem($item, true);

        if ($location) {
            $html = ''
                . '<div class="geolocation panel">'
                . '<h4>' . __('Geolocation') . '</h4>'
                . '<div style="margin: 14px 0">' . $view->itemGoogleMap($item, true, '100%', '270px')
                . '</div></div>';
            echo $html;
        }
    }

    public function hookPublicItemsShow($args)
    {
        $view = $args['view'];
        $item = $args['item'];
        $location = $this->_db->getTable('Location')->findLocationByItem($item, true);

        if ($location) {
            $width = get_option('geolocation_item_map_width') ?: '';
            $height = get_option('geolocation_item_map_height') ?: '300px';
            $html = '<div id="geolocation">';
            $html .= '<h2>' . __('Geolocation') . '</h2>';
            $html .= $view->itemGoogleMap($item, false, $width, $height);
            $html .= "</div>";
            echo $html;
        }
    }

    /**
     * Hook to include a form in the admin items search form.
     *
     * @internal Themed partial should go to "my_theme/map".
     */
    public function hookAdminItemsSearch($args)
    {
        $view = $args['view'];
        echo $view->partial('map/advanced-search-partial.php');
    }

    /**
     * Hook to include a form in the admin items search form.
     *
     * @internal Themed partial should go to "my_theme/map".
     */
    public function hookPublicItemsSearch($args)
    {
        $view = $args['view'];
        echo $view->partial('map/advanced-search-partial.php');
    }

    public function hookItemsBrowseSql($args)
    {
        $db = $this->_db;
        $select = $args['select'];
        $params = $args['params'];

        $alias = $this->_db->getTable('Location')->getTableAlias();
        if (!empty($params['only_map_items'])
                || !empty($params['item_id'])
                || !empty($params['geolocation-address'])
            ) {
            $select->joinInner(
                array($alias => $db->Location),
                "$alias.item_id = items.id",
                array());
        }

        // Select all items around the selected one.
        if (!empty($params['item_id'])) {
            // TODO Use a sub-query?
            $location = $db->getTable('Location')->findLocationByItem($params['item_id'], true);
            if ($location) {
                $params['geolocation-address'] = $location->address;
                $params['geolocation-latitude'] = $location->latitude;
                $params['geolocation-longitude'] = $location->longitude;
                $params['geolocation-radius'] = isset($params['geolocation-radius'])
                    ? $params['geolocation-radius']
                    : get_option('geolocation_default_radius');
                $this->_selectItemsBrowseSql($select, $params);
            }
        }

        elseif (!empty($params['geolocation-address'])) {
            // Get the address, latitude, longitude, and the radius from parameters
            $params['geolocation-address'] = trim($params['geolocation-address']);
            $params['geolocation-latitude'] = trim($params['geolocation-latitude']);
            $params['geolocation-longitude'] = trim($params['geolocation-longitude']);
            $params['geolocation-radius'] = trim($params['geolocation-radius']);
            // Limit items to those that exist within a geographic radius if an address and radius are provided
            if ($params['geolocation-address'] != ''
                    && is_numeric($params['geolocation-latitude'])
                    && is_numeric($params['geolocation-longitude'])
                    && is_numeric($params['geolocation-radius'])
                ) {
                $this->_selectItemsBrowseSql($select, $params);
            }
        }
    }

    /**
     * Helper for hookItemsBrowseSql().
     */
    private function _selectItemsBrowseSql($select, $params)
    {
        $db = $this->_db;

        // Select distance based upon haversine forumula.
        if (get_option('geolocation_use_metric_distances')) {
            $denominator = 111;
            $earthRadius = 6371;
        } else {
            $denominator = 69;
            $earthRadius = 3959;
        }

        $lat = $db->quote($params['geolocation-latitude'], Zend_Db::FLOAT_TYPE);
        $lng = $db->quote($params['geolocation-longitude'], Zend_Db::FLOAT_TYPE);
        $radius = $db->quote($params['geolocation-radius'], Zend_Db::FLOAT_TYPE);

        $select->columns(<<<SQL
$earthRadius * ACOS(
    COS(RADIANS($lat)) *
    COS(RADIANS(locations.latitude)) *
    COS(RADIANS($lng) - RADIANS(locations.longitude))
    +
    SIN(RADIANS($lat)) *
    SIN(RADIANS(locations.latitude))
) AS distance
SQL
        );

        // WHERE the distance is within radius miles/kilometers of the specified lat & long
        $select->where(<<<SQL
(locations.latitude BETWEEN $lat - $radius / $denominator AND $lat + $radius / $denominator)
AND
(locations.longitude BETWEEN $lng - $radius / $denominator AND $lng + $radius / $denominator)
SQL
        );

        // Actually use distance calculation.
        //$select->having('distance < radius');

        //ORDER by the closest distances
        $select->order('distance');
    }

    /**
     * Add geolocation search options to filter output.
     *
     * @param array $displayArray
     * @param array $args
     * @return array
     */
    public function filterItemSearchFilters($displayArray, $args)
    {
        $requestArray = $args['request_array'];
        if (!empty($requestArray['geolocation-address']) && !empty($requestArray['geolocation-radius'])) {
            if (get_option('geolocation_use_metric_distances')) {
                $unit = __('kilometers');
            } else {
                $unit = __('miles');
            }
            $displayArray['location'] = __('within %1$s %2$s of "%3$s"',
                $requestArray['geolocation-radius'],
                $unit,
                $requestArray['geolocation-address']);
        }
        return $displayArray;
    }

    public function filterAdminNavigationMain($navArray)
    {
        $navArray['Geolocation'] = array(
            'label' => __('Map'),
            'uri' => url('geolocation/map/browse'),
        );
        return $navArray;
    }

    public function filterPublicNavigationMain($navArray)
    {
        $navArray['Geolocation'] = array(
            'label' => __('Map'),
            'uri' => url('geolocation/map/browse'),
        );
        return $navArray;
    }

    public function filterResponseContexts($contexts)
    {
        $contexts['kml'] = array(
            'suffix' => 'kml',
            'headers' => array(
                'Content-Type' => 'application/vnd.google-earth.kml+xml',
            ),
        );
        return $contexts;
    }

    public function filterActionContexts($contexts, $args)
    {
        $controller = $args['controller'];
        if ($controller instanceof Geolocation_MapController) {
            $contexts['browse'] = array(
                'kml',
            );
        }
        return $contexts;
    }

    public function filterAdminItemsFormTabs($tabs, $args)
    {
        // insert the map tab before the Miscellaneous tab
        $item = $args['item'];
        $tabs['Map'] = get_view()->mapForm($item);

        return $tabs;
    }

    public function filterPublicNavigationItems($navArray)
    {
        if (get_option('geolocation_link_to_nav')) {
            $navArray['Browse Map'] = array(
                'label' => __('Browse Map'),
                'uri' => url('items/map'),
            );
        }
        return $navArray;
    }

    /**
     * Register the geolocations API resource.
     *
     * @param array $apiResources
     * @return array
     */
    public function filterApiResources($apiResources)
    {
        $apiResources['geolocations'] = array(
            'record_type' => 'Location',
            'actions' => array(
                'get',
                'index',
                'post',
                'put',
                'delete',
            )
        );
        return $apiResources;
    }

    /**
     * Add geolocations to item API representations.
     *
     * @param array $extend
     * @param array $args
     * @return array
     */
    public function filterApiExtendItems($extend, $args)
    {
        $item = $args['record'];
        $location = $this->_db->getTable('Location')->findBy(array(
            'item_id' => $item->id,
        ));
        if (!$location) {
            return $extend;
        }
        $locationId = $location[0]['id'];
        $extend['geolocations'] = array(
            'id' => $locationId,
            'url' => Omeka_Record_Api_AbstractRecordAdapter::getResourceUrl("/geolocations/$locationId"),
            'resource' => 'geolocations',
        );
        return $extend;
    }

    /**
     * Hook to include a form in a contribution type form.
     *
     * @internal Themed partial should go to "my_theme/contribution/map".
     */
    public function hookContributionTypeForm($args)
    {
        if (get_option('geolocation_add_map_to_contribution_form')) {
            $contributionType = $args['type'];
            $view = $args['view'];
            // Item is used only to get original location, if not changed.
            $item = (empty($_POST) && isset($view->item)) ? $view->item : null;
            echo $view->mapForm($item);
        }
    }

    public function filterExhibitLayouts($layouts)
    {
        $layouts['geolocation-map'] = array(
            'name' => __('Geolocation Map'),
            'description' => __('Show attached items on a map'),
        );
        return $layouts;
    }

    public function filterApiImportOmekaAdapters($adapters, $args)
    {
        $geolocationAdapter = new ApiImport_ResponseAdapter_Omeka_GenericAdapter(null, $args['endpointUri'], 'Location');
        $geolocationAdapter->setResourceProperties(array(
            'item' => 'Item',
        ));
        $adapters['geolocations'] = $geolocationAdapter;
        return $adapters;
    }

    public function geolocationShortcode($args)
    {
        static $index = 0;
        $index++;

        $booleanFilter = new Omeka_Filter_Boolean;

        if (isset($args['lat'])) {
            $latitude = $args['lat'];
        } else {
            $latitude = get_option('geolocation_default_latitude');
        }

        if (isset($args['lon'])) {
            $longitude = $args['lon'];
        } else {
            $longitude = get_option('geolocation_default_longitude');
        }

        if (isset($args['zoom'])) {
            $zoomLevel = $args['zoom'];
        } else {
            $zoomLevel = get_option('geolocation_default_zoom_level');
        }

        $center = array(
            'latitude' => (double) $latitude,
            'longitude' => (double) $longitude,
            'zoomLevel' => (double) $zoomLevel,
        );

        $options = array();

        if (isset($args['fit'])) {
            $options['fitMarkers'] = $booleanFilter->filter($args['fit']);
        } else {
            $options['fitMarkers'] = '1';
        }

        if (isset($args['type'])) {
            $options['mapType'] = $args['type'];
        }

        if (isset($args['collection'])) {
            $options['params']['collection'] = $args['collection'];
        }

        if (isset($args['tags'])) {
            $options['params']['tags'] = $args['tags'];
        }

        $pattern = '#^[0-9]*(px|%)$#';

        if (isset($args['height']) && preg_match($pattern, $args['height'])) {
            $height = $args['height'];
        } else {
            $height = '436px';
        }

        if (isset($args['width']) && preg_match($pattern, $args['width'])) {
            $width = $args['width'];
        } else {
            $width = '100%';
        }

        $attrs = array(
            'style' => "height:$height;width:$width",
        );
        return get_view()->googleMap("geolocation-shortcode-$index", $options, $attrs, $center);
    }
}
