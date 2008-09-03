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
        $items = $this->_getItems();
        
        //var_dump($items);exit();
        
        $locations = get_location_for_item($items);
        
        //Make this accessible from the plugin template helpers
        $params = array('page'=>$this->_getParam('page', 1));
        Zend_Registry::set('map_params', $params);
        
        $this->view->assign(compact('items', 'locations'));
    }
    
    private function _getItems()
    {
        $itemTable = $this->getTable('Item');
        $itemSelect = $itemTable->getSelectForFindBy();
        $itemSelect->joinInner(array('l' => $this->getDb()->Location), 'l.item_id = i.id', array());
        return $itemTable->fetchObjects($itemSelect);
    }
}