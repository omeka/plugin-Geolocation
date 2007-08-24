<?php
//Include the ActiveRecord model
require_once 'Location.php';
/**
 * 
 *
 * @package Omeka
 * 
 **/
class Geolocation extends Kea_Plugin
{	
	protected $metaInfo = array(
			'description'=>'Uses the Google Maps API to allow Items to be associated with a geographical location.',
			'author'=>'Center for History & New Media');
	
	public function definition() {
		$this->hasConfig('Default Latitude', 'The default latitude for the map.', 50);
		$this->hasConfig('Default Longitude', 'The default longitude for the map.', 70);
		$this->hasConfig('Default ZoomLevel', 'The default zoom level for the map.', 5);
		$this->hasConfig('Google Maps API Key', 'The API key (plugin will not work properly without this).');
	}
	
	/**
	 * Create the `locations` table that will store all the map locations
	 *
	 * @return void
	 **/
	public function customInstall()
	{
		//Create the locations table
		$this->getDbConn()->execute("CREATE TABLE `locations` (
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`item_id` BIGINT UNSIGNED NOT NULL ,
			`latitude` DOUBLE NOT NULL ,
			`longitude` DOUBLE NOT NULL ,
			`zipcode` INT NOT NULL ,
			`zoom_level` INT NOT NULL ,
			`map_type` VARCHAR( 255 ) NOT NULL ,
			`address` TEXT NOT NULL ,
			INDEX ( `item_id` )
			) ENGINE = MYISAM ;");
	}

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
	public function filterBrowse($select, $recordType)
	{
		if( ($recordType == 'Item') and $this->displayMap) {
			
			//INNER JOIN the locations table
			$select->innerJoin(array('Location', 'l'), 'l.item_id = i.id');
		}
	}
	
	public function onCommitForm($record, $post)
	{
		switch (get_class($record)) {
			case 'Item':
				
				$geo_post = $post['geolocation'][0];
				
				//If we have filled out info for the geolocation, then submit to the db
				if(!empty($geo_post) and !empty($geo_post['latitude']) and !empty($geo_post['longitude'])) {
					
					//Find the ActiveRecord location object
					$location = Doctrine_Manager::getInstance()->getTable('Location')->findLocationByItem($record);
					
					if(!$location) {
						$location = new Location;
						$location->item_id = $record->id;
					}
					
					if( $location->commitForm($geo_post) ) {
						return true;
					}
				}
				
				break;
			
			default:
				break;
		}
		
	}
	
	/**
	 * Append map data to relevant theme pages
	 *
	 * @return void
	 **/
	public function appendToPage($page, $options) 
	{
		//This should pull in the $items var
		extract($options);
		
		switch ($page) {
			case 'items/form':
				map_form($item);
				break;
			
			case 'items/show':
				map_for_item($item);
				break;
				
			default:
				# code...
				break;
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
	$select = new Kea_Select;
	$select->from(array('Location', 'l'), 'l.*');
	
	$item_id = ($item_id instanceof Item) ? $item_id->id : $item_id;
	
	//Create a WHERE condition that will pull down all the location info
	if(count($item_id) > 1 or ($item_id instanceof Doctrine_Collection)) {
		
		//Loop through a collection of ActiveRecord items
		if($item_id instanceof Doctrine_Collection) {
			foreach ($item_id as $item) {
				$select->orWhere('l.item_id = ?', $item->id);
			}
		}
		//Loop through an array of item IDs
		else {
			foreach ($item_id as $id) {
				$select->orWhere('l.item_id = ?', $id);
			}			
		}
		

	}else {
		$select->where('l.item_id = ?', $item_id);
	}
		
	//Fetch the data
	$array = $select->fetchAll();
	
	
	//Now process into an array where the key is the item_id		
	$locations = array();	
	foreach ($array as $k => $row) {
		$locations[$row['item_id']] = $row;
	}	
			
	return $locations;
}

	/**
	 *  Possible options include:
	 *		clickable = true  (makes the map clickable)
	 *		form = 'geolocation'  (provides the prefix for form elements that should catch the map coordinates)
	 *		 
	 * 
	 **/
	function google_map($width, $height, $divName = 'map', $options = array()) {
		echo "<div id=\"$divName\"></div>";
		//Load this junk in from the plugin config

		$plugin = Zend::Registry( 'Geolocation' );
		$options['default']['latitude'] = $plugin->getConfig('Default Latitude');
		$options['default']['longitude'] = $plugin->getConfig('Default Longitude');
		$options['default']['zoomLevel'] = $plugin->getConfig('Default ZoomLevel');
		
		//Load the Key into the plugin config
		//$options['api_key'] = $plugin->getConfig('Google Maps API Key');
		
		$options['width'] = $width;
		$options['height'] = $height;
		
		//Right now there are only 2 URLs that can pull in map data so it makes no sense to allow other arbitrary settings
		switch ($options['uri']) {
			case 'browse':
				$options['uri'] = array();
				$options['uri']['href'] = uri('map/browse');
				$options['uri']['type'] = 'browse';
				break;
			case 'show':
				$options['uri'] = array();
				$options['uri']['href'] = uri('map/show');
				$options['uri']['type'] = 'show';
				break;
			default:
				throw new Exception( 'URI option is required!' );
				break;
		}
		
		//The request parameters get put into the map options
		$params = array();
		if(!isset($options['params'])) {
			$params = $_GET;
		}else {
			$params = array_merge($options['params'], $_GET);
		}
		
		//Append the 'rest' parameter to signify that we want to return XML		
		$params['output'] = 'rest';
		$options['uri']['params'] = $params;	

		require_once 'Zend/Json.php';
		$options = Zend_Json::encode($options);
		echo "<script>var ${divName}Omeka = new OmekaMap('$divName', $options);</script>";
	}
	
	function map_for_item($item, $width=200, $height=200) {		
		google_map($width, $height, 'item_map', array('uri'=>'show','params'=>array('id'=>$item->id)));
	}
	
	function map_form($item, $width=400, $height=400) { ?>
		<?php 
			$loc = array_pop(get_location_for_item($item));
			
			$longitude = $loc['longitude'] or $longitude = @$_POST['geolocation'][0]['longitude'];
			
			$latitude = $loc['latitude'] or $latitude = @$_POST['geolocation'][0]['latitude'];
			
			$zoom_level = $loc['zoom_level'] or $zoom_level = @$_POST['geolocation'][0]['zoom_level'];
			
			$address = $loc['address'] or $address = @$_POST['geolocation'][0]['address'];
		?>
		
		<fieldset id="location_form">
			<input type="hidden" name="geolocation[0][latitude]" value="<?php echo $latitude; ?>" />
			<input type="hidden" name="geolocation[0][longitude]" value="<?php echo $longitude; ?>" />
			<input type="hidden" name="geolocation[0][zoom_level]" value="<?php echo $zoom_level; ?>" />
			
			<label>Find Your location via address:</label>
			<input type="text" name="geolocation[0][address]" id="geolocation_address" value="<?php echo $address; ?>" />
			<input type="submit" name="find_location_by_address" id="find_location_by_address" value="Find By Address" />
		</fieldset>
		
		<?php 
		google_map($width, $height, 'item_form', array(
			'clickable'=>true, 
			'form'=>'geolocation', 
			'uri'=>'show',
			'params'=>array('id'=>$item->id)));
	 } 
?>
