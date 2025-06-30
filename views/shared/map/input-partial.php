<?php
$center = js_escape($center);
$options = $this->geolocationMapOptions($options);
?>

<input type="hidden" name="geolocation[latitude]" value="<?php echo $lat; ?>">
<input type="hidden" name="geolocation[longitude]" value="<?php echo $lng; ?>">
<input type="hidden" name="geolocation[zoom_level]" value="<?php echo $zoom; ?>">
<input type="hidden" name="geolocation[map_type]" value="Leaflet">

<div class="field">
    <div id="location_form" class="two columns alpha">
        <label for="geolocation_address"><?php echo html_escape($label); ?></label>
    </div>
    <div class="inputs five columns omega">
        <input type="text" name="geolocation[address]" id="geolocation_address" value="<?php echo $address; ?>">
        <button type="button" name="geolocation_find_location_by_address" id="geolocation_find_location_by_address" data-success-message="<?php echo __('Location found.'); ?>"><?php echo __('Find'); ?></button>
    </div>
</div>

<div class="metadata-location-picker">
  <label for="metadata-location-select">Get location from metadata: </label>
  <select id="metadata-location-select">
    <!-- TODO: get metadata -->
    <?php foreach ($metadata as $md): ?>
      <option value="<?php echo html_escape($md); ?>">
        <?php echo html_escape($md); ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button type="button" id="metadata-load-btn">Load</button>
</div>

<div id="geolocation-sr-alerts" class="sr-only" aria-live="polite" aria-atomic="true"></div>
<div id="omeka-map-form" class="geolocation-map"></div>

<?php
echo js_tag('geocoder');
$geocoder = json_encode(get_option('geolocation_geocoder'));
?>
<script type="text/javascript">
var omekaGeolocationForm = new OmekaMapForm('omeka-map-form', <?php echo $center; ?>, <?php echo $options; ?>);
var geocoder = new OmekaGeocoder(<?php echo $geocoder; ?>);
jQuery(document).on('omeka:tabselected', function () {
    omekaGeolocationForm.resize();
});

jQuery(document).ready(function () {
    // Make the Find By Address button lookup the geocode of an address and add a marker.
    jQuery('#geolocation_find_location_by_address').on('click', function (event) {
        event.preventDefault();
        var address = jQuery('#geolocation_address').val();
        var successMessage = jQuery(this).data('successMessage');
        geocoder.geocode(address).then(function (coords) {
            var marker = omekaGeolocationForm.setMarker(L.latLng(coords));
            if (marker === false) {
                jQuery('#geolocation_address').val('');
                jQuery('#geolocation_address').focus();
            } else {
                jQuery('#geolocation-sr-alerts').text(successMessage + ' ' + address);
            }
        }, function () {
            alert('Error: "' + address + '" was not found!');
        });
    });

    // Make the return key in the geolocation address input box click the button to find the address.
    jQuery('#geolocation_address').on('keydown', function (event) {
        if (event.which == 13) {
            event.preventDefault();
            jQuery('#geolocation_find_location_by_address').click();
        }
    });

    // Make the metadata load button set the hidden form fields the plugin uses.
    jQuery('#metadata-load-btn').on('click', function (event) {
        var metadata = $('#metadata-location-select').val();
        if (!metadata) return;
        $('input[name="location]').val(metadata);
    });
});
</script>
