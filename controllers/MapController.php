<?php

require_once 'Omeka/Controller/Action.php';

class Geolocation_MapController extends Omeka_Controller_Action
{
    public function browseAction()
    {        
        if ($searchJson = $this->_getParam('searchJson')) {
            $_GET = Zend_Json::decode($searchJson);
        }
        // Need to use a plugin hook here to make sure that this search retrieves
        // only items that are on the map.
        $this->_setParam('only_map_items', true);
        $this->_setParam('use_map_per_page', true);
        $results = $this->_helper->searchItems();
        
        $items      = $results['items'];
        $totalItems = $this->_getTotalItems();
        $locations  = geolocation_get_location_for_item($items);

        // Make the pagination values accessible from the plugin template 
        // helpers.
        $params = array('page'          => $results['page'], 
                        'per_page'      => geolocation_get_map_items_per_page(), 
                        'total_results' => $results['total_results']);

        Zend_Registry::set('map_params', $params);
        
        // Make the pagination values accessible from pagination_links().
        Zend_Registry::set('pagination', $params);
        
        $this->view->assign(compact('items', 'totalItems', 'locations'));
    }

    private function _getTotalItems()
    {
        $itemTable = $this->getTable('Item');
        $itemSelect = $itemTable->getSelectForCount();
        $itemSelect->joinInner(array('l' => $this->getDb()->Location), 'l.item_id = i.id', array());
        return $itemTable->fetchOne($itemSelect);
    }

}