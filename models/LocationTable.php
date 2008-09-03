<?php
class LocationTable extends Omeka_Db_Table
{
    /**
     * Return a multidimensional array of location info
     * @param array|int $item_id
     * @return array
     **/
    public function findLocationByItem($item, $findOnlyOne = false)
    {
        $db = get_db();
        
        if (($item instanceof Item) && !$item->exists()) {
            return array();
        } else if (is_array($item) && !count($item)) {
            return array();
        }
        
        $select = $db->select()->from(array('l' => $db->Location), 'l.*');
        
        $item = ($item instanceof Item) ? $item->id : $item;
        
        // Create a WHERE condition that will pull down all the location info
        if (count($item) > 1 || (is_array($item))) {
            $to_pass = array();
            foreach ($item as $it) {
                $to_pass[] = ($it instanceof Item) ? $it->id : $it;
            }
            $select->where('l.item_id IN (?)', $to_pass);
        } else {
            $select->where('l.item_id = ?', ($item instanceof Item) ? $item->id : $item);
        }
        
        $locations = $this->fetchObjects($select);
        
        if ($findOnlyOne) {
            return current($locations);
        }
        
        $indexed = array();
        
        //Now process into an array where the key is the item_id        
        foreach ($locations as $k => $loc) {
            $indexed[$loc['item_id']] = $loc;
        }
        
        return $indexed;
    }
}