<?php
class Table_Location extends Omeka_Db_Table
{
    /**
     * Returns a location or an array of locations for an item or an array of
     * items.
     *
     * @param array|Item|integer $item An item or item id, or an array of items
     * or item ids.
     * @param boolean $findOnlyFirst Whether or not to return only the first
     * location of each item, if it exists.
     * @param boolean $flat Whether or not to return the list of locations as a
     * simple unordered list. If the param $item is not an array, the list is
     * always flat.
     * @return array|Location A location, or a list of locations if a flat
     * response is requested, or an associative array of locations, with the
     * item id as key. If the parameter "find only first" is true, the value
     * will be a single location, else the list of locations associated to the
     * item.
     */
    public function findLocationsByItem($item, $findOnlyFirst = false, $flat = false)
    {
        $db = $this->_db;

        // Quick checks.
        if (($item instanceof Item) && !$item->exists()) {
            return array();
        }
        // Empty.
        elseif (empty($item)) {
            return array();
        }

        $params = array('item' => $item);
        $select = $this->getSelectForFindBy($params);

        // If only a single location is request, return the first one found.
        if (!is_array($item) && $findOnlyFirst) {
            $location = $this->fetchObject($select);
            return $location;
        }

        if ($findOnlyFirst) {
            $alias = $this->getTableAlias();
            // With MySql, group by item allows to keep the first of the group.
            $select->group("$alias.item_id");
            // Sort by id in order to return always the first location.
            $select->order("$alias.id");
        }

        // Get the locations.
        $locations = $this->fetchObjects($select);

        if (!is_array($item) || $flat) {
            return $locations;
        }

        // Return an associative array of locations where the key is the item_id
        // of the location.
        $indexedLocations = array();
        if ($findOnlyFirst) {
            foreach ($locations as $loc) {
                $indexedLocations[$loc['item_id']] = $loc;
            }
        }
        // Return an associative array of locations with each item id.
        else {
            foreach ($locations as $loc) {
                $indexedLocations[$loc['item_id']][] = $loc;
            }
        }

        return $indexedLocations;
    }

    /**
     * Returns one location or an array of the first locations for an item or an
     * array of items.
     *
     * @param array|Item|integer $item An item or item id, or an array of items
     * or item ids.
     * @param boolean $findOnlyFirst Whether or not to return only one location
     * if it exists for the item.
     * @return array|Location A location or an associative array of locations,
     * with the item id as key.
     */
    public function findLocationByItem($item, $findOnlyFirst = false)
    {
        return $this->findLocationsByItem($item, $findOnlyFirst);
    }

    /**
     * Add permission check to location queries.
     *
     * Since all locations belong to an item we can override this method to join
     * the items table and add a permission check to the select object.
     *
     * @return Omeka_Db_Select
     */
    public function getSelect()
    {
        $select = parent::getSelect();
        $select->join(array('items' => $this->_db->Item), 'items.id = locations.item_id', array());
        $permissions = new Omeka_Db_Select_PublicPermissions('Items');
        $permissions->apply($select, 'items');
        return $select;
    }

    /**
     * Retrieve an array of key=>value pairs that can be used as options in a
     * <select> form input.
     *
     * @param Omeka_Db_Select
     * @param array
     * @return void
     */
    public function applySearchFilters($select, $params)
    {
        $alias = $this->getTableAlias();
        $boolean = new Omeka_Filter_Boolean;
        $genericParams = array();
        foreach ($params as $key => $value) {
            if ($value === null || (is_string($value) && trim($value) == '')) {
                continue;
            }
            switch ($key) {
                case 'item':
                    $this->filterByItem($select, $value);
                    break;
                default:
                    $genericParams[$key] = $value;
            }
        }

        if (!empty($genericParams)) {
            parent::applySearchFilters($select, $genericParams);
        }
    }

    /**
     * Filter locations by item.
     *
     * @see self::applySearchFilters()
     * @param Omeka_Db_Select
     * @param Item|array|integer $items An item or item id, or an array of items
     * or item ids.
     */
    public function filterByItem($select, $items)
    {
        $alias = $this->getTableAlias();

        if (!is_array($items)) {
            $items = array($items);
        }

        $items = array_map(
            function ($value) {
                 return (integer) ((is_object($value)) ? $value->id : $value);
            },
            $items);

        if (count($items) > 1) {
            $select->where("`$alias`.`item_id` IN (?)", $items);
        } else {
            $select->where("`$alias`.`item_id` = ?", reset($items));
        }
    }
}
