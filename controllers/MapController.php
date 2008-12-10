<?php

require_once 'Omeka/Controller/Action.php';

class Geolocation_MapController extends Omeka_Controller_Action
{
    private $_perPage;
    
    public function browseAction()
    {
	
        $this->_setPerPage();
        
        $items      = $this->_getItems();
        $totalItems = $this->_getTotalItems();
        $locations  = get_location_for_item($items);
        
        // Make the pagination values accessible from the plugin template 
        // helpers.
        $params = array('page'          => $this->_getParam('page', 1), 
                        'per_page'      => $this->_perPage, 
                        'total_results' => $totalItems);
        Zend_Registry::set('map_params', $params);
        
        // Make the pagination values accessible from pagination_links().
        Zend_Registry::set('pagination', $params);
        
        $this->view->assign(compact('items', 'totalItems', 'locations'));
    }
    
    private function _getItems()
    {
        $itemTable = $this->getTable('Item');
        $itemSelect = $itemTable->getSelectForFindBy(array('per_page' => $this->_perPage, 
                                                           'page' => $this->_getParam('page', 1)));
        $itemSelect->joinInner(array('l' => $this->getDb()->Location), 'l.item_id = i.id', array());
        return $itemTable->fetchObjects($itemSelect);
    }
    private function _getTotalItems()
    {
        $itemTable = $this->getTable('Item');
        $itemSelect = $itemTable->getSelectForCount();
        $itemSelect->joinInner(array('l' => $this->getDb()->Location), 'l.item_id = i.id', array());
        return $itemTable->fetchOne($itemSelect);
    }
    
    private function _setPerPage()
    {
        if (is_numeric(get_option('geo_per_page'))) {
            $this->_perPage = get_option('geo_per_page');
        } else {
            $this->_perPage = 10;
        }
    }
}