<?php
define('GEOLOCATION_PLUGIN_VERSION', '1.0rc4');

//Include the ActiveRecord model
require_once 'Location.php';

add_plugin_hook('initialize', 'geo_initialize');
add_plugin_hook('install', 'geo_install');
add_plugin_hook('config_form', 'geo_form');
add_plugin_hook('config', 'geo_config');

//We are going to add some object-instance hooks to demonstrate the power of the callback
$geo = new GeolocationPlugin;
add_plugin_hook('item_browse_sql', array($geo, 'locationSql'));

add_plugin_hook('after_save_item', 'geo_save_location');

add_plugin_hook('theme_header', 'geo_map_header');

add_plugin_hook('add_routes', 'geo_add_routes');
	
//Register $geo so that we can call it from the controller
Zend_Registry::set('geolocation', $geo);

/**
 * Output the script tags that include the GMaps JS from afar
 *
 * @return void
 **/
function geo_map_header()
{
	$key = get_option('geo_gmaps_key');
	
	if(!$key) return;
	
	$path = WEB_PLUGIN . DIRECTORY_SEPARATOR . 'Geolocation' . DIRECTORY_SEPARATOR .'map.js';
	?>
<script src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=<?php echo $key;?>" type="text/javascript"></script>
<script src="<?php echo $path;?>" type="text/javascript" charset="utf-8"></script>

<?php
}

function geo_add_routes($router)
{
    $mapRoute = new Zend_Controller_Router_Route('items/map/:page', array('controller'=>'map','action'=>'browse', 'page'=>1, 'module'=>'geolocation'), array('page'=>'\d+'));
    
	$router->addRoute('map_browse', $mapRoute);	
	
	add_data_feed('map-xml', 
	array(
		'access_uri'=>$mapRoute,
		'script_path'=> PLUGIN_DIR . '/Geolocation/xml/map/browse.php', 
		'mime_type'=>'application/xml'));
		
	//Create a feed that provides the custom XML for the map
    add_data_feed('map-xml',
    array(
        'access_uri'=>'map/show',
        'script_path'=> PLUGIN_DIR . '/Geolocation/xml/map/show.php',
        'mime_type'=>'application/xml'));		
}

function geo_initialize()
{
	//Maybe we need to upgrade the plugin.  check on it.
	$version = get_option('geolocation_plugin_version');
	
	//We need to make sure that our MapController has available the theme pages it needs
	add_theme_pages('admin', 'admin');
	add_theme_pages('public', 'public');
	add_controllers('controllers');	
	add_navigation('Map', 'items/map', 'archive');
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
 * Installer creates a 'locations' table, sets some default lat/lng/zoom and API key attributes in DB
 *
 * @return void
 **/
function geo_install()
{	
	$db = get_db();

	$db->exec("CREATE TABLE IF NOT EXISTS $db->Location (
		`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`item_id` BIGINT UNSIGNED NOT NULL ,
		`latitude` DOUBLE NOT NULL ,
		`longitude` DOUBLE NOT NULL ,
		`zoom_level` INT NOT NULL ,
		`map_type` VARCHAR( 255 ) NOT NULL ,
		`address` TEXT NOT NULL ,
		INDEX ( `item_id` )
		) ENGINE = MYISAM");
		
	set_option('geo_plugin_version', GEOLOCATION_PLUGIN_VERSION);	
}

add_plugin_hook('append_to_item_form', 'map_form');
add_plugin_hook('append_to_item_show', 'map_for_item');

/**
 * Each time we save an item, check the POST to see if we are also saving a location
 *
 * @return void
 **/
function geo_save_location($item)
{	
	$post = $_POST;	
	$geo_post = $post['geolocation'][0];
		
	//Find the ActiveRecord location object
	$location = get_db()->getTable('Location')->findLocationByItem($item);
					
	//If we have filled out info for the geolocation, then submit to the db
	if(!empty($geo_post) and 
		(!empty($geo_post['latitude']) and !empty($geo_post['longitude']))) {
		
		if(!$location) {
			$location = new Location;
			$location->item_id = $item->id;						
		}
		
  		if( $location->saveForm($geo_post) ) {
			return true;
		}
	}		
	//If the form is empty, then we want to delete whatever location is currently stored
	else {
		if($location) {
			$location->delete();
		}
	}	
}

/**
 * 
 *
 * @package Omeka
 * 
 **/
class GeolocationPlugin
{	
	/**
	 * Let the plugin know that it should throw some hooks into the items browsing to 
	 * filter out items based whether or not they have locations associated with them
	 *
	 * @return void
	 **/
	public function setMapDisplay($bool=true)
	{
		$this->displayMap = true;
	}
	
	/**
	 * Plugin must be able to add SQL to the master query that pulls down a list of items
	 *
	 * @return void
	 **/
	public function locationSql($select)
	{
		if($this->displayMap) {
			$db = get_db();
			//INNER JOIN the locations table
			$select->innerJoin("$db->Location l", 'l.item_id = i.id');
		}
	}
	
} // END class Geolocation


/**
 * Return a multidimensional array of location info
 *
 * @param array|int $item_id
 * @return array
 **/
function get_location_for_item($item_id)
{
	return get_db()->getTable('Location')->findLocationByItem($item_id);
}

/**
 *  Possible options include:
 *		form = 'geolocation'  (provides the prefix for form elements that should catch the map coordinates)
 *		 
 * 
 **/
function google_map($divName = 'map', $options = array()) {
	echo "<div id=\"$divName\"></div>";
	//Load this junk in from the plugin config

	$lat = (double) get_option('geo_default_latitude');
	$lng = (double) get_option('geo_default_longitude');
	$zoom = (double) get_option('geo_default_zoom_level');
	
	$options['default']['latitude'] = $lat;
	$options['default']['longitude'] = $lng;
	$options['default']['zoomLevel'] = $zoom;
	
	//Load the Key into the plugin config
	//$options['api_key'] = $plugin->getConfig('Google Maps API Key');

	//The request parameters get put into the map options
	$params = array();
	if(!isset($options['params'])) {
		$params = $_GET;
	}else {
		$params = array_merge($options['params'], $_GET);
	}
	
	//Append the 'rest' parameter to signify that we want to return XML		
	$params['output'] = 'map-xml';
	$options['params'] = $params;	

	require_once 'Zend/Json.php';
	$options = Zend_Json::encode($options);
	
	echo "<script>var ${divName}Omeka = new OmekaMap('$divName', $options);</script>";
}

function map_for_item($item, $width=200, $height=200) {		
?>
	<style type="text/css" media="screen">
		/* The map for the items page needs a bit of styling on it */
		
		#address_balloon dt {
			font-weight: bold;
		}
		
		#address_balloon {
			width: 100px;
		}
		
	</style>

<?php	
	google_map('item_map' . $item->id, 
		array(
			'uri'=>uri('map/show'),
			'params'=>array('id'=>$item->id), 
			'type'=>'show', 
			'width'=>$width,
			'height'=>$height));
}

function map_pagination() {
	return pagination_links(
	5, null,null,null,null, uri('items/map/') );
}

function map_form($item, $width=400, $height=400) { 
		$loc = array_pop(get_location_for_item($item));
		
		$usePost = !empty($_POST);
	
		if($usePost) {
			$lng = (double) @$_POST['geolocation'][0]['longitude'];
			$lat =  (double) @$_POST['geolocation'][0]['latitude'];
			$zoom = (int) @$_POST['geolocation'][0]['zoom_level'];
			$addr = @$_POST['geolocation'][0]['address'];
		}else {
			$lng = (double) $loc['longitude'];
			$lat = (double) $loc['latitude'];
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
	</style>
	
	<fieldset id="location_form">
		<input type="hidden" name="geolocation[0][latitude]" value="<?php echo $lat; ?>" />
		<input type="hidden" name="geolocation[0][longitude]" value="<?php echo $lng; ?>" />
		<input type="hidden" name="geolocation[0][zoom_level]" value="<?php echo $zoom; ?>" />
		<input type="hidden" name="geolocation[0][map_type]" value="Google Maps V2" />
		
		<label>Find Your location via address:</label>
		<input type="text" name="geolocation[0][address]" id="geolocation_address" size="60" value="<?php echo $addr; ?>" />
		<input type="submit" name="find_location_by_address" id="find_location_by_address" value="Find By Address" />
	</fieldset>
	
	<?php 
	$options = array();
	
	if($lng and $lat) {
		//B/c of changes in map via POST, we should always pass the form the map parameters manually
		$options['point'] = array('lng'=>$lng, 'lat'=>$lat, 'zoom'=>$zoom);			
	}
	
	$options['type'] = 'form';
	$options['width'] = $width;
	$options['height'] = $height;
	
	google_map('item_form', $options);
 } 
?>
