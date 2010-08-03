<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 */

defined('GEOLOCATION_DIR') || define('GEOLOCATION_DIR', dirname(dirname(__FILE__)));
require_once 'Geolocation_ViewTestCase.php';
require_once 'Geolocation_TestCase.php';

/**
 * 
 *
 * @package Omeka
 * @copyright Center for History and New Media, 2007-2010
 */
class Geolocation_AllTests extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new Geolocation_AllTests('Geolocation Tests');
        $testCollector = new PHPUnit_Runner_IncludePathTestCollector(
          array(dirname(__FILE__) . '/cases')
        );
        $suite->addTestFiles($testCollector->collectTests());
        return $suite;
    }
}