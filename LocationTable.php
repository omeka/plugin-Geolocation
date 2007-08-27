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
	public function findLocationByItem($item)
	{		
		if($item->exists()) {
		
			$dql = "SELECT l.* FROM Location l WHERE l.item_id = ? LIMIT 1";
		
			$q = new Doctrine_Query;
		
			$q->parseQuery($dql);
		
			return $q->execute(array($item->id))->getFirst();			
		
		}
	}
}

?>