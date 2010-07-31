<?php

require_once HELPERS;
require_once GEOLOCATION_DIR . '/models/Location.php';

class Geolocation_ViewTestCase extends PHPUnit_Framework_TestCase 
{
	protected $view;
	
	public function setUp()
	{
		$this->view = new Omeka_View;
		Zend_Registry::set('view', $this->view);
        Omeka_Context::getInstance()->setDb($this->getMock('Omeka_Db', null, array(null)));
	}
	
	public function tearDown()
	{
        Zend_Registry::_unsetInstance();
        Omeka_Context::resetInstance();
        Omeka_Controller_Flash::reset();
        parent::tearDown();
	}
}
