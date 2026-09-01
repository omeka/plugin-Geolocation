<?php

class Geolocation_View_Helper_GeolocationSrAlerts extends Zend_View_Helper_Abstract
{
    /**
     * The visually-hidden aria-live region the map JS writes popup open/close
     * announcements into. Shared by the browse and single-item maps so the
     * translated announcement strings live in one place.
     *
     * The id is scoped to the map's own div id ("{$mapDivId}-sr-alerts") so a
     * page rendering more than one map keeps a distinct region per map; the map
     * JS resolves its region the same way from its mapDivId.
     */
    public function geolocationSrAlerts($mapDivId)
    {
        return '<div id="' . html_escape($mapDivId) . '-sr-alerts" class="sr-only" aria-live="polite" aria-atomic="true"'
             . ' data-lat-string="' . html_escape(__('Latitude')) . '"'
             . ' data-long-string="' . html_escape(__('Longitude')) . '"'
             . ' data-opened-string="' . html_escape(__('Opened.')) . '"'
             . ' data-closed-string="' . html_escape(__('Closed.')) . '"></div>';
    }
}
