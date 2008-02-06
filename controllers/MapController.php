<?php

require_once 'Omeka/Controller/Action.php';

class MapController extends Omeka_Controller_Action
{
    public function noRouteAction()
    {
        $this->_redirect('/');
    }

	public function browseAction()
	{
		//Tell the plugin that it should filter the SQL in the items browse
		Zend_Registry::get( 'geolocation' )->setMapDisplay(true);
				
		$c = $this->getController('items');
		
		//Retrieve the items from that shit
		$items = $c->browseAction();
								
		$locations = get_location_for_item($items);
		
		$pass_to_render = compact('items', 'locations');
		$pass_to_render['recordset'] = $items;
		$pass_to_render['record_type'] = 'Item';
		
		return $this->render('map/browse.php', $pass_to_render);
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
		
        $item = get_db()->getTable('Item')->find($this->_getParam('id'));
		
		$locations = get_location_for_item($item->id);
				
		$location = $locations[$item->id];	
		
		$has_location = !$location ? false : true;
		
		$this->_setParam('output', 'map-xml');
		
		$this->render('map/show.php', compact('item','location', 'has_location'));
	}

}?>