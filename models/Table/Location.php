<?php
class Table_Location extends Omeka_Db_Table
{
    /**
     * Returns all locations for an item or array of items, grouped by item_id.
     *
     * @param array|Item|int $item
     * @return array item_id => Location[]
     */
    public function findLocationsByItem($item)
    {
        $db = get_db();

        if (($item instanceof Item) && !$item->exists()) {
            return [];
        } elseif (is_array($item) && !count($item)) {
            return [];
        }

        $alias = $this->getTableAlias();
        $select = $db->select()->from([$alias => $db->Location], "$alias.*");

        if (is_array($item)) {
            $itemIds = [];
            foreach ($item as $it) {
                $itemIds[] = (int) (($it instanceof Item) ? $it->id : $it);
            }
            $select->where("$alias.item_id IN (?)", $itemIds);
        } else {
            $itemId = (int) (($item instanceof Item) ? $item->id : $item);
            $select->where("$alias.item_id = ?", $itemId);
        }

        $locations = $this->fetchObjects($select);
        $grouped = [];
        foreach ($locations as $loc) {
            $grouped[$loc->item_id][] = $loc;
        }
        return $grouped;
    }

    /**
     * Join items so that public permissions on items are enforced for locations.
     *
     * Locations have no visibility of their own — a location is public only if
     * its item is public. Joining the items table here means every query on
     * this table automatically excludes locations for private items.
     *
     * @return Omeka_Db_Select
     */
    public function getSelect()
    {
        $select = parent::getSelect();
        $select->join(['items' => $this->_db->Item], 'items.id = locations.item_id', []);
        $permissions = new Omeka_Db_Select_PublicPermissions('Items');
        $permissions->apply($select, 'items');
        return $select;
    }
}
