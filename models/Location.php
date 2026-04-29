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
        if (!is_numeric($this->latitude)) {
            $this->addError('latitude', __('Location requires a latitude.'));
        }
        if (!is_numeric($this->longitude)) {
            $this->addError('longitude', __('Location requires a longitude.'));
        }
        if (!is_numeric($this->zoom_level)) {
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
