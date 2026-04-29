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
    clusterGroup: null,

    addMarker: function (latLng, options, bindHtml)
    {
        var map = this.map;
        var marker = L.marker(latLng, options);
        var srAlertsDiv = jQuery('#geolocation-sr-alerts');
        var srAlertStringArray = [marker.options.title, srAlertsDiv.data('latString'), latLng[0], srAlertsDiv.data('longString'), latLng[1]];
        var srOpenedAlertStringArray = srAlertStringArray.concat([srAlertsDiv.data('openedString')]);
        var srClosedAlertStringArray = srAlertStringArray.concat([srAlertsDiv.data('closedString')]);

        if (this.clusterGroup) {
            this.clusterGroup.addLayer(marker);
        } else {
            marker.addTo(map);
        }

        if (bindHtml) {
            marker.bindPopup(bindHtml, {autoPanPadding: [50, 50]});
            // Fit images on the map on first load
            marker.once('popupopen', function (event) {
                var popup = event.popup;
                var imgs = popup.getElement().getElementsByTagName('img');
                for (var i = 0; i < imgs.length; i++) {
                    imgs[i].addEventListener('load', function imgLoadListener(event) {
                        event.target.removeEventListener('load', imgLoadListener);
                        // Marker autopan is disabled during panning, so defer
                        if (map._panAnim && map._panAnim._inProgress) {
                            map.once('moveend', function () {
                                popup.update();
                            });
                        } else {
                            popup.update();
                        }
                    });
                }
            });

            marker.addEventListener('popupopen', function (event) {
                srAlertsDiv.text(srOpenedAlertStringArray.join(' '));
            });

            marker.addEventListener('popupclose', function (event) {
                srAlertsDiv.text(srClosedAlertStringArray.join(' '));
            });
        }

        this.markers.push(marker);
        this.markerBounds.extend(latLng);
        return marker;
    },

    fitMarkers: function () {
        if (this.markers.length == 1) {
            this.map.panTo(this.markers[0].getLatLng());
        } else if (this.markers.length > 0) {
            this.map.fitBounds(this.markerBounds, {padding: [25, 25]});
        }
    },

    initMap: function () {
        var that = this;
        var customMap = this.options.custom_map;

        if (!this.center) {
            alert('Error: The center of the map has not been set!');
            return;
        }

        this.map = L.map(this.mapDivId).setView([this.center.latitude, this.center.longitude], this.center.zoomLevel);
        this.markerBounds = L.latLngBounds();

        L.tileLayer.provider(this.options.basemap, this.options.basemapOptions).addTo(this.map);

        if (customMap) {
            if (customMap.type === 'tiled' && customMap.tile_url) {
                L.tileLayer(customMap.tile_url, customMap).addTo(this.map);
            } else if (customMap.type === 'wms' && customMap.wms_url && customMap.layers) {
                if (customMap.transparent) {
                    customMap.format = 'image/png';
                }
                L.tileLayer.wms(customMap.wms_url, customMap).addTo(this.map);
            }
        }

        if (this.options.cluster) {
            this.clusterGroup = L.markerClusterGroup({
                showCoverageOnHover: false
            });
            this.map.addLayer(this.clusterGroup);
        }

        jQuery(this.map.getContainer()).trigger('o:geolocation:init_map', this);

        new OmekaFitControl({ position: 'topleft', omekaMap: that }).addTo(this.map);

        // Show the center marker if we have that enabled.
        if (this.center.show) {
            this.addMarker([this.center.latitude, this.center.longitude],
                           {title: "(" + this.center.latitude + ',' + this.center.longitude + ")"},
                           this.center.markerHtml);
        }
    }
};

var OmekaFitControl = L.Control.extend({
    onAdd: function () {
        var omekaMap = this.options.omekaMap;
        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-fit-all');
        var link = L.DomUtil.create('a', '', container);
        link.href = '#';
        link.title = omekaMap.options.strings.fitAllMarkers;
        link.setAttribute('role', 'button');
        link.setAttribute('aria-label', omekaMap.options.strings.fitAllMarkers);
        link.innerHTML = '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M1 1h5v2H3v3H1V1zm11 0h5v5h-2V3h-3V1zM1 12h2v3h3v2H1v-5zm14 3h-3v2h5v-5h-2v3z" fill="currentColor"/></svg>';
        L.DomEvent.on(link, 'click', function (e) {
            L.DomEvent.preventDefault(e);
            L.DomEvent.stopPropagation(e);
            omekaMap.fitMarkers();
            link.blur();
        });
        this._link = link;
        return container;
    },
    onRemove: function () {
        L.DomEvent.off(this._link, 'click');
    }
});

function OmekaMapBrowse(mapDivId, center, options) {
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();

    //XML loads asynchronously, so need to call for further config only after it has executed
    this.loadKmlIntoMap(this.options.uri, this.options.params);
}

OmekaMapBrowse.prototype = {

    afterLoadItems: function () {
        if (this.options.fitMarkers) {
            this.fitMarkers();
        }

        if (!this.options.list) {
            return;
        }
        var listDiv = jQuery('#' + this.options.list);

        if (!listDiv.length) {
            alert('Error: You have no map links div!');
        } else {
            //Create HTML links for each of the markers
            this.buildListLinks(listDiv);
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
                        Placemark
                            namewithlink
                            description
                            Point - longitude,latitude
                */
                var placeMarks = xml.find('Placemark');

                // If we have some placemarks, load them
                if (placeMarks.length) {
                    // Retrieve the balloon styling from the KML file
                    that.browseBalloon = that.getBalloonStyling(xml);

                    // Build the markers from the placemarks
                    jQuery.each(placeMarks, function (index, placeMark) {
                        placeMark = jQuery(placeMark);
                        that.buildMarkerFromPlacemark(placeMark);
                    });

                    // We have successfully loaded some map points, so continue setting up the map object
                    return that.afterLoadItems();
                } else {
                    // @todo Elaborate with an error message
                    return false;
                }
            }
        });
    },

    getBalloonStyling: function (xml) {
        return xml.find('BalloonStyle text').text();
    },

    // Build a marker given the KML XML Placemark data
    // I wish we could use the KML file directly, but it's behind the admin interface so no go
    buildMarkerFromPlacemark: function (placeMark) {
        // Get the info for each location on the map
        var title = placeMark.find('name').text();
        var titleWithLink = placeMark.find('namewithlink').text();
        var body = placeMark.find('description').text();
        var snippet = placeMark.find('Snippet').text();

        // Extract the lat/long from the KML-formatted data
        var coordinates = placeMark.find('Point coordinates').text().split(',');
        var longitude = coordinates[0];
        var latitude = coordinates[1];

        // Use the KML formatting (do some string sub magic)
        var balloon = this.browseBalloon;
        balloon = balloon.replace('$[namewithlink]', titleWithLink).replace('$[description]', body).replace('$[Snippet]', snippet);

        // Build a marker, add HTML for it
        this.addMarker([latitude, longitude], {title: title, alt: title}, balloon);
    },

    buildListLinks: function (container) {
        var that = this;
        var list = jQuery('<ul></ul>');
        list.appendTo(container);

        // Loop through all the markers
        jQuery.each(this.markers, function (index, marker) {
            var listElement = jQuery('<li></li>');

            // Make an <a> tag, give it a class for styling
            var link = jQuery('<a></a>');
            link.addClass('item-link');

            // Links open up the markers on the map, clicking them doesn't actually go anywhere
            link.attr('href', 'javascript:void(0);');
            link.attr('role', 'button');

            // Each <li> starts with the title of the item
            link.text(marker.options.title);

            // Clicking the link should take us to the map
            link.bind('click', {}, function (event) {
                link.toggleClass('current');

                if (that.clusterGroup) {
                    that.clusterGroup.zoomToShowLayer(marker, function () {
                        marker.fire('click');
                    });
                } else {
                    that.map.once('moveend', function () {
                        marker.fire('click');
                    });
                    that.map.flyTo(marker.getLatLng());
                }
            });

            link.appendTo(listElement);
            listElement.appendTo(list);
        });
    }
};

function OmekaMapSingle(mapDivId, center, options) {
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();
    if (options.points && options.points.length) {
        for (var i = 0; i < options.points.length; i++) {
            var pt = options.points[i];
            this.addMarker([pt.latitude, pt.longitude], {}, pt.markerHtml);
        }
        this.fitMarkers();
    }
}

function OmekaMapForm(mapDivId, center, options) {
    var that = this;
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();

    this.locationCounter = 0;
    this.markerMap = {};
    this.locationsContainer = document.getElementById('geolocation-locations-container');

    // Leaflet.draw's edit and delete toolbars require a FeatureGroup to operate on.
    this.drawnItems = new L.FeatureGroup();
    this.map.addLayer(this.drawnItems);

    var drawControl = new L.Control.Draw({
        position: 'topleft',
        draw: {
            marker: true,
            polyline: false,
            polygon: false,
            rectangle: false,
            circle: false,
            circlemarker: false,
        },
        edit: {
            featureGroup: this.drawnItems,
            edit: true,
            remove: true,
        },
    });
    this.map.addControl(drawControl);

    this.map.on(L.Draw.Event.CREATED, function (event) {
        var latlng = event.layer.getLatLng();
        var idx = that.addLocation(latlng.lat, latlng.lng, that.map.getZoom(), null, '', '');
        that.markerMap[idx].openPopup();
    });

    this.map.on(L.Draw.Event.EDITED, function (event) {
        event.layers.eachLayer(function (layer) {
            var idx = layer._geolocationIndex;
            if (idx === undefined) return;
            var latlng = layer.getLatLng();
            jQuery('[name="geolocation[' + idx + '][latitude]"]').val(latlng.lat);
            jQuery('[name="geolocation[' + idx + '][longitude]"]').val(latlng.lng);
        });
    });

    this.map.on(L.Draw.Event.DELETED, function (event) {
        event.layers.eachLayer(function (layer) {
            var idx = layer._geolocationIndex;
            if (idx === undefined) return;
            jQuery('[name^="geolocation[' + idx + ']["]').remove();
            delete that.markerMap[idx];
        });
    });

    this.map.on('zoomend', function () {
        var zoom = that.map.getZoom();
        for (var i in that.markerMap) {
            jQuery('[name="geolocation[' + i + '][zoom_level]"]').val(zoom);
        }
    });
}

OmekaMapForm.prototype = {

    addLocation: function (lat, lng, zoom, id, address, label) {
        var that = this;
        var index = this.locationCounter++;

        var marker = L.marker([lat, lng]);
        marker._geolocationIndex = index; // links this layer to its hidden form inputs
        this.drawnItems.addLayer(marker);
        this.markers.push(marker);
        this.markerBounds.extend([lat, lng]);

        var labelInput = jQuery('<input type="text" class="geolocation-popup-label">').val(label);
        var popupContent = jQuery('<div></div>')
            .append(jQuery('<label></label>').text(that.options.strings.label + ': ').append(labelInput));

        marker.bindPopup(popupContent[0], {autoPanPadding: [50, 50]});

        labelInput.on('input', function () {
            jQuery('[name="geolocation[' + index + '][label]"]').val(jQuery(this).val());
        });

        var container = jQuery(this.locationsContainer);
        var addHidden = function (field, value) {
            container.append(jQuery('<input type="hidden">').attr('name', 'geolocation[' + index + '][' + field + ']').val(value));
        };
        if (id) {
            addHidden('id', id);
        }
        addHidden('latitude', lat);
        addHidden('longitude', lng);
        addHidden('zoom_level', zoom);
        addHidden('address', address);
        addHidden('label', label);

        this.markerMap[index] = marker;
        return index;
    },

    getLocationCount: function () {
        return Object.keys(this.markerMap).length;
    },

    resize: function () {
        this.map.invalidateSize();
    }
};
