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
        $select->join(['items' => $this->_db->Item], 'items.id = locations.item_id', []);
        $permissions = new Omeka_Db_Select_PublicPermissions('Items');
        $permissions->apply($select, 'items');
        return $select;
    }
}
