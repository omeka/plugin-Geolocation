<?php

class GeolocationPlugin extends Omeka_Plugin_AbstractPlugin
{
    const DEFAULT_LOCATIONS_PER_PAGE = 10;
    const DEFAULT_BASEMAP = 'CartoDB.Voyager';
    const DEFAULT_GEOCODER = 'nominatim';

    protected $_hooks = [
        'install',
        'uninstall',
        'upgrade',
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
        'initialize',
        'contribution_type_form',
        'contribution_save_form',
        'static_site_export_site_config',
        'static_site_export_site_export_post',
        'static_site_export_item_bundle',
        'exhibit_builder_static_site_export_exhibit_page_block',
    ];

    protected $_filters = [
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
        'static_site_export_vendor_packages',
        'static_site_export_shortcodes',
        'static_site_export_omeka_shortcode_callbacks',
    ];

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
        `address` TEXT NOT NULL ,
        `label` VARCHAR( 255 ) NOT NULL DEFAULT '' ,
        INDEX (`item_id`)) ENGINE = InnoDB";
        $db->query($sql);

        set_option('geolocation_default_latitude', '38');
        set_option('geolocation_default_longitude', '-77');
        set_option('geolocation_default_zoom_level', '5');
        set_option('geolocation_per_page', self::DEFAULT_LOCATIONS_PER_PAGE);
        set_option('geolocation_add_map_to_contribution_form', '0');
        set_option('geolocation_default_radius', 10);
        set_option('geolocation_use_metric_distances', '0');
        set_option('geolocation_basemap', self::DEFAULT_BASEMAP);
        set_option('geolocation_geocoder', self::DEFAULT_GEOCODER);
        set_option('geolocation_item_map_enable', '1');
    }

    public function hookUninstall()
    {
        // Delete the plugin options
        delete_option('geolocation_default_latitude');
        delete_option('geolocation_default_longitude');
        delete_option('geolocation_default_zoom_level');
        delete_option('geolocation_per_page');
        delete_option('geolocation_add_map_to_contribution_form');
        delete_option('geolocation_use_metric_distances');
        delete_option('geolocation_link_to_nav');
        delete_option('geolocation_default_radius');
        delete_option('geolocation_basemap');
        delete_option('geolocation_geocoder');
        delete_option('geolocation_auto_fit_browse');
        delete_option('geolocation_mapbox_access_token');
        delete_option('geolocation_mapbox_map_id');
        delete_option('geolocation_cluster');
        delete_option('geolocation_item_map_enable');
        delete_option('geolocation_custom_map');

        // This is for older versions of Geolocation, which used to store a Google Map API key.
        delete_option('geolocation_gmaps_key');

        // Drop the Location table
        $db = get_db();
        $db->query("DROP TABLE IF EXISTS `$db->Location`");
    }

    public function hookUpgrade($args)
    {
        if (version_compare($args['old_version'], '1.1', '<')) {
            // If necessary, upgrade the plugin options
            // Check for old plugin options, and if necessary, transfer to new options
            $options = ['default_latitude', 'default_longitude', 'default_zoom_level', 'per_page'];
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
        if (version_compare($args['old_version'], '3.0', '<')) {
            delete_option('geolocation_api_key');
            delete_option('geolocation_map_type');
            set_option('geolocation_basemap', self::DEFAULT_BASEMAP);
        }
        if (version_compare($args['old_version'], '3.1', '<')) {
            set_option('geolocation_geocoder', self::DEFAULT_GEOCODER);

            if (get_option('geolocation_basemap') == 'OpenStreetMap.BlackAndWhite') {
                set_option('geolocation_basemap', self::DEFAULT_BASEMAP);
            }
        }
        if (version_compare($args['old_version'], '3.2', '<')) {
            $newMapboxIds = [
                'mapbox.streets' => 'mapbox/streets-v11',
                'mapbox.outdoors' => 'mapbox/outdoors-v11',
                'mapbox.light' => 'mapbox/light-v10',
                'mapbox.dark' => 'mapbox/dark-v10',
                'mapbox.satellite' => 'mapbox/satellite-v9',
                'mapbox.streets-satellite' => 'mapbox/satellite-streets-v11',
            ];

            $oldMapboxId = get_option('geolocation_mapbox_map_id');
            if ($oldMapboxId && isset($newMapboxIds[$oldMapboxId])) {
                set_option('geolocation_mapbox_map_id', $newMapboxIds[$oldMapboxId]);
            }
        }
        if (version_compare($args['old_version'], '3.3', '<')) {
            $stamenBasemaps = [
                'Stamen.Toner' => 'Stadia.StamenToner',
                'Stamen.TonerBackground' => 'Stadia.StamenTonerBackground',
                'Stamen.TonerLite' => 'Stadia.StamenTonerLite',
                'Stamen.Watercolor' => 'Stadia.StamenWatercolor',
                'Stamen.Terrain' => 'Stadia.StamenTerrain',
                'Stamen.TerrainBackground' => 'Stadia.StamenTerrainBackground',
            ];

            $currentBasemap = get_option('geolocation_basemap');
            if (isset($stamenBasemaps[$currentBasemap])) {
                set_option('geolocation_basemap', $stamenBasemaps[$currentBasemap]);
            }
        }
        if (version_compare($args['old_version'], '4.0', '<')) {
            $db = get_db();
            $db->query("ALTER TABLE `$db->Location` ADD COLUMN `label` VARCHAR(255) NOT NULL DEFAULT '' AFTER `address`");
            $db->query("ALTER TABLE `$db->Location` DROP COLUMN `map_type`");
        }
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $view = $args['view'];
        $customMap = [
            'type' => 'none',
            'tile_url' => '',
            'wms_url' => '',
            'layers' => '',
            'styles' => '',
            'transparent' => false,
            'minNativeZoom' => '',
            'maxNativeZoom' => '',
            'attribution' => '',
        ];
        $customMap = array_merge($customMap, (array) json_decode((string) get_option('geolocation_custom_map'), true));
        include 'config_form.php';
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        // Use the form to set a bunch of default options in the db
        set_option('geolocation_default_latitude', $_POST['default_latitude']);
        set_option('geolocation_default_longitude', $_POST['default_longitude']);
        set_option('geolocation_default_zoom_level', $_POST['default_zoom_level']);
        set_option('geolocation_item_map_enable', $_POST['geolocation_item_map_enable']);
        set_option('geolocation_item_map_width', $_POST['item_map_width']);
        set_option('geolocation_item_map_height', $_POST['item_map_height']);
        $perPage = (int) $_POST['per_page'];
        if ($perPage <= 0) {
            $perPage = self::DEFAULT_LOCATIONS_PER_PAGE;
        }
        set_option('geolocation_per_page', $perPage);
        set_option('geolocation_add_map_to_contribution_form', $_POST['geolocation_add_map_to_contribution_form']);
        set_option('geolocation_link_to_nav', $_POST['geolocation_link_to_nav']);
        set_option('geolocation_default_radius', $_POST['geolocation_default_radius']);
        set_option('geolocation_use_metric_distances', $_POST['geolocation_use_metric_distances']);
        set_option('geolocation_basemap', $_POST['basemap']);
        set_option('geolocation_auto_fit_browse', $_POST['auto_fit_browse']);
        set_option('geolocation_mapbox_access_token', $_POST['mapbox_access_token']);
        set_option('geolocation_mapbox_map_id', $_POST['mapbox_map_id']);
        set_option('geolocation_cluster', $_POST['cluster']);
        set_option('geolocation_geocoder', $_POST['geocoder']);

        $customMap = array_filter(array_map('trim', $_POST['custom_map']), 'strlen');
        if (isset($customMap['minNativeZoom'])) {
            $customMap['minNativeZoom'] = (int) $customMap['minNativeZoom'];
        }
        if (isset($customMap['maxNativeZoom'])) {
            $customMap['maxNativeZoom'] = (int) $customMap['maxNativeZoom'];
        }
        $customMap['transparent'] = (bool) $customMap['transparent'];
        set_option('geolocation_custom_map', json_encode($customMap));
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
        $mapRoute = new Zend_Controller_Router_Route('items/map', [
            'controller' => 'map',
            'action' => 'browse',
            'module' => 'geolocation',
        ]);
        $router->addRoute('items_map', $mapRoute);

        // Trying to make the route look like a KML file so google will eat it.
        // @todo Include page parameter if this works.
        $kmlRoute = new Zend_Controller_Router_Route_Regex('geolocation/map\.kml', [
            'controller' => 'map',
            'action' => 'browse',
            'module' => 'geolocation',
            'output' => 'kml',
        ]);
        $router->addRoute('map_kml', $kmlRoute);
    }

    public function hookAdminHead($args)
    {
        $this->_head();
    }

    public function hookPublicHead($args)
    {
        $this->_head();
    }

    private function _getVersion()
    {
        $pluginLoader = Zend_Registry::get('plugin_loader');
        return $pluginLoader->getPlugin('Geolocation')->getIniVersion();
    }

    private function _head()
    {
        $version = $this->_getVersion();
        queue_css_file('leaflet/leaflet', null, null, 'javascripts', $version);
        queue_css_file('leaflet-draw/leaflet.draw', null, null, 'javascripts', $version);
        queue_css_file('geolocation-marker', null, null, 'css', $version);
        queue_js_file(['leaflet/leaflet', 'leaflet/leaflet-providers', 'leaflet-draw/leaflet.draw', 'map'], 'javascripts', [], $version);

        if (get_option('geolocation_cluster')) {
            queue_css_file(['MarkerCluster', 'MarkerCluster.Default'], null, null, 'javascripts/leaflet-markercluster', $version);
            queue_js_file('leaflet-markercluster/leaflet.markercluster', 'javascripts', [], $version);
        }
    }

    public function hookAfterSaveItem($args)
    {
        if (!($post = $args['post'])) {
            return;
        }

        $item = $args['record'];
        // geolocation_form_shown is a sentinel set by input-partial.php. Its
        // presence means the map form was rendered, so missing geolocation[]
        // inputs mean all markers were deleted, not that the form was absent.
        if (!isset($post['geolocation_form_shown'])) {
            return;
        }

        // Build an index of existing locations. As POST entries are matched to existing
        // records, they are removed from $remaining. Whatever is left at the end was
        // not in the POST and gets deleted.
        $remaining = [];
        foreach ($this->_db->getTable('Location')->findBy(['item_id' => $item->id]) as $loc) {
            $remaining[$loc->id] = $loc;
        }

        foreach ($post['geolocation'] ?? [] as $entry) {
            if (!is_numeric($entry['latitude'] ?? null) || !is_numeric($entry['longitude'] ?? null)) {
                continue;
            }
            $id = !empty($entry['id']) ? (int) $entry['id'] : null;
            if ($id && isset($remaining[$id])) {
                $location = $remaining[$id];
                unset($remaining[$id]);
            } else {
                $location = new Location;
                $location->item_id = $item->id;
            }
            // Exclude 'id' so a crafted POST cannot cause setPostData to set
            // the id on a new record, which would trigger an UPDATE on a row
            // belonging to a different item.
            $location->setPostData(array_diff_key($entry, ['id' => null]));
            $location->save();
        }

        foreach ($remaining as $loc) {
            $loc->delete();
        }
    }

    public function hookAdminItemsShowSidebar($args)
    {
        $view = $args['view'];
        $item = $args['item'];
        $locations = $this->_db->getTable('Location')->findBy(['item_id' => $item->id]);

        if (!empty($locations)) {
            $html = ''
                  . '<div class="geolocation panel">'
                  . '<h4>' . __('Geolocation') . '</h4>'
                  . '<div style="margin: 14px 0">'
                  . $view->geolocationMapSingle($item, '100%', '270px')
                  . '</div></div>';
            echo $html;
        }
    }

    public function hookPublicItemsShow($args)
    {
        if (!get_option('geolocation_item_map_enable')) {
            return;
        }

        $view = $args['view'];
        $item = $args['item'];
        $locations = $this->_db->getTable('Location')->findBy(['item_id' => $item->id]);

        if (!empty($locations)) {
            $width = $this->_filterCssLength(get_option('geolocation_item_map_width'), '100%');
            $height = $this->_filterCssLength(get_option('geolocation_item_map_height'), '300px');
            $html = "<div id='geolocation'>";
            $html .= '<h2>'.__('Geolocation').'</h2>';
            $html .= $view->geolocationMapSingle($item, $width, $height);
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
        $alias = $this->_db->getTable('Location')->getTableAlias();
        $isMapped = null;
        if (array_key_exists('geolocation-mapped', $args['params'])
            && $args['params']['geolocation-mapped'] !== ''
        ) {
            $isMapped = (bool) $args['params']['geolocation-mapped'];
        }

        if ($isMapped === true
            || !empty($args['params']['geolocation-address'])
        ) {
            $select->joinInner(
                [$alias => $db->Location],
                "$alias.item_id = items.id",
                []
            );
        } elseif ($isMapped === false) {
            $select->joinLeft(
                [$alias => $db->Location],
                "$alias.item_id = items.id",
                []
            );
            $select->where("$alias.id IS NULL");
        }
        if (!empty($args['params']['geolocation-address'])) {
            // Get the address, latitude, longitude, and the radius from parameters
            $address = trim($args['params']['geolocation-address']);
            $lat = trim($args['params']['geolocation-latitude']);
            $lng = trim($args['params']['geolocation-longitude']);
            $radius = trim($args['params']['geolocation-radius']);
            // Limit items to those that exist within a geographic radius if an address and radius are provided
            if ($address != ''
                && is_numeric($lat)
                && is_numeric($lng)
                && is_numeric($radius)
            ) {
                // SELECT distance based upon haversine forumula
                if (get_option('geolocation_use_metric_distances')) {
                    $denominator = 111;
                    $earthRadius = 6371;
                } else {
                    $denominator = 69;
                    $earthRadius = 3959;
                }

                $radius = $db->quote($radius, Zend_Db::FLOAT_TYPE);
                $lat = $db->quote($lat, Zend_Db::FLOAT_TYPE);
                $lng = $db->quote($lng, Zend_Db::FLOAT_TYPE);
                $sqlMathExpression =
                    new Zend_Db_Expr(
                        "$earthRadius * ACOS(
                        COS(RADIANS($lat)) *
                        COS(RADIANS(locations.latitude)) *
                        COS(RADIANS($lng) - RADIANS(locations.longitude))
                        +
                        SIN(RADIANS($lat)) *
                        SIN(RADIANS(locations.latitude))
                        ) AS distance"
                    );

                $select->columns($sqlMathExpression);

                // WHERE the distance is within radius miles/kilometers of the specified lat & long
                $locationWithinRadius =
                    new Zend_Db_Expr(
                        "(locations.latitude BETWEEN $lat - $radius / $denominator
                            AND $lat + $radius / $denominator)
                            AND
                        (locations.longitude BETWEEN $lng - $radius / $denominator
                            AND $lng + $radius / $denominator)"
                    );
                $select->where($locationWithinRadius);

                // Actually use distance calculation.
                //$select->having('distance < radius');

                //ORDER by the closest distances
                $select->order('distance');
            }
        }
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
            $displayArray['location'] = __(
                'within %1$s %2$s of "%3$s"',
                $requestArray['geolocation-radius'],
                $unit,
                $requestArray['geolocation-address']
            );
        }
        if (array_key_exists('geolocation-mapped', $requestArray)
            && $requestArray['geolocation-mapped'] !== ''
        ) {
            if ($requestArray['geolocation-mapped']) {
                $displayArray['Geolocation Status'] = __('Only Items with Locations');
            } else {
                $displayArray['Geolocation Status'] = __('Only Items without Locations');
            }
        }
        return $displayArray;
    }

    /**
     * Add the translations.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
        add_shortcode('geolocation', [$this, 'geolocationShortcode']);
    }

    public function filterAdminNavigationMain($navArray)
    {
        $navArray['Geolocation'] = ['label' => __('Map'), 'uri' => url('geolocation/map/browse')];
        return $navArray;
    }

    public function filterPublicNavigationMain($navArray)
    {
        $navArray['Geolocation'] = ['label' => __('Map'), 'uri' => url('geolocation/map/browse')];
        return $navArray;
    }

    public function filterResponseContexts($contexts)
    {
        $contexts['kml'] = ['suffix' => 'kml',
            'headers' => ['Content-Type' => 'text/xml']];
        return $contexts;
    }

    public function filterActionContexts($contexts, $args)
    {
        $controller = $args['controller'];
        if ($controller instanceof Geolocation_MapController) {
            $contexts['browse'] = ['kml'];
        }
        return $contexts;
    }

    public function filterAdminItemsFormTabs($tabs, $args)
    {
        // insert the map tab before the Miscellaneous tab
        $item = $args['item'];
        $tabs['Map'] = $this->_mapForm($item);

        return $tabs;
    }

    public function filterPublicNavigationItems($navArray)
    {
        if (get_option('geolocation_link_to_nav')) {
            $navArray['Browse Map'] = [
                'label' => __('Browse Map'),
                'uri' => url('items/map'),
            ];
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
        $apiResources['geolocations'] = [
            'record_type'  => 'Location',
            'actions'      => ['get', 'index', 'post', 'put', 'delete'],
            // Whitelist item_id as an allowed GET param on the index action;
            // without this the API router rejects ?item_id=X requests.
            'index_params' => ['item_id'],
        ];
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
        $locations = $this->_db->getTable('Location')->findBy(['item_id' => $item->id]);
        if (!$locations) {
            return $extend;
        }
        // count+url is the Omeka API format for multi-resource references;
        // ApiController validates this shape and rejects plain arrays of objects.
        $extend['geolocations'] = [
            'count'    => count($locations),
            'url'      => Omeka_Record_Api_AbstractRecordAdapter::getResourceUrl('/geolocations') . '?item_id=' . $item->id,
            'resource' => 'geolocations',
        ];
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
            echo $this->_mapForm(null, __('Find A Geographic Location For The %s:', $contributionType->display_name), $view);
        }
    }

    public function hookContributionSaveForm($args)
    {
        $this->hookAfterSaveItem($args);
    }

    public function filterExhibitLayouts($layouts)
    {
        $layouts['geolocation-map'] = [
            'name' => __('Geolocation Map'),
            'description' => __('Show attached items on a map'),
        ];
        return $layouts;
    }

    public function filterApiImportOmekaAdapters($adapters, $args)
    {
        $geolocationAdapter = new ApiImport_ResponseAdapter_Omeka_GenericAdapter(null, $args['endpointUri'], 'Location');
        $geolocationAdapter->setResourceProperties(['item' => 'Item']);
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

        $center = ['latitude' => (float) $latitude, 'longitude' => (float) $longitude, 'zoomLevel' => (float) $zoomLevel];

        $options = [];

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

        if (isset($args['range'])) {
            $options['params']['range'] = $args['range'];
        }

        $height = $this->_filterCssLength(isset($args['height']) ? $args['height'] : '', '436px');
        $width = $this->_filterCssLength(isset($args['width']) ? $args['width'] : '', '100%');

        $attrs = ['style' => "height:$height;width:$width"];
        return get_view()->geolocationMapBrowse("geolocation-shortcode-$index", $options, $attrs, $center);
    }

    /**
     * Returns the form code for geographically searching for items.
     *
     * @param Item $item
     * @param string $label if empty string, a default string will be used. Set
     * null if you don't want a label.
     * @param Omeka_View $view
     * @return string Html string.
     */
    protected function _mapForm($item, $label = '', $view = null)
    {
        if (is_null($view)) {
            $view = get_view();
        }

        if ($label == '') {
            $label = __('Find a Location by Address:');
        }

        $center = $this->_getCenter();
        $center['show'] = false;

        // If the form was previously submitted (e.g. save failed validation),
        // re-populate from POST so unsaved changes are not lost.
        $existingLocations = [];
        if (isset($_POST['geolocation_form_shown'])) {
            foreach ($_POST['geolocation'] ?? [] as $entry) {
                if (!is_numeric($entry['latitude'] ?? null) || !is_numeric($entry['longitude'] ?? null)) {
                    continue;
                }
                $existingLocations[] = [
                    'id'         => !empty($entry['id']) ? (int) $entry['id'] : null,
                    'latitude'   => (float) $entry['latitude'],
                    'longitude'  => (float) $entry['longitude'],
                    'zoom_level' => (int) ($entry['zoom_level'] ?? 0),
                    'address'    => $entry['address'] ?? '',
                    'label'      => $entry['label'] ?? '',
                ];
            }
        } elseif ($item && $item->id) {
            foreach ($this->_db->getTable('Location')->findBy(['item_id' => $item->id]) as $loc) {
                $existingLocations[] = [
                    'id'         => $loc->id,
                    'latitude'   => $loc->latitude,
                    'longitude'  => $loc->longitude,
                    'zoom_level' => $loc->zoom_level,
                    'address'    => $loc->address,
                    'label'      => $loc->label,
                ];
            }
        }

        // For single-location items this sets the initial zoom correctly.
        // For multi-location items fitBounds overrides the center on tab select.
        if ($existingLocations) {
            $center['latitude'] = $existingLocations[0]['latitude'];
            $center['longitude'] = $existingLocations[0]['longitude'];
            $center['zoomLevel'] = $existingLocations[0]['zoom_level'];
        }

        $options = [];
        $options['form'] = ['id' => 'location_form'];
        $options['cluster'] = false;

        return $view->partial('map/input-partial.php', [
            'label' => $label,
            'center' => $center,
            'options' => $options,
            'existingLocations' => $existingLocations,
        ]);
    }

    protected function _getCenter()
    {
        return [
            'latitude' => (float) get_option('geolocation_default_latitude'),
            'longitude' => (float) get_option('geolocation_default_longitude'),
            'zoomLevel' => (float) get_option('geolocation_default_zoom_level'),
        ];
    }

    protected function _filterCssLength($length, $default)
    {
        $length = trim((string) $length);

        // Treat bare numbers as pixel dimensions
        if (ctype_digit($length)) {
            $length .= 'px';
        }

        if (!preg_match('/^[0-9]+(px|%)$/', $length)) {
            return $default;
        }

        return $length;
    }

    /**
     * StaticSiteExport plugin: Add "Map" link to static site menu.
     */
    public function hookStaticSiteExportSiteConfig($args)
    {
        $args['site_config']['menus']['main'][] = [
            'name' => __('Map'),
            'pageRef' => '/geolocation',
            'weight' => 40,
        ];
    }

    /**
     * StaticSiteExport plugin: Add vendor packages to static site.
     */
    function filterStaticSiteExportVendorPackages($vendorPackages, $args)
    {
        $vendorPackages['leaflet'] = sprintf('%s/Geolocation/libraries/Geolocation/StaticSiteExport/leaflet', PLUGIN_DIR);
        $vendorPackages['omeka-geolocation'] = sprintf('%s/Geolocation/libraries/Geolocation/StaticSiteExport/omeka-geolocation', PLUGIN_DIR);
        return $vendorPackages;
    }

    /**
     * StaticSiteExport plugin: Add shortcodes to static site.
     */
    public function filterStaticSiteExportShortcodes($shortcodes, $args)
    {
        $shortcodes['omeka-geolocation-locations'] = sprintf('%s/Geolocation/libraries/Geolocation/StaticSiteExport/shortcodes/omeka-geolocation-locations.html', PLUGIN_DIR);
        return $shortcodes;
    }

    public function filterStaticSiteExportOmekaShortcodeCallbacks($callbacks)
    {
        // @see GeolocationPlugin::geolocationShortcode()
        $callbacks['geolocation'] = function ($args, $frontMatter, $job) {
            $frontMatter['css'][] = 'vendor/leaflet/leaflet.css';
            $frontMatter['css'][] = 'vendor/omeka-geolocation/geolocation-marker.css';
            $frontMatter['js'][] = 'vendor/jquery/jquery.js';
            $frontMatter['js'][] = 'vendor/leaflet/leaflet.js';
            $frontMatter['js'][] = 'vendor/omeka-geolocation/geolocation-locations.js';
            return '{{< omeka-geolocation-locations page="geolocation" locationsResource="geolocation_locations.json" >}}';
        };

        return $callbacks;
    }

    /**
     * StaticSiteExport plugin: Add geolocation content to static site.
     */
    public function hookStaticSiteExportSiteExportPost($args)
    {
        $job = $args['job'];

        // Create the geolocation section.
        $frontMatter = new ArrayObject([
            'title' => __('Map'),
            'css' => [
                'vendor/leaflet/leaflet.css',
                'vendor/omeka-geolocation/geolocation-marker.css',
            ],
            'js' => [
                'vendor/jquery/jquery.js',
                'vendor/leaflet/leaflet.js',
                'vendor/omeka-geolocation/geolocation-locations.js',
            ],
        ]);
        $job->makeDirectory('content/geolocation');
        $job->makeFile(
            'content/geolocation/index.md',
            sprintf(
                "%s\n%s",
                json_encode($frontMatter, JSON_PRETTY_PRINT),
                '{{< omeka-geolocation-locations page="geolocation" locationsResource="geolocation_locations.json" >}}'
            )
        );

        // Make the locations file.
        $locationRows = get_db()->getTable('Location')->findAll();
        $locations = [];
        foreach ($locationRows as $locationRow) {
            $item = get_db()->getTable('Item')->find($locationRow->item_id);
            $locations[] = $this->_locationToStaticSiteExportArray($locationRow, $item);
        }
        $job->makeFile('content/geolocation/geolocation_locations.json', json_encode($locations));
    }

    /**
     * StaticSiteExport plugin: Add map block to item pages.
     */
    public function hookStaticSiteExportItemBundle($args)
    {
        $job = $args['job'];
        $item = $args['item'];
        $frontMatterPage = $args['front_matter_page'];
        $blocks = $args['blocks'];

        $itemLocations = get_db()->getTable('Location')->findBy(['item_id' => $item->id]);
        if (empty($itemLocations)) {
            return;
        }

        $frontMatterPage['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatterPage['css'][] = 'vendor/omeka-geolocation/geolocation-marker.css';
        $frontMatterPage['js'][] = 'vendor/jquery/jquery.js';
        $frontMatterPage['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatterPage['js'][] = 'vendor/omeka-geolocation/geolocation-locations.js';

        // Make the locations file.
        $locations = [];
        foreach ($itemLocations as $location) {
            $locations[] = $this->_locationToStaticSiteExportArray($location, $item);
        }
        $job->makeFile(
            sprintf('content/items/%s/geolocation_locations.json', $item->id),
            json_encode($locations)
        );

        // Make the markdown.
        $markdown = [];
        $markdown[] = sprintf('## %s', __('Geolocation'));
        $markdown[] = sprintf('{{< omeka-geolocation-locations page="items/%s" locationsResource="geolocation_locations.json" >}}', $item->id);

        $blocks[] = [
            'name' => 'geolocation',
            'frontMatter' => new ArrayObject,
            'markdown' => implode("\n", $markdown),
        ];
    }

    /**
     * StaticSiteExport plugin: Add map block to exhibit builder pages.
     */
    public function hookExhibitBuilderStaticSiteExportExhibitPageBlock($args)
    {
        $job = $args['job'];
        $frontMatterExhibitPage = $args['frontMatterExhibitPage'];
        $frontMatterExhibitPageBlock = $args['frontMatterExhibitPageBlock'];
        $exhibitPageBlock = $args['block'];
        $markdown = $args['markdown'];

        if ('geolocation-map' !== $exhibitPageBlock->layout) {
            return;
        }

        $exhibitPage = $exhibitPageBlock->getPage();
        $exhibit = $exhibitPage->getExhibit();
        $attachments = $exhibitPageBlock->getAttachments();

        $frontMatterExhibitPage['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatterExhibitPage['css'][] = 'vendor/omeka-geolocation/geolocation-marker.css';
        $frontMatterExhibitPage['js'][] = 'vendor/jquery/jquery.js';
        $frontMatterExhibitPage['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatterExhibitPage['js'][] = 'vendor/omeka-geolocation/geolocation-locations.js';

        $locations = [];
        foreach ($attachments as $attachment) {
            $item = $attachment->getItem();
            $itemLocations = get_db()->getTable('Location')->findBy(['item_id' => $item->id]);
            foreach ($itemLocations as $location) {
                $locations[] = $this->_locationToStaticSiteExportArray($location, $item);
            }
        }
        $job->makeFile(
            sprintf('content/exhibits/%s/%s/geolocation_locations.json', $exhibit->slug, $exhibitPage->slug),
            json_encode($locations)
        );

        $markdown[] = sprintf(
            '{{< omeka-geolocation-locations page="exhibits/%s/%s" locationsResource="geolocation_locations.json" >}}',
            $exhibit->slug,
            $exhibitPage->slug
        );
    }

    private function _locationToStaticSiteExportArray(Location $location, Item $item)
    {
        $file = $item->getFile();
        return [
            'latitude'     => $location->latitude,
            'longitude'    => $location->longitude,
            'zoomLevel'    => $location->zoom_level,
            'address'      => $location->address,
            'label'        => $location->label,
            'itemID'       => $item->id,
            'itemTitle'    => $item->getDisplayTitle(),
            'fileID'       => $file ? $file->id : null,
            'hasThumbnail' => $item->hasThumbnail(),
        ];
    }
}
