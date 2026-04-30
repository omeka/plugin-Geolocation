<?php

class Geolocation_MapController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        $this->_helper->db->setDefaultModelName('Item');
    }

    public function browseAction()
    {
        list($params, $limit, $currentPage) = $this->_getBrowseParams();

        $this->view->totalItems = $this->_helper->db->getTable()->count($params);
        $this->view->params = $params;

        Zend_Registry::set('pagination', [
            'page' => $currentPage,
            'per_page' => $limit,
            'total_results' => $this->view->totalItems,
        ]);
    }

    public function browseJsonAction()
    {
        list($params, $limit, $currentPage) = $this->_getBrowseParams();

        $items = $this->_helper->db->getTable()->findBy($params, $limit, $currentPage);
        $this->view->items = $items;
        $this->view->locations = $this->_helper->db->getTable('Location')->findLocationsByItem($items);
        $this->getResponse()->setHeader('Content-Type', 'application/json');
    }

    private function _getBrowseParams()
    {
        $params = $this->getAllParams();
        $params['geolocation-mapped'] = true;
        $limit = (int) get_option('geolocation_per_page');
        $currentPage = $this->getParam('page', 1);
        return [$params, $limit, $currentPage];
    }
}
