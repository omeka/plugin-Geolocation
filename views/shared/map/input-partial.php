<?php
$center = js_escape($center);
$options = $this->geolocationMapOptions($options);
?>

<input type="hidden" name="geolocation_form_shown" value="1">
<input type="hidden" name="geolocation_locations" id="geolocation-locations-json">

<div class="field">
    <div id="location_form" class="two columns alpha">
        <label for="geolocation_address"><?php echo html_escape($label); ?></label>
    </div>
    <div class="inputs five columns omega">
        <input type="text" id="geolocation_address">
        <button type="button" name="geolocation_find_location_by_address" id="geolocation_find_location_by_address" data-success-message="<?php echo __('Location found.'); ?>"><?php echo __('Find'); ?></button>
    </div>
</div>
<div id="geolocation-sr-alerts" class="sr-only" aria-live="polite" aria-atomic="true"></div>
<div id="omeka-map-form" class="geolocation-map" role="region" aria-roledescription="map" aria-label="<?php echo html_escape(__('Geolocation')); ?>" data-locations="<?php echo html_escape(json_encode($existingLocations)); ?>"></div>

<?php
echo js_tag('geocoder');
$geocoder = json_encode(get_option('geolocation_geocoder'));
?>
<script type="text/javascript">
var omekaGeolocationForm = new OmekaMapForm('omeka-map-form', <?php echo $center; ?>, <?php echo $options; ?>);
jQuery.each(jQuery('#omeka-map-form').data('locations'), function (i, loc) {
    omekaGeolocationForm.addLocation(loc);
});
var geocoder = new OmekaGeocoder(<?php echo $geocoder; ?>);
var geolocationMapFitted = false;
jQuery(document).on('omeka:tabselected', function () {
    omekaGeolocationForm.resize();
    // fitBounds requires a visible container. If the map tab is not active on page
    // load the container has 0 height, so defer fitting to the first omeka:tabselected
    // event at which the container actually has size.
    if (!geolocationMapFitted && omekaGeolocationForm.map.getSize().x > 0) {
        omekaGeolocationForm.fitLocations();
        geolocationMapFitted = true;
    }
});

jQuery(document).ready(function () {
    jQuery('#geolocation_find_location_by_address').on('click', function (event) {
        event.preventDefault();
        var address = jQuery('#geolocation_address').val();
        var successMessage = jQuery(this).data('successMessage');
        geocoder.geocode(address).then(function (coords) {
            var latlng = L.latLng(coords);
            omekaGeolocationForm.addLocation({
                geometry_json: JSON.stringify({type: 'Point', coordinates: [latlng.lng, latlng.lat]}),
                zoom_level: omekaGeolocationForm.map.getZoom(),
                address: address
            });
            omekaGeolocationForm.map.panTo(latlng);
            jQuery('#geolocation-sr-alerts').text(successMessage + ' ' + address);
        }, function () {
            alert('Error: "' + address + '" was not found!');
        });
    });

    jQuery('#geolocation_address').on('keydown', function (event) {
        if (event.which == 13) {
            event.preventDefault();
            jQuery('#geolocation_find_location_by_address').click();
        }
    });
});
</script>
