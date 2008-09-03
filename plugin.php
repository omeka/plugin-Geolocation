<?php
define('GEOLOCATION_PLUGIN_VERSION', '1.0.0');

// Add the controllers/, models/, and views/ directories.
add_plugin_directories();

require_once 'Location.php';

add_plugin_hook('install', 'geo_install');
add_plugin_hook('config_form', 'geo_form');
add_plugin_hook('config', 'geo_config');

$geo = new GeolocationPlugin;
add_plugin_hook('item_browse_sql', array($geo, 'locationSql'));
add_plugin_hook('after_save_item', 'geo_save_location');
add_plugin_hook('add_routes', 'geo_add_routes');
add_plugin_hook('append_to_item_form', 'map_form');
add_plugin_hook('append_to_item_show', 'map_for_item');
    
// Register $geo so that we can call it from the controller
Zend_Registry::set('geolocation', $geo);

add_filter('admin_navigation_main', 'geo_admin_nav');
function geo_admin_nav($navArray)
{
    $geoNav = array('Map'=> url_for('map'));
    $navArray += $geoNav;
    return $navArray;
}

/**
 * Output the script tags that include the GMaps JS from afar
 * @return void
 **/
function geolocation_scripts()
{
    $key = get_option('geo_gmaps_key');
    
    if (!$key) {
        return;
    }
    
?>
<script src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=<?php echo $key;?>" type="text/javascript"></script>
<?php
echo js('map');
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
function geo_add_routes($router)
{
    $mapRoute = new Zend_Controller_Router_Route('items/map/:page', 
                                                 array('controller' => 'map', 
                                                       'action'     => 'browse', 
                                                       'page'       => 1, 
                                                       'module'     => 'geolocation'), 
                                                 array('page' => '\d+'));
    $router->addRoute('items_map', $mapRoute);    
    
    // @hack Have to have a basic route for this b/c it is being overridden by 
    // the 'page' route in routes.ini
    $mapRoute2 = new Zend_Controller_Router_Route('map/browse', 
                                                  array('controller' => 'map', 
                                                        'action'     => 'browse'));
    $router->addRoute('map_browse', $mapRoute2);
    
    add_data_feed('kml', array('access_uri'  => 'map/browse', 
                               'script_path' => PLUGIN_DIR . '/Geolocation/kml/browse.php', 
                               'mime_type'   => 'application/vnd.google-earth.kml+xml'));                
}

function geo_form()
{
    include 'form.php';
}

function geo_config()
{
    //Use the form to set a bunch of default options in the db
    set_option('geo_gmaps_key', $_POST['map_key']);
    set_option('geo_default_latitude', $_POST['default_latitude']);
    set_option('geo_default_longitude', $_POST['default_longitude']);
    set_option('geo_default_zoom_level', $_POST['default_zoomlevel']);    
}

/**
 * Installer creates a 'locations' table, sets some default lat/lng/zoom and API 
 * key attributes in DB
 * @return void
 **/
function geo_install()
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
    $db->exec($sql);
    set_option('geo_plugin_version', GEOLOCATION_PLUGIN_VERSION);    
}

/**
 * Each time we save an item, check the POST to see if we are also saving a 
 * location
 * @return void
 **/
function geo_save_location($item)
{
    $post = $_POST;    
    
    // If we don't have the geolocation form on the page, don't do anything!
    if (!$post['geolocation']) {
        return;
    }
    
    $geo_post = $post['geolocation'][0];
        
    // Find the ActiveRecord location object
    $location = get_db()->getTable('Location')->findLocationByItem($item, true);
                    
    // If we have filled out info for the geolocation, then submit to the db
    if (!empty($geo_post) 
        && (!empty($geo_post['latitude']) 
            && !empty($geo_post['longitude']))) {
        if (!$location) {
            $location = new Location;
            $location->item_id = $item->id;                        
        }
        if ($location->saveForm($geo_post) ) {
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
function google_map($divName = 'map', $options = array()) {
    
    echo "<div id=\"$divName\"></div>";
    
    //Load this junk in from the plugin config
    if (!isset($options['center'])) {
        $lat  = (double) get_option('geo_default_latitude');
        $lng  = (double) get_option('geo_default_longitude');
        $zoom = (double) get_option('geo_default_zoom_level');
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
    
    //Merge in extra parameters from the controller
    if (Zend_Registry::isRegistered('map_params')) {
        $params = array_merge($params, Zend_Registry::get('map_params'));
    }
    $output['params'] = $params;
    
    //We are using KML as the output format
    $params['output'] = 'kml';
    $options['params'] = $params;    
    
    require_once 'Zend/Json.php';
    $options = Zend_Json::encode($options);
    
    echo "<script type=\"text/javascript\">var ${divName}Omeka = new OmekaMap('$divName', $options);</script>";
}

function map_for_item($item, $width = 200, $height = 200) {        
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
        google_map($divId, $options);
    } else {
        echo '<div class="map-notification"><br/><br/>This item has no location info associated with it.</div>';
    }
}

function map_form($item, $width = 400, $height = 400) { 
        $loc = array_pop(get_location_for_item($item));
        $usePost = !empty($_POST);
        if ($usePost) {
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
    <input type="hidden" name="geolocation[0][map_type]" value="Google Maps V2" />
    <label>Find Your location via address:</label>
    <input type="text" name="geolocation[0][address]" id="geolocation_address" size="60" value="<?php echo $addr; ?>" />
    <button type="button" name="find_location_by_address" id="find_location_by_address">Find By Address</button>
</fieldset>
<?php 
    $options = array();
    
    // If we are using the POST data, we don't need to re-retrieve the KML for 
    // the item's location
    if (!$usePost && $item->exists()) {
        $options['uri']    = uri('map/browse');
        $options['params'] = array('id' => $item->id);        
    }
    
    $options['form'] = array('id' => 'location_form', 
                             'posted' => $usePost);
    
    /*
    if($usePost) {
        $options['form']['post'] = array('latitude'=>$lat, 'longitude'=>$lng, 'zoomLevel'=>$zoom);
    }
    */  
    if ($lng) {
        $options['center']['latitude']  = $lat;
        $options['center']['longitude'] = $lng;
        $options['center']['zoomLevel'] = $zoom;        
    }
    google_map('item_form', $options);
 } 
?>
