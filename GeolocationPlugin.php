<?php

define('GOOGLE_MAPS_API_VERSION', '3.x');
define('GEOLOCATION_MAX_LOCATIONS_PER_PAGE', 50);
define('GEOLOCATION_DEFAULT_LOCATIONS_PER_PAGE', 10);
define('GEOLOCATION_PLUGIN_DIR', PLUGIN_DIR . '/Geolocation');

class GeolocationPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
            'install',
            'uninstall',
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
            'initialize'          
            );
    
    protected $_filters = array(
            'admin_navigation_main',
            'public_navigation_main',
            'response_contexts',
            'action_contexts',
            'admin_items_form_tabs',
            'public_navigation_items'            
            );
    
    
    public function setUp()
    {
        if(plugin_is_active('Contribution')) {
            $this->_hooks[] = 'contribution_type_form';
            //$this->_hooks[] = 'contribution_save_form';
        }
        parent::setUp();
    }
        
    public function hookAdminHead($args)
    {
        $view = $args['view'];
        $view->addHelperPath(GEOLOCATION_PLUGIN_DIR . '/helpers', 'Geolocation_View_Helper_');
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();        
        if ( ($module == 'geolocation' && $controller == 'map')
                    || ($module == 'contribution' 
                        && $controller == 'contribution' 
                        && $action == 'contribute' 
                        && get_option('geolocation_add_map_to_contribution_form') == '1')
                     || ($controller == 'items') )  {
            queue_css_file('geolocation-items-map');
            queue_css_file('geolocation-marker');
            queue_js_url("http://maps.google.com/maps/api/js?sensor=false");
            queue_js_file('map');
        }
    }
        
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
        INDEX (`item_id`)) ENGINE = MYISAM";
        $db->query($sql);
        
        set_option('geolocation_default_latitude', '38');
        set_option('geolocation_default_longitude', '-77');
        set_option('geolocation_default_zoom_level', '5');
        set_option('geolocation_per_page', GEOLOCATION_DEFAULT_LOCATIONS_PER_PAGE);
        set_option('geolocation_add_map_to_contribution_form', '1');
        set_option('geolocation_use_metric_distances', '1');        
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
        
        // This is for older versions of Geolocation, which used to store a Google Map API key.
        delete_option('geolocation_gmaps_key');
        
        // Drop the Location table
        $db = get_db();
        $db->query("DROP TABLE IF EXISTS `$db->Location`");        
    }
    
    public function hookConfigForm()
    {
        // If necessary, upgrade the plugin options
        // Check for old plugin options, and if necessary, transfer to new options
        $options = array('default_latitude', 'default_longitude', 'default_zoom_level', 'per_page');
        foreach($options as $option) {
            $oldOptionValue = get_option('geo_' . $option);
            if ($oldOptionValue != '') {
                set_option('geolocation_' . $option, $oldOptionValue);
                delete_option('geo_' . $option);
            }
        }
        delete_option('geo_gmaps_key');        
        include 'config_form.php';        
    }
    
    public function hookConfig($args)
    {
        // Use the form to set a bunch of default options in the db
        set_option('geolocation_default_latitude', $_POST['default_latitude']);
        set_option('geolocation_default_longitude', $_POST['default_longitude']);
        set_option('geolocation_default_zoom_level', $_POST['default_zoomlevel']);
        set_option('geolocation_item_map_width', $_POST['item_map_width']);
        set_option('geolocation_item_map_height', $_POST['item_map_height']);
        $perPage = (int)$_POST['per_page'];
        if ($perPage <= 0) {
            $perPage = GEOLOCATION_DEFAULT_LOCATIONS_PER_PAGE;
        } else if ($perPage > GEOLOCATION_MAX_LOCATIONS_PER_PAGE) {
            $perPage = GEOLOCATION_MAX_LOCATIONS_PER_PAGE;
        }
        set_option('geolocation_per_page', $perPage);
        set_option('geolocation_add_map_to_contribution_form', $_POST['geolocation_add_map_to_contribution_form']);
        set_option('geolocation_link_to_nav', $_POST['geolocation_link_to_nav']);     
        set_option('geolocation_use_metric_distances', $_POST['geolocation_use_metric_distances']);  
    }
    
    public function hookDefineAcl($args)
    {   
        $acl = $args['acl'];
        $acl->allow(null, 'Items', 'modifyPerPage');        
    }
    
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];
        $mapRoute = new Zend_Controller_Router_Route('items/map/:page',
                        array('controller' => 'map',
                                'action'     => 'browse',
                                'module'     => 'geolocation',
                                'page'       => '1'),
                        array('page' => '\d+'));
        $router->addRoute('items_map', $mapRoute);
        
        // Trying to make the route look like a KML file so google will eat it.
        // @todo Include page parameter if this works.
        $kmlRoute = new Zend_Controller_Router_Route_Regex('geolocation/map\.kml',
                        array('controller' => 'map',
                                'action' => 'browse',
                                'module' => 'geolocation',
                                'output' => 'kml'));
        $router->addRoute('map_kml', $kmlRoute);        
    }
    
    public function hookAfterSaveItem($args)
    {
        $post = $_POST;
        $item = $args['record'];   
        // If we don't have the geolocation form on the page, don't do anything!
        if (!$post['geolocation']) {
            return;
        }
        
        // Find the location object for the item
        $location = $this->_db->getTable('Location')->findLocationByItem($item, true);

        
        // If we have filled out info for the geolocation, then submit to the db
        $geolocationPost = $post['geolocation'];
        if (!empty($geolocationPost) &&
                        (((string)$geolocationPost['latitude']) != '') &&
                        (((string)$geolocationPost['longitude']) != '')) {
            if (!$location) {
                $location = new Location;
                $location->item_id = $item->id;
            }
            $location->setPostData($geolocationPost);
            $location->save();
            // If the form is empty, then we want to delete whatever location is
            // currently stored
        } else {
            if ($location) {
                $location->delete();
            }
        }        
    }
    
    public function hookAdminItemsShowSidebar($args)
    {
        $view = $args['view'];
        $item = $args['item'];
        $location = $this->_db->getTable('Location')->findLocationByItem($item, true);

        if ($location) {
            $html = '';
            $html .= "<div id='geolocation' class='info-panel panel'>";
            $html .= $view->itemGoogleMap($item, '224px', '270px' );
            $html .= "</div>";
            echo $html;
        }        
    }
    
    public function hookPublicHead($args)
    {
        $view = $args['view'];
        $view->addHelperPath(GEOLOCATION_PLUGIN_DIR . '/helpers', 'Geolocation_View_Helper_');
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        if ( ($module == 'geolocation' && $controller == 'map')
                        || ($module == 'contribution' 
                            && $controller == 'contribution' 
                            && $action == 'contribute' 
                            && get_option('geolocation_add_map_to_contribution_form') == '1')
                         || ($controller == 'items') )  {
            queue_css_file('geolocation-items-map');
            queue_css_file('geolocation-marker');
            queue_js_url("http://maps.google.com/maps/api/js?sensor=false");
            queue_js_file('map');
        }        
    }
    
    public function hookPublicItemsShow($args)
    {
        $view = $args['view'];
        $item = $args['item'];
        $location = $this->_db->getTable('Location')->findLocationByItem($item, true);

        if ($location) {
            $width = get_option('geolocation_item_map_width') ? get_option('geolocation_item_map_width') : '';
            $height = get_option('geolocation_item_map_height') ? get_option('geolocation_item_map_height') : '300px';            
            $html = "<div id='geolocation'>";
            $html .= '<h2>Geolocation</h2>';
            $html .= $view->itemGoogleMap($item, $width, $height);
            $html .= "</div>";
            echo $html;
        }
    }
    
    public function hookAdminItemsSearch($args)
    {
        // Get the request object
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $view = $args['view'];
        if ($request->getControllerName() == 'map' && $request->getActionName() == 'browse') {
            echo $view->partial('advanced-search-partial.php', array('searchFormId'=>'search', 'searchButtonId'=>'submit_search_advanced'));
        } else if ($request->getControllerName() == 'items' && $request->getActionName() == 'advanced-search') {
            echo $view->partial('advanced-search-partial.php', array('searchFormId'=>'advanced-search-form', 'searchButtonId'=>'submit_search_advanced'));
        }
    }
    
    public function hookPublicItemsSearch($args)
    {
        $view = $args['view'];                
        echo $view->partial('advanced-search-partial.php', array('searchFormId'=>'advanced-search-form', 'searchButtonId'=>'submit_search_advanced'));
    }
    
    public function hookItemsBrowseSql($args)
    {
        $db = $this->_db;
        $select = $args['select'];
        $alias = $this->_db->getTable('Location')->getTableAlias();
        if(isset($args['params']['geolocation-address'])) {
            
            
            // Get the address, latitude, longitude, and the radius from parameters
            $address = trim($args['params']['geolocation-address']);
            $currentLat = trim($args['params']['geolocation-latitude']);
            $currentLng = trim($args['params']['geolocation-longitude']);
            $radius = trim($args['params']['geolocation-radius']);
        
            
            if ( (isset($args['params']['only_map_items']) && $args['params']['only_map_items'] ) || $address != '') {
                //INNER JOIN the locations table

                $select->joinInner(array($alias => $db->Location), "$alias.item_id = items.id",
                            array('latitude', 'longitude', 'address'));                    
            }
            // Limit items to those that exist within a geographic radius if an address and radius are provided
            if ($address != '' && is_numeric($currentLat) && is_numeric($currentLng) && is_numeric($radius)) {
                // SELECT distance based upon haversine forumula
                $select->columns('3956 * 2 * ASIN(SQRT(  POWER(SIN(('.$currentLat.' - locations.latitude) * pi()/180 / 2), 2) + COS('.$currentLat.' * pi()/180) *  COS(locations.latitude * pi()/180) *  POWER(SIN(('.$currentLng.' -locations.longitude) * pi()/180 / 2), 2)  )) as distance');
                // WHERE the distance is within radius miles/kilometers of the specified lat & long
                if (get_option('geolocation_use_metric_distances')) {
                    $denominator = 111;
                } else {
                    $denominator = 69;
                }
                
                $select->where('(latitude BETWEEN '.$currentLat.' - ' . $radius . '/'.$denominator.' AND ' . $currentLat . ' + ' . $radius .  '/'.$denominator.')
             AND (longitude BETWEEN ' . $currentLng . ' - ' . $radius . '/'.$denominator.' AND ' . $currentLng  . ' + ' . $radius .  '/'.$denominator.')');
                //ORDER by the closest distances
                $select->order('distance');
            }
        } else if( isset($args['params']['only_map_items'])) {
            
            $select->joinInner(array($alias => $db->Location), "$alias.item_id = items.id",
                    array());            
        }
        
    }
    
    /**
     * Add the translations.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }
        
    public function filterAdminNavigationMain($navArray)
    {
        $navArray['Geolocation'] = array('label'=>__('Map'), 'uri'=>url('geolocation/map/browse'));
        return $navArray;        
    }
    
    public function filterPublicNavigationMain($navArray)
    {
        $navArray['Geolocation'] = array('label'=>__('Map'), 'uri'=>url('geolocation/map/browse'));
        return $navArray;        
    }
    
    public function filterResponseContexts($contexts)
    {
        $contexts['kml'] = array('suffix'  => 'kml',
                'headers' => array('Content-Type' => 'text/xml'));
        return $contexts;        
    }
    
    public function filterActionContexts($contexts, $args)
    {
        $controller = $args['controller'];
        if ($controller instanceof Geolocation_MapController) {
            $contexts['browse'] = array('kml');
        }
        return $contexts;        
    }
    
    public function filterAdminItemsFormTabs($tabs, $args)
    {
        // insert the map tab before the Miscellaneous tab
        $item = $args['item'];
        $tabs[__('Map')] = $this->_mapForm($item);
        
        return $tabs;     
    }
    
    public function filterPublicNavigationItems($navArray)
    {
        if (get_option('geolocation_link_to_nav')) {
            $navArray['Browse Map'] = array(
                                            'label'=>__('Browse Map'),
                                            'uri' => url('items/map') 
                                            );
        }
        return $navArray;        
    }     
    
    public function hookContributionTypeForm($args)
    {
        $contributionType = $args['type'];
        echo $this->_mapForm(null, __('Find A Geographic Location For The ') . $contributionType->display_name . ':', false );
    }

    public function hookSaveContributionForm($args)
    {
        
    }
    
    /**
     * Returns the form code for geographically searching for items
     * @param Item $item
     * @param int $width
     * @param int $height
     * @return string
     **/    
    protected function _mapForm($item, $label = 'Find a Location by Address:', $confirmLocationChange = true,  $post = null)
    {
        $html = '';
        $label = __('Find a Location by Address:');
        $center = $this->_getCenter();
        $center['show'] = false;
        
        $location = $this->_db->getTable('Location')->findLocationByItem($item, true);
                
        if ($post === null) {
            $post = $_POST;
        }
        
        $usePost = !empty($post) && !empty($post['geolocation']);
        if ($usePost) {
            $lng  = (double) @$post['geolocation']['longitude'];
            $lat  = (double) @$post['geolocation']['latitude'];
            $zoom = (int) @$post['geolocation']['zoom_level'];
            $addr = @$post['geolocation']['address'];
        } else {
            if ($location) {
                $lng  = (double) $location['longitude'];
                $lat  = (double) $location['latitude'];
                $zoom = (int) $location['zoom_level'];
                $addr = $location['address'];
            } else {
                $lng = $lat = $zoom = $addr = '';
            }
        }
        
        $html .= '<div class="field">';
        $html .=     '<div id="location_form" class="two columns alpha">';
        $html .=         '<input type="hidden" name="geolocation[latitude]" value="' . $lat . '" />';
        $html .=         '<input type="hidden" name="geolocation[longitude]" value="' . $lng . '" />';
        $html .=         '<input type="hidden" name="geolocation[zoom_level]" value="' . $zoom . '" />';
        $html .=         '<input type="hidden" name="geolocation[map_type]" value="Google Maps v' . GOOGLE_MAPS_API_VERSION . '" />';
        $html .=         '<label>' . html_escape($label) . '</label>';
        $html .=     '</div>';
        $html .=     '<div class="inputs five columns omega">';
        $html .=          '<div class="input-block">';
        $html .=            '<input type="text" name="geolocation[address]" id="geolocation_address" value="' . $addr . '" class="textinput"/>';
        $html .=            '<button type="button" style="float:none;" name="geolocation_find_location_by_address" id="geolocation_find_location_by_address">'.__('Find').'</button>';        
        $html .=          '</div>';
        $html .=     '</div>';
        $html .= '</div>';
        $html .= '<div  id="omeka-map-form" style="width: 100%; height: 300px"></div>';
        
        
        $options = array();
        $options['form'] = array('id' => 'location_form',
                'posted' => $usePost);
        if ($location or $usePost) {
            $options['point'] = array('latitude' => $lat,
                    'longitude' => $lng,
                    'zoomLevel' => $zoom);
        }
        
        $options['confirmLocationChange'] = $confirmLocationChange;
        
        $center = js_escape($center);
        $options = js_escape($options);        

        $js = "var anOmekaMapForm = new OmekaMapForm(" . js_escape('omeka-map-form') . ", $center, $options);";
        $js .= "
            jQuery(document).bind('omeka:tabselected', function () {
                anOmekaMapForm.resize();
            });                        
        ";
        
        $html .= "<script type='text/javascript'>" . $js . "</script>";
        return $html;
    }
    
    protected function _getCenter()
    {
        return array(
                'latitude'=>  (double) get_option('geolocation_default_latitude'),
                'longitude'=> (double) get_option('geolocation_default_longitude'),
                'zoomLevel'=> (double) get_option('geolocation_default_zoom_level'));        
        
    }    
}