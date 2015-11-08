function OmekaMap(mapDivId, center, options) {
    this.mapDivId = mapDivId;
    this.center = center;
    this.options = options;
}

OmekaMap.prototype = {

    map: null,
    mapDivId: null,
    markers: [],
    options: {},
    center: null,
    markerBounds: null,

    addMarker: function (lat, lng, options, bindHtml, folder)
    {
        if (!options) {
            options = {};
        }
        options.position = new google.maps.LatLng(lat, lng);
        options.map = this.map;

        var marker = new google.maps.Marker(options);

        if (bindHtml) {
            var infoWindow = new google.maps.InfoWindow({
                content: bindHtml
            });

            var that = this;
            google.maps.event.addListener(marker, 'click', function () {
                // Prevent multiple windows from being open at once.
                if (that.lastWindow) {
                    that.lastWindow.close();
                }
                that.lastWindow = infoWindow;
                infoWindow.open(this.map, marker);
            });
        }

        marker.set('folder', folder);

        this.markers.push(marker);
        this.markerBounds.extend(options.position);
        return marker;
    },

    fitMarkers: function () {
        if (this.markers.length == 1) {
            this.map.setCenter(this.markers[0].getPosition());
        } else {
            this.map.fitBounds(this.markerBounds);
        }
    },

    // Helper to convert the map type from Omeka to Google.
    convertMapType: function (omekaMapType) {
        switch (omekaMapType) {
        case 'hybrid': return google.maps.MapTypeId.HYBRID;
        case 'satellite': return google.maps.MapTypeId.SATELLITE;
        case 'terrain': return google.maps.MapTypeId.TERRAIN;
        case 'roadmap':
        default: return google.maps.MapTypeId.ROADMAP;
        }
    },

    initMap: function () {
        if (!this.center) {
            alert('Error: The center of the map has not been set!');
            return;
        }

        // Build the map.
        var mapOptions = {
            zoom: this.center.zoomLevel,
            center: new google.maps.LatLng(this.center.latitude, this.center.longitude),
            mapTypeId: this.convertMapType(this.options.mapType),
        };

        jQuery.extend(mapOptions, this.options.mapOptions);

        this.map = new google.maps.Map(document.getElementById(this.mapDivId), mapOptions);
        this.markerBounds = new google.maps.LatLngBounds();

        // Show the center marker if we have that enabled.
        if (this.center.show) {
            this.addMarker(this.center.latitude,
                           this.center.longitude,
                           {title: "(" + this.center.latitude + ',' + this.center.longitude + ")"},
                           this.center.markerHtml);
        }
    }
};

function OmekaMapSingle(mapDivId, center, options) {
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();
}

function OmekaMapBrowse(mapDivId, center, options) {
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();

    //XML loads asynchronously, so need to call for further config only after it has executed
    this.loadKmlIntoMap(this.options.uri, this.options.params);
}

OmekaMapBrowse.prototype = {

    /* Update the main map and add the list of folders, if needed and if any. */
    afterLoadItems: function (folders) {
        if (this.options.fitMarkers) {
            this.fitMarkers();
        }

        // "list" contains the id used to build the list of links of locations.
        if (!this.options.list) {
            return;
        }
        var listDiv = jQuery('#' + this.options.list);

        if (!listDiv.size()) {
            alert('Error: You have no map links div!');
        } else {
            //Create HTML links for each of the markers
            this.buildListLinks(listDiv, folders);
        }
    },

    /* Need to parse KML manually b/c Google Maps API cannot access the KML
       behind the admin interface */
    loadKmlIntoMap: function (kmlUrl, params) {
        var that = this;
        jQuery.ajax({
            type: 'GET',
            dataType: 'xml',
            url: kmlUrl,
            data: params,
            success: function(data) {
                var xml = jQuery(data);

                /* KML can be parsed as:
                    kml - root element
                        Document
                            Folder - item level, if any
                                Placemark
                                    namewithlink
                                    description
                                    Point - longitude,latitude
                */

                // Retrieve the balloon styling from the KML file.
                that.browseBalloon = that.getBalloonStyling(xml);

                var folders = xml.find('Folder');
                if (folders.size()) {
                    jQuery.each(folders, function (index, folder) {
                        folder = jQuery(folder);
                        var placeMarks = folder.find('Placemark');

                        // If we have some placemarks, load them.
                        if (placeMarks.size()) {
                            // Build the markers from the placemarks
                            jQuery.each(placeMarks, function (index, placeMark) {
                                placeMark = jQuery(placeMark);
                                that.buildMarkerFromPlacemark(placeMark, folder);
                            });
                        } else {
                            // @todo Elaborate with an error message
                            return false;
                        }
                    });

                    // We have successfully loaded some map points, so continue
                    // setting up the map object.
                    return that.afterLoadItems(folders);

                } else {
                    var folder = null;
                    var placeMarks = xml.find('Placemark');

                    // If we have some placemarks, load them
                    if (placeMarks.size()) {
                        // Build the markers from the placemarks
                        jQuery.each(placeMarks, function (index, placeMark) {
                            placeMark = jQuery(placeMark);
                            that.buildMarkerFromPlacemark(placeMark, folder);
                        });

                        // We have successfully loaded some map points, so
                        // continue setting up the map object.
                        return that.afterLoadItems(folders);
                    } else {
                        // @todo Elaborate with an error message
                        return false;
                    }
                }
            }
        });
    },

    getBalloonStyling: function (xml) {
        return xml.find('BalloonStyle text').text();
    },

    // Build a marker given the KML XML Placemark data. Some data can be set at
    // Folder (item) level.
    // I wish we could use the KML file directly, but it's behind the admin interface so no go
    buildMarkerFromPlacemark: function (placeMark, folder) {
        // Get the info for each location on the map
        var title = placeMark.find('name').text();
        var titleWithLink = placeMark.find('namewithlink').text();
        var body = placeMark.find('description').text();
        var snippet = placeMark.find('Snippet').text();

        // Set the default values if none was provided at the location level.
        if (folder) {
            title = title ? title : folder.find('name').text();
            titleWithLink = titleWithLink ? titleWithLink : folder.find('namewithlink').text();
            body = body ? body : folder.find('description').text();
            snippet = snippet ? snippet : folder.find('Snippet').text();
        }

        // Extract the lat/long from the KML-formatted data
        var coordinates = placeMark.find('Point coordinates').text().split(',');
        var longitude = coordinates[0];
        var latitude = coordinates[1];

        // Use the KML formatting (do some string sub magic)
        var balloon = this.browseBalloon;
        balloon = balloon.replace('$[namewithlink]', titleWithLink).replace('$[description]', body).replace('$[Snippet]', snippet);

        // Build a marker, add HTML for it
        this.addMarker(latitude, longitude, {title: title}, balloon, folder);
    },

    // Calculate the zoom level given the 'range' value
    // Not currently used by this class, but possibly useful
    // http://throwless.wordpress.com/2008/02/23/gmap-geocoding-zoom-level-and-accuracy/
    calculateZoom: function (range, width, height) {
        var zoom = 18 - Math.log(3.3 * range / Math.sqrt(width * width + height * height)) / Math.log(2);
        return zoom;
    },

    // Build the list of links to markers, grouped by folders if any.
    buildListLinks: function (container, folders) {
        var that = this;
        var list = jQuery('<ul></ul>');
        list.appendTo(container);

        // TODO Factorize.
        if (folders.size()) {
            // Build the list of markers by folder. To avoid an issue, the
            // process is done from the markers, already checked.

            // Build the list of markers by folder to simplify next process.
            var markers = this.markers;
            var markersByFolder = new Array();
            jQuery.each(folders, function (index, folder) {
                var folderId = jQuery(folder).attr('id');
                var folderMarkers = {folderId: folderId, markers: []};
                jQuery.each(markers, function (index, marker) {
                    var markerFolderId = marker.get('folder').attr('id');
                    if (folderId == markerFolderId) {
                        folderMarkers.markers.push(marker);
                    }
                });
                markersByFolder.push(folderMarkers);
            });

            markersByFolder.forEach(function(folderMarkers, index) {
                var listFolder = jQuery('<li class="locations-item"></li>');
                listFolder.attr('id', folderMarkers.folderId);

                // If there is one marker, don't display the list. This makes
                // this view similar to old Geolocation.
                if (folderMarkers.markers.length == 1) {
                    var marker = folderMarkers.markers[0];
                    var listElement = listFolder;
                    listElement.addClass('single');

                    // Make an <a> tag, give it a class for styling
                    var link = jQuery('<a></a>');
                    link.addClass('item-link');

                    // Links open up the markers on the map, clicking them doesn't actually go anywhere
                    link.attr('href', 'javascript:void(0);');

                    // Each <li> starts with the title of the item
                    link.html(marker.getTitle());

                    // Clicking the link should take us to the map
                    link.bind('click', {}, function (event) {
                        google.maps.event.trigger(marker, 'click');
                        that.map.panTo(marker.getPosition());
                    });

                    link.appendTo(listElement);
                    listElement.appendTo(list);

                } else {
                    folderKml = folders.parent().find('#' + folderMarkers.folderId);
                    listFolder.html(folderKml.find('name').text());
                    var listElements = jQuery('<ul></ul>');

                    // Add the list of markers, with links.
                    folderMarkers.markers.forEach(function (marker, index) {
                        var listElement = jQuery('<li></li>');

                        // Make an <a> tag, give it a class for styling
                        var link = jQuery('<a></a>');
                        link.addClass('item-link');

                        // Links open up the markers on the map, clicking them doesn't actually go anywhere
                        link.attr('href', 'javascript:void(0);');

                        // Each line is the index of the location of the item.
                        var indexNumber = index + 1;
                        link.html(indexNumber.toString() + ' ');

                        // Clicking the link should take us to the map
                        link.bind('click', {}, function (event) {
                            google.maps.event.trigger(marker, 'click');
                            that.map.panTo(marker.getPosition());
                        });

                        link.appendTo(listElement);
                        listElement.appendTo(listElements);
                    });

                    listElements.appendTo(listFolder);
                    listFolder.appendTo(list);
                }
            });

        } else {
            // Simple list of single markers.
            jQuery.each(this.markers, function (index, marker) {
                var listElement = jQuery('<li></li>');

                // Make an <a> tag, give it a class for styling
                var link = jQuery('<a></a>');
                link.addClass('item-link');

                // Links open up the markers on the map, clicking them doesn't actually go anywhere
                link.attr('href', 'javascript:void(0);');

                // Each <li> starts with the title of the item
                link.html(marker.getTitle());

                // Clicking the link should take us to the map
                link.bind('click', {}, function (event) {
                    google.maps.event.trigger(marker, 'click');
                    that.map.panTo(marker.getPosition());
                });

                link.appendTo(listElement);
                listElement.appendTo(list);
            });
        }
    }
};

function OmekaMapForm(mapDivId, center, options) {
    var that = this;
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();

    this.formDiv = jQuery('#' + this.options.form.id);

    // Make the map clickable to add a location point.
    google.maps.event.addListener(this.map, 'click', function (event) {
        // If we are clicking a new spot on the map
        if (!that.options.confirmLocationChange || that.markers.length === 0 || confirm('Are you sure you want to change the location of the item?')) {
            var point = event.latLng;
            var marker = that.setMarker(point);
            jQuery('#geolocation_address').val('');
        }
    });

    // Make the map update on zoom changes.
    google.maps.event.addListener(this.map, 'zoom_changed', function () {
        that.updateZoomForm();
    });

    // Make the Find By Address button lookup the geocode of an address and add a marker.
    jQuery('#geolocation_location_find').bind('click', function (event) {
        var address = jQuery('#geolocation_address').val();
        that.findAddress(address);
        event.stopPropagation();
        return false;
    });

    // Make the return key in the geolocation address input box click the button to find the address.
    jQuery('#geolocation_address').bind('keydown', function (event) {
        if (event.which == 13) {
            jQuery('#geolocation_location_find').click();
            event.stopPropagation();
            return false;
        }
    });

    // Make the button Add add the point to the list.
    jQuery('#geolocation_location_add').bind('click', function (event) {
        that.addLocation();
        event.stopPropagation();
        return false;
    });

    // Make the buttons Remove remove the point of the list (may be dynamically
    // created).
    jQuery(document).on('click', '.geolocation-remove', function () {
        var locationElement = jQuery(this).closest('tr');
        that.removeLocation(locationElement);
     });

    // Make the buttons Display display the current point (may be dynamically
    // created).
    jQuery(document).on('click', '.geolocation-display', function () {
        var locationElement = jQuery(this).closest('tr');
        that.displayLocation(locationElement);
     });

    // Add the existing map point.
    if (this.options.point) {
        this.map.setZoom(this.options.point.zoomLevel);

        var point = new google.maps.LatLng(this.options.point.latitude, this.options.point.longitude);
        var marker = this.setMarker(point);
        this.map.setCenter(marker.getPosition());
    }
}

OmekaMapForm.prototype = {
    /* Get the geolocation of the address and add marker. */
    findAddress: function (address) {
        var that = this;
        if (!this.geocoder) {
            this.geocoder = new google.maps.Geocoder();
        }
        this.geocoder.geocode({'address': address}, function (results, status) {
            // If the point was found, then put the marker on that spot
            if (status == google.maps.GeocoderStatus.OK) {
                var point = results[0].geometry.location;

                // If required, ask the user if they want to add a marker to the geolocation point of the address.
                // If so, add the marker, otherwise clear the address.
                if (!that.options.confirmLocationChange || that.markers.length === 0 || confirm('Are you sure you want to change the location of the item?')) {
                    var marker = that.setMarker(point);
                } else {
                    jQuery('#geolocation_address').val('');
                    jQuery('#geolocation_address').focus();
                }
            } else {
                // If no point was found, give us an alert
                alert('Error: "' + address + '" was not found!');
                return null;
            }
        });
    },

    /* Add the current geolocation to the list of points. */
    addLocation: function () {
        // Get the point and check it.
        if (!this.markers.length) {
            alert('Error: No point defined!');
            return null;
        }
        var marker = this.markers[0];
        var point = marker.getPosition();

        var addressElement = document.getElementsByName('geolocation[address]')[0];
        var latitudeElement = document.getElementsByName('geolocation[latitude]')[0];
        var longitudeElement = document.getElementsByName('geolocation[longitude]')[0];
        var zoomElement = document.getElementsByName('geolocation[zoom_level]')[0];

        if (latitudeElement.value == '' || longitudeElement.value == '') {
            alert('Error: No point defined!');
            return null;
        }

        // Set the value in the list.
        var locations = jQuery('table.geolocation-locations tbody tr');

        // Remove the message for empty location if any.
        if (locations.length == 1) {
            locations.remove();
        }

        var newRowId = 'new-' + Math.floor(Math.random() * 999999999);

        // Add a new row to the list.
        var row = '<tr id="geolocation-location-' + newRowId + '" class="geolocation-location location-new ' + (locations.length % 2 ? 'odd' : 'even') + '">';
        row += '<td>';
        row += '<button type="button" class="geolocation-display button small green" id="locations-' + newRowId + '-display" name="locations[' + newRowId + '][display]" title="Display this location">O</button>';
        row += '<button type="button" class="geolocation-remove button small red" id="locations-' + newRowId + '-remove" name="locations[' + newRowId + '][remove]" title="Remove this location">X</button>';
        row += '</td>';
        row += '<td><input type="text" class="geolocation-address" placeholder="Address" value="" id="locations-' + newRowId + '-address" name="locations[' + newRowId + '][address]"></td>';
        row += '<td><input type="text" class="geolocation-latitude" maxlength="15" placeholder="Latitude" value="" id="locations-' + newRowId + '-latitude" name="locations[' + newRowId + '][latitude]"></td>';
        row += '<td><input type="text" class="geolocation-longitude" maxlength="15" placeholder="Longitude" value="" id="locations-' + newRowId + '-longitude" name="locations[' + newRowId + '][longitude]"></td>';
        row += '<td><select class="geolocation-zoom-level" id="locations-' + newRowId + '-zoom_level" name="locations[' + newRowId + '][zoom_level]">';
        row += '<option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>';
        row += '<option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option>';
        row += '<option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option>';
        row += '<option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option>';
        row += '</select></td>';
        row += '<td><select class="geolocation-map-type" id="locations-' + newRowId + '-map_type" name="locations[' + newRowId + '][map_type]">';
        row += '<option value="roadmap">Roadmap</option><option value="satellite">Satellite</option><option value="hybrid">Hybrid</option><option value="terrain">Terrain</option>';
        row += '</select></td>';
        row += '</tr>';

        jQuery('table.geolocation-locations tr:last').after(row);
        document.getElementById('locations-' + newRowId + '-address').value = addressElement.value;
        document.getElementById('locations-' + newRowId + '-latitude').value = latitudeElement.value;
        document.getElementById('locations-' + newRowId + '-longitude').value = longitudeElement.value;
        jQuery('#locations-' + newRowId + '-zoom_level').val(this.map.getZoom().toString());
        jQuery('#locations-' + newRowId + '-map_type').val(this.map.getMapTypeId());
    },

    /* Remove the current geolocation from the list. */
    removeLocation: function (location) {
        location.remove();

        // Add the empty location if none.
        var locations = jQuery('table.geolocation-locations tbody tr');
        if (locations.length == 0) {
            var row = '<tr id="geolocation-empty"><td colspan="6">No location defined.</td></tr>';
            jQuery('table.geolocation-locations tbody').innerHTML(row);
        }
    },

    /* Display the current geolocation from the list. */
    displayLocation: function (location) {
        var that = this;
        var latitude = location.find('td input.geolocation-latitude').val();
        var longitude = location.find('td input.geolocation-longitude').val();
        if (latitude.length == 0 || longitude.length == 0) {
            alert('Error: This location has no latitude or longitude!');
            return;
        }
        var point = new google.maps.LatLng(latitude, longitude);
        var address = location.find('td input.geolocation-address').val();
        var zoomLevel = +location.find('td select.geolocation-zoom-level').val();
        var mapType = location.find('td select.geolocation-map-type').val();

        jQuery('#geolocation_address').val(address);
        // TODO This hack avoids a recursion for newly created locations.
        if (!location.hasClass('location-new')) {
            that.map.setZoom(zoomLevel);
        }
        that.map.setMapTypeId(this.convertMapType(mapType));
        that.updateForm(point);
        that.setMarker(point);
    },

    /* Set the marker to the point. */
    setMarker: function (point) {
        var that = this;

        // Get rid of existing markers.
        this.clearForm();

        // Add the marker
        var marker = this.addMarker(point.lat(), point.lng());
        marker.setAnimation(google.maps.Animation.DROP);

        // Pan the map to the marker
        that.map.panTo(point);

        // Make the marker clear the form if clicked.
        google.maps.event.addListener(marker, 'click', function (event) {
            if (!that.options.confirmLocationChange || confirm('Are you sure you want to remove the location of the item?')) {
                that.clearForm();
            }
        });

        this.updateForm(point);
        return marker;
    },

    /* Update the latitude, longitude, and zoom of the form. */
    updateForm: function (point) {
        var latElement = document.getElementsByName('geolocation[latitude]')[0];
        var lngElement = document.getElementsByName('geolocation[longitude]')[0];
        var zoomElement = document.getElementsByName('geolocation[zoom_level]')[0];

        // If we passed a point, then set the form to that. If there is no point, clear the form
        if (point) {
            latElement.value = point.lat();
            lngElement.value = point.lng();
            zoomElement.value = this.map.getZoom();
        } else {
            latElement.value = '';
            lngElement.value = '';
            zoomElement.value = this.map.getZoom();
        }
    },

    /* Update the zoom input of the form to be the current zoom on the map. */
    updateZoomForm: function () {
        var zoomElement = document.getElementsByName('geolocation[zoom_level]')[0];
        zoomElement.value = this.map.getZoom();
    },

    /* Clear the form of all markers. */
    clearForm: function () {
        // Remove the markers from the map
        for (var i = 0; i < this.markers.length; i++) {
            this.markers[i].setMap(null);
        }

        // Clear the markers array
        this.markers = [];

        // Update the form
        this.updateForm();
    },

    /* Resize the map and center it on the first marker. */
    resize: function () {
        google.maps.event.trigger(this.map, 'resize');
        var point;
        if (this.markers.length) {
            var marker = this.markers[0];
            point = marker.getPosition();
        } else {
            point = new google.maps.LatLng(this.center.latitude, this.center.longitude);
        }
        this.map.setCenter(point);
    }
};
