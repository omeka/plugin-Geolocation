<?php
define('GEOLOCATION_PLUGIN_VERSION', '1.0.2');
define('GOOGLE_MAPS_API_VERSION', '2.x');

require_once 'Location.php';

// Plugin Hooks
add_plugin_hook('install', 'geolocation_install');
add_plugin_hook('uninstall', 'geolocation_uninstall');
add_plugin_hook('config_form', 'geolocation_config_form');
add_plugin_hook('config', 'geolocation_config');
add_plugin_hook('add_routes', 'geolocation_add_routes');
add_plugin_hook('item_browse_sql', 'geolocation_location_sql');
add_plugin_hook('after_save_item', 'geolocation_save_location');
add_plugin_hook('append_to_item_form', 'geolocation_map_form');
add_plugin_hook('admin_append_to_items_show_secondary', 'geolocation_admin_map_for_item');

// Plugin Filters
add_filter('admin_navigation_main', 'geolocation_admin_nav');
add_filter('define_response_contexts', 'kml_response_context');
add_filter('define_action_contexts', 'kml_action_context');

// Hook Functions
function geolocation_install()
{    
    $db = get_db();
    $sql = "
    CREATE TABLE IF NOT EXISTS $db->Location (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `item_id` BIGINT UNSIGNED NOT NULL ,
    `latitude` DOUBLE NOT NULL ,
    `longitude` DOUBLE NOT NULL ,
    `zoom_level` INT NOT NULL ,
    `map_type` VARCHAR( 255 ) NOT NULL ,
    `address` TEXT NOT NULL ,
    INDEX (`item_id`)) ENGINE = MYISAM";
    $db->query($sql);
    set_option('geolocation_plugin_version', GEOLOCATION_PLUGIN_VERSION);    
}

function geolocation_uninstall()
{
	delete_option('geolocation_plugin_version');
	delete_option('geolocation_gmaps_key');
	delete_option('geolocation_default_latitude');
	delete_option('geolocation_default_longitude');
	delete_option('geolocation_default_zoom_level');
	delete_option('geolocation_per_page');

	$db = get_db();
	$db->query("DROP TABLE $db->Location");
}

function geolocation_config_form()
{
	include 'config_form.php';
}

function geolocation_config()
{
    //Use the form to set a bunch of default options in the db
    set_option('geolocation_gmaps_key', $_POST['map_key']);
    set_option('geolocation_default_latitude', $_POST['default_latitude']);
    set_option('geolocation_default_longitude', $_POST['default_longitude']);
    set_option('geolocation_default_zoom_level', $_POST['default_zoomlevel']); 
    set_option('geolocation_per_page', $_POST['per_page']);
}

/**
 * Plugin hook that can manipulate Omeka's routes to allow for new URIs to 
 * access data
 * Currently does the following things:
 *     matches up the URI items/map/:page with MapController::browseAction()
 * Adds a couple of data feeds to render XML for the map (these pages are in the 
 * xml/ directory)
 * @see Zend_Controller_Router_Rewrite
 * @param $router
 * @return void
 **/
function geolocation_add_routes($router)
{
    $mapRoute = new Zend_Controller_Router_Route('items/map/:page', 
                                                 array('controller' => 'map', 
                                                       'action'     => 'browse', 
                                                       'module'     => 'geolocation'), 
                                                 array('page' => '\d+'));
    $router->addRoute('items_map', $mapRoute);    
    
    // @hack Have to have a basic route for this b/c it is being overridden by 
    // the 'page' route in routes.ini
    $mapRoute2 = new Zend_Controller_Router_Route('map/browse', 
                                                  array('controller' => 'map', 
                                                        'action'     => 'browse'));
    $router->addRoute('map_browse', $mapRoute2);
    
    // Trying to make the route look like a KML file so google will eat it.
    // @todo Include page parameter if this works.
    $kmlRoute = new Zend_Controller_Router_Route_Regex('geolocation/map\.kml', 
                                                array('controller' => 'map',
                                                    'action' => 'browse',
                                                    'module' => 'geolocation',
                                                    'output' => 'kml'));
    $router->addRoute('map_kml', $kmlRoute);
}


function geolocation_location_sql()
{
	$geo = new GeolocationPlugin;
	// Register $geo so that we can call it from the controller
	Zend_Registry::set('geolocation', $geo);
	
	$array = array($geo, 'locationSql');
	
	return $array;
}

/**
 * Each time we save an item, check the POST to see if we are also saving a 
 * location
 * @return void
 **/
function geolocation_save_location($item)
{
    $post = $_POST;    
    // If we don't have the geolocation form on the page, don't do anything!
    if (!$post['geolocation']) {
        return;
    }
    
    $geolocation_post = $post['geolocation'][0];
  
    // Find the ActiveRecord location object
    $location = get_db()->getTable('Location')->findLocationByItem($item, true);
                    
    // If we have filled out info for the geolocation, then submit to the db
    if (!empty($geolocation_post) 
        && (!empty($geolocation_post['latitude']) 
            && !empty($geolocation_post['longitude']))) {
        if (!$location) {
            $location = new Location;
            $location->item_id = $item->id;  
             
        }
        if ($location->saveForm($geolocation_post) ) {
            return true;
        }
    // If the form is empty, then we want to delete whatever location is 
    // currently stored
    } else {
        if ($location) {
            $location->delete();
        }
    }
}

// Filter Functions
function geolocation_admin_nav($navArray)
{
    $geoNav = array('Map' => uri('geolocation/map/browse'));

    $navArray += $geoNav;
    return $navArray;
}

function kml_response_context($context)
{
    $context['kml'] = array('suffix'  => 'kml', 
                            'headers' => array('Content-Type' => 'application/vnd.google-earth.kml+xml'));
    return $context;
}

function kml_action_context($context, $controller)
{
    if ($controller instanceof Geolocation_MapController) {
        $context['browse'] = array('kml');
    }
    return $context;
}
    

// Helpers

/**
 * Output the script tags that include the GMaps JS from afar
 * @return void
 **/
function geolocation_scripts()
{
    $key = get_option('geolocation_gmaps_key');
    
    if (!$key) {
        return;
    }
    
?>
<script src="http://maps.google.com/maps?file=api&amp;v=<?php echo GOOGLE_MAPS_API_VERSION; ?>&amp;key=<?php echo $key;?>" type="text/javascript"></script>
<?php
echo js('map');
}

class GeolocationPlugin
{    
    /**
     * Let the plugin know that it should throw some hooks into the items 
     * browsing to filter out items based whether or not they have locations 
     * associated with them
     * @return void
     **/
    public function setMapDisplay($bool = true)
    {
        $this->displayMap = true;
    }
    
    /**
     * Plugin must be able to add SQL to the master query that pulls down a list 
     * of items
     * @return void
     **/
    public function locationSql($select)
    {
        if ($this->displayMap) {
            $db = get_db();
            //INNER JOIN the locations table
            $select->joinInner(array('l' => $db->Location), 'l.item_id = i.id');
        }
    }
}


/**
 * Return a multidimensional array of location info
 * @param array|int $item_id
 * @return array
 **/
function get_location_for_item($item_id)
{
    return get_db()->getTable('Location')->findLocationByItem($item_id);
}

/**
 * Possible options include:
 *     form = 'geolocation'  (provides the prefix for form elements that should 
 *     catch the map coordinates)
 **/
function geolocation_google_map($divName = 'map', $options = array()) {
    
    echo "<div id=\"$divName\" class=\"map\"></div>";
    
    //Load this junk in from the plugin config
    if (!isset($options['center'])) {
        $lat  = (double) get_option('geolocation_default_latitude');
        $lng  = (double) get_option('geolocation_default_longitude');
        $zoom = (double) get_option('geolocation_default_zoom_level');
        $options['center']['latitude']  = $lat;
        $options['center']['longitude'] = $lng;
        $options['center']['zoomLevel'] = $zoom;
    }
    
    //Load the Key into the plugin config
    //$options['api_key'] = $plugin->getConfig('Google Maps API Key');
    
    //The request parameters get put into the map options
    $params = array();
    if (!isset($options['params'])) {
        $params = array();
    }
    $params = array_merge($params, $_GET);
    
    if ($options['loadKml']) {
        unset($options['loadKml']);
        // This should not be a link to the public side b/c then all the URLs that
        // are generated inside the KML will also link to the public side.
        $options['uri'] = uri('geolocation/map.kml');
    }
    
    //Merge in extra parameters from the controller
    if (Zend_Registry::isRegistered('map_params')) {
        $params = array_merge($params, Zend_Registry::get('map_params'));
    }
        
    //We are using KML as the output format
    $options['params'] = $params;    
        
    require_once 'Zend/Json.php';
    $options = Zend_Json::encode($options);
    
    echo "<script type=\"text/javascript\">var ${divName}Omeka = new OmekaMap('$divName', $options);</script>";
}

function geolocation_map_for_item($item, $width = 200, $height = 200) {        
    geolocation_scripts(); 
    $divId = 'item_map' . $item->id;
?>
<style type="text/css" media="screen">
    /* The map for the items page needs a bit of styling on it */
    #address_balloon dt {
        font-weight: bold;
    }
    #address_balloon {
        width: 100px;
    }
    #<?php echo $divId;?> {
        width: <?php echo $width; ?>px;
        height: <?php echo $height; ?>px;
    }
    div.map-notification {
        width: <?php echo $width; ?>px;
        height: <?php echo $height; ?>px;
        display:block;
        border: 1px dotted #ccc;
        text-align:center;
        font-size: 2em;
    }
</style>
<h2>Geolocation</h2>
<?php    
    //Options for google_maps helper
    $options = array(
            // We don't really need to load the KML for a single item since it's 
            // just hardcoded in the JSON
            //'uri' => uri('map/browse'),
            //'params' => array('id'=>$item->id)
            'size' => 'small');
    
    $location = current(get_location_for_item($item));
    
    // Only set the center of the map if this item actually has a location 
    // associated with it
    if ($location) {
        $center['latitude']    = $location->latitude;
        $center['longitude']   = $location->longitude;
        $center['zoomLevel']   = $location->zoom_level;
        $options['center']     = $center;
        $options['showCenter'] = true;
        geolocation_google_map($divId, $options);
    } else {
        echo '<p class="map-notification">This item has no location info associated with it.</p>';
    } 
}

function geolocation_map_form($item, $width = 400, $height = 400) { 
    	geolocation_scripts();    
    	$loc = array_pop(get_location_for_item($item));
        $usePost = $_POST;
        if ($usePost) {
			echo $usePost;
            $lng  = (double) @$_POST['geolocation'][0]['longitude'];
            $lat  = (double) @$_POST['geolocation'][0]['latitude'];
            $zoom = (int) @$_POST['geolocation'][0]['zoom_level'];
            $addr = @$_POST['geolocation'][0]['address'];
        } else if ($loc) {
            $lng  = (double) $loc['longitude'];
            $lat  = (double) $loc['latitude'];
            $zoom = (int) $loc['zoom_level'];
            $addr = $loc['address'];
        }
?>
<style type="text/css" media="screen">
    /* Need a bit of styling for the geocoder balloon */
    #geocoder_balloon a{
        display:block;
        width:50%;
        float:left;
    }
    #item_form{
        width: <?php echo $width; ?>px;
        height: <?php echo $height; ?>px;
    }
</style>
<fieldset id="location_form">
    <input type="hidden" name="geolocation[0][latitude]" value="<?php echo $lat; ?>" />
    <input type="hidden" name="geolocation[0][longitude]" value="<?php echo $lng; ?>" />
    <input type="hidden" name="geolocation[0][zoom_level]" value="<?php echo $zoom; ?>" />
    <input type="hidden" name="geolocation[0][map_type]" value="Google Maps v<?php echo GOOGLE_MAPS_API_VERSION;  ?>" />
    <label>Find Your location via address:</label>
    <input type="text" name="geolocation[0][address]" id="geolocation_address" size="60" value="<?php echo $addr; ?>" />
    <button type="button" name="find_location_by_address" id="find_location_by_address">Find By Address</button>
</fieldset>
<?php 
    $options = array();
    
    $options['form'] = array('id' => 'location_form', 
                             'posted' => $usePost);
    
    
    if($usePost) {
        $options['form']['post'] = array('latitude'=>$lat, 'longitude'=>$lng, 'zoomLevel'=>$zoom);
    }
    
    if ($lng) {
        $options['center']['latitude']  = $lat;
        $options['center']['longitude'] = $lng;
        $options['center']['zoomLevel'] = $zoom;  
        $options['showCenter'] = true;      
    }
    geolocation_google_map('item_form', $options);
}

function geolocation_admin_map_for_item($item)
{
?>
<style type="text/css" media="screen">
	.info-panel .map {margin-top:-18px;display:block; margin-left:-18px; margin-bottom:0;border-top:3px solid #eae9db; padding:0;}
</style>
  <?php
	echo '<div class="info-panel">';
	geolocation_map_for_item($item,'224','270');
	echo '</div>';
} 
?>