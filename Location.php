<?php
/**
 * Location
 * @package: Omeka
 */
class Location extends Kea_Record
{
    public function setTableDefinition()
    {
		$this->setTableName('locations');
		
		$this->hasColumn('item_id', 'integer', null, array('range'=>array('1')));
		$this->hasColumn('latitude', 'double', null, array('notblank'=>true));
		$this->hasColumn('longitude', 'double', null, array('notblank'=>true));
		$this->hasColumn('zipcode', 'integer');
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