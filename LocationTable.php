<?php
/**
 * LocationTable
 * @package: Omeka
 */
class LocationTable extends Doctrine_Table
{
	
	/**
	 * Return a multidimensional array of location info
	 *
	 * @param array|int $item_id
	 * @return array
	 **/
	public function getLocationByItem($item)
	{
		if($item->exists()) {
		
			$dql = "SELECT l.* FROM Location l WHERE l.item_id = ? LIMIT 1";
		
			$q = new Doctrine_Query;
		
			$q->parseQuery($dql);
		
			return $q->execute($item->id)->getFirst();			
		
		} else {

			return new Location;
			
		}
		

	}
}

?>