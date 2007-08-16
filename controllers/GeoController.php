<?php

require_once 'Kea/Controller/Action.php';

class GeoController extends Kea_Controller_Action
{
    public function indexAction()
    {
		echo 'Geo!';
    }

    public function noRouteAction()
    {
        $this->_redirect('/');
    }

	public function browseAction()
	{
		
		//OK This will fake a request to the ItemsController
		$req = clone $this->getRequest();
		$req->setControllerName('items');
		
		//Tell the plugin that it should filter the SQL in the items browse
		Zend::Registry( 'Geolocation' )->setMapDisplay(true);
		
		require_once CONTROLLER_DIR.DIRECTORY_SEPARATOR.'ItemsController.php';
		
		$itemController = new ItemsController($req, $this->getResponse(), array('return'=>'items'));
		
		//Retrieve the items from that shit
		$items = $itemController->browseAction();
				
				
								
		$locations = get_location_for_item($items);
		
		$this->render('map/browse.php', compact('items', 'locations'));
	}
/*
		
	public function showAction()
	{
		//Another fake request to the ItemsController
		$req = clone $this->getRequest();
		$req->setControllerName('items');
		
		require_once 'file';
	}
*/	
}

?>