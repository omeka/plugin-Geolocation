<?php

/**
 * Location
 * @package: Omeka
 */
class Location extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    public $item_id;
    public $latitude;
    public $longitude;
    public $zoom_level;
    public $address;
    public $label;
    public $geometry_json;

    /**
     * Executes before the record is saved.
     */
    protected function beforeSave($args)
    {
        if (is_null($this->address)) {
            $this->address = '';
        }
        if (is_null($this->label)) {
            $this->label = '';
        }
        // latitude and longitude are kept in sync with geometry_json so that
        // geographic radius search (hookItemsBrowseSql) works for all location
        // types without spatial SQL functions. For shapes, we use the bounding
        // box center as a representative point.
        $geometry = json_decode($this->geometry_json, true);
        if ($geometry) {
            if ($geometry['type'] === 'Point') {
                $this->longitude = $geometry['coordinates'][0];
                $this->latitude  = $geometry['coordinates'][1];
            } else {
                // Polygon coordinates[0] is the outer boundary; LineString coordinates is the points array directly
                $coords = $geometry['type'] === 'Polygon'
                    ? $geometry['coordinates'][0]
                    : $geometry['coordinates'];
                $lngs = array_column($coords, 0);
                $lats = array_column($coords, 1);
                $this->longitude = (min($lngs) + max($lngs)) / 2;
                $this->latitude  = (min($lats) + max($lats)) / 2;
            }
        }
    }

    /**
     * Validate this location before saving.
     */
    protected function _validate()
    {
        if (empty($this->item_id)) {
            $this->addError('item_id', __('Location requires an item ID.'));
        }
        // An item must exist.
        if (!$this->getTable('Item')->exists($this->item_id)) {
            $this->addError('item_id', __('Location requires a valid item ID.'));
        }
        $geometry = json_decode($this->geometry_json, true);
        if (!$geometry || !in_array($geometry['type'] ?? '', ['Point', 'LineString', 'Polygon'])) {
            $this->addError('geometry_json', __('Location requires a valid geometry.'));
        }
    }

    /**
     * Identify Location records as relating to the Locations ACL resource.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'Locations';
    }
}
