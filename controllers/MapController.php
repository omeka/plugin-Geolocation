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

	    //If we are looking for a specific item, bypass the other shit
		if($this->_getParam('id')) {
		    
		    $item = get_db()->getTable('Item')->find($this->_getParam('id'));
		    $items = array($item);
		                
		}else {
    		$c = $this->getController('items');
		
    		//Retrieve the items from that shit
    		$items = $c->browseAction();		    
 //   		exit;
		}
        
		$locations = get_location_for_item($items);
		//Make this accessible from the plugin template helpers
		$params = array('page'=>$this->_getParam('page', 1));
		Zend_Registry::set('map_params', $params);
		
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
}?>