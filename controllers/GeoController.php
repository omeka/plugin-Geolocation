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
	
	public function showAction()
	{
		if(!$this->_getParam('id')) {
			echo "<item></item>";return;
		}
		//This needs to piggyback off of the permissions checks for items
		
		//Another fake request to the ItemsController
		$req = clone $this->getRequest();
		$req->setControllerName('items');
		
		require_once CONTROLLER_DIR.DIRECTORY_SEPARATOR.'ItemsController.php';
		
		$itemController = new ItemsController($req, $this->getResponse(), array('return'=>'item'));
		
		$item = $itemController->showAction();
		
		$locations = get_location_for_item($item->id);
				
		$location = $locations[$item->id];		
		$this->render('map/show.php', compact('item','location'));
	}
/*
		
	public function showAction()
	{
		
		
		require_once 'file';
	}
*/	
}

?>