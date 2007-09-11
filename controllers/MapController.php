<?php

require_once 'Kea/Controller/Action.php';

class MapController extends Kea_Controller_Action
{
    public function noRouteAction()
    {
        $this->_redirect('/');
    }

	public function browseAction()
	{
		
		//Tell the plugin that it should filter the SQL in the items browse
		Zend::Registry( 'geolocation' )->setMapDisplay(true);
				
		$c = $this->getController('items');
		
		//Retrieve the items from that shit
		$items = $c->browseAction();
								
		$locations = get_location_for_item($items);
			
		$this->render('map/browse.php', compact('items', 'locations'));
	}
	
	protected function getController($return) 
	{
		//Fake a request to the items controller
		$req = clone $this->getRequest();
		$req->setControllerName('items');
		
		$req->setParam('controller', 'items');
		
		require_once CONTROLLER_DIR.DIRECTORY_SEPARATOR.'ItemsController.php';
		
		$itemController = new ItemsController($req, $this->getResponse(), array('return'=>$return));
		
		return $itemController;		
	}
	
	public function showAction()
	{
		if(!$this->_getParam('id')) {
			echo "<item></item>";return;
		}
		//This needs to piggyback off of the permissions checks for items		
		
		$itemController = $this->getController('item');
		
		$item = $itemController->showAction();
		
		$locations = get_location_for_item($item->id);
				
		$location = $locations[$item->id];	
		
		$has_location = !$location ? false : true;
			
		$this->render('map/show.php', compact('item','location', 'has_location'));
	}

}?>