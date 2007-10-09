<?php
require_once 'LocationTable.php';
/**
 * Location
 * @package: Omeka
 */
class Location extends Omeka_Record
{
    public function setTableDefinition()
    {
		$this->setTableName('locations');
		
		$this->hasColumn('item_id', 'integer', null, array('range'=>array('1')));
		$this->hasColumn('latitude', 'float');
		$this->hasColumn('longitude', 'float');
		$this->hasColumn('zoom_level', 'integer');
		$this->hasColumn('map_type', 'string', 255);
		$this->hasColumn('address', 'string');
    }

    public function setUp()
    {
		$this->hasOne('Item','Location.item_id');
    }
}

?>