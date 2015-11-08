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
    public $map_type;
    public $address;

    /**
     * Executes before the record is saved.
     */
    protected function beforeSave($args)
    {
        if (is_null($this->map_type)) {
            $this->map_type = '';
        }
        if (is_null($this->address)) {
            $this->address = '';
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
        if (empty($this->latitude)) {
            $this->addError('latitude', __('Location requires a latitude.'));
        }
        if (empty($this->longitude)) {
            $this->addError('longitude', __('Location requires a longitude.'));
        }
        if (empty($this->zoom_level)) {
            $this->addError('zoom_level', __('Location requires a zoom level.'));
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
