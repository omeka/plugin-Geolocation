<?php
/**
 * Omeka
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Omeka\Record\Api
 */
class Api_Location extends Omeka_Record_Api_AbstractRecordAdapter
{
    /**
     * Get the REST representation of a location.
     *
     * @param Location $record
     * @return array
     */
    public function getRepresentation(Omeka_Record_AbstractRecord $record)
    {
        $representation = [
            'id' => $record->id,
            'url' => $this->getResourceUrl("/geolocations/{$record->id}"),
            'geometry_json' => $record->geometry_json,
            'latitude' => $record->latitude,
            'longitude' => $record->longitude,
            'zoom_level' => $record->zoom_level,
            'address' => $record->address,
            'label' => $record->label,
            'item' => [
                'id' => $record->item_id,
                'url' => $this->getResourceUrl("/items/{$record->item_id}"),
                'resource' => 'items',
            ],
        ];
        return $representation;
    }

    /**
     * Set POST data to a location.
     *
     * @param Location $record
     * @param mixed $data
     */
    public function setPostData(Omeka_Record_AbstractRecord $record, $data)
    {
        if (isset($data->item->id)) {
            $record->item_id = $data->item->id;
        }
        $this->_applyLocationFields($record, $data);
    }

    /**
     * Set PUT data to a location.
     *
     * @param Location $record
     * @param mixed $data
     */
    public function setPutData(Omeka_Record_AbstractRecord $record, $data)
    {
        $this->_applyLocationFields($record, $data);
    }

    private function _applyLocationFields(Omeka_Record_AbstractRecord $record, $data)
    {
        if (isset($data->geometry_json)) {
            $record->geometry_json = $data->geometry_json;
        } elseif (isset($data->latitude) && isset($data->longitude)) {
            // Fallback for pre-4.0 API clients that post lat/lng without geometry_json
            $record->geometry_json = json_encode([
                'type' => 'Point',
                'coordinates' => [(float) $data->longitude, (float) $data->latitude],
            ]);
        }
        if (isset($data->zoom_level)) {
            $record->zoom_level = $data->zoom_level;
        }
        if (isset($data->address)) {
            $record->address = $data->address;
        } else {
            $record->address = '';
        }
        if (isset($data->label)) {
            $record->label = $data->label;
        } else {
            $record->label = '';
        }
    }
}
