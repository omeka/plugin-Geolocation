function OmekaMap(mapDivId, center, options) {
    this.mapDivId = mapDivId;
    this.center = center;
    this.options = options;
}

OmekaMap.prototype = {

    map: null,
    mapDivId: null,
    center: null,
    options: {},
    locations: [],
    locationBounds: null,
    clusterGroup: null,
    deflateGroup: null,

    addMarker: function (latLng, options, bindHtml) {
        var map = this.map;
        var marker = L.marker(latLng, options);

        if (this.clusterGroup) {
            this.clusterGroup.addLayer(marker);
        } else {
            marker.addTo(map);
        }

        if (bindHtml) {
            marker.bindPopup(bindHtml, {autoPanPadding: [50, 50]});
        }

        this.locations.push(marker);
        this.locationBounds.extend(latLng);
        return marker;
    },

    fitLocations: function () {
        if (!this.locationBounds.isValid()) {
            return;
        }
        var bounds = this.locationBounds;
        // fitBounds on a zero-area bounds (single point) zooms in too
        // aggressively; panTo preserves the set zoom level.
        if (bounds.getNorth() === bounds.getSouth() && bounds.getEast() === bounds.getWest()) {
            this.map.panTo(bounds.getCenter());
        } else {
            this.map.fitBounds(bounds, {padding: [25, 25]});
        }
    },

    addShapeLayer: function (geojson, bindHtml) {
        var layer = L.GeoJSON.geometryToLayer(geojson);
        if (bindHtml) {
            layer.bindPopup(bindHtml, {autoPanPadding: [50, 50]});
        }
        this.deflateGroup.addLayer(layer);
        this.locations.push(layer);
        this.locationBounds.extend(layer.getBounds());
        return layer;
    },

    addLayerFromGeometry: function (geometry, options, bindHtml) {
        var layer;
        if (geometry.type === 'Point') {
            layer = this.addMarker([geometry.coordinates[1], geometry.coordinates[0]], options, bindHtml);
        } else {
            layer = this.addShapeLayer(geometry, bindHtml);
        }
        if (bindHtml) {
            var srAlertsDiv = jQuery('#geolocation-sr-alerts');
            var title = options.title || '';
            var latlng = geometry.type === 'Point'
                ? layer.getLatLng()
                : layer.getBounds().getCenter();
            var parts = [title, srAlertsDiv.data('latString'), latlng.lat,
                srAlertsDiv.data('longString'), latlng.lng];
            var srOpenedText = parts.concat(srAlertsDiv.data('openedString')).join(' ');
            var srClosedText = parts.concat(srAlertsDiv.data('closedString')).join(' ');
            // Leaflet popup events give no screen reader feedback; announce the
            // layer title, coordinates, and open/close status.
            layer.addEventListener('popupopen', function () {
                srAlertsDiv.text(srOpenedText);
            });
            layer.addEventListener('popupclose', function () {
                srAlertsDiv.text(srClosedText);
            });
            // Popup dimensions are calculated before images load; update on
            // first open so the popup resizes correctly once the image loads.
            layer.once('popupopen', function (event) {
                var popup = event.popup;
                jQuery(popup.getElement()).find('img').one('load', function () {
                    popup.update();
                });
            });
        }
        return layer;
    },

    initMap: function () {
        var customMap = this.options.custom_map;

        if (!this.center) {
            alert('Error: The center of the map has not been set!');
            return;
        }

        this.map = L.map(this.mapDivId).setView([this.center.latitude, this.center.longitude], this.center.zoomLevel);
        this.locationBounds = L.latLngBounds();
        this.locations = [];

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
        }

        // When clustering is enabled, markerLayer routes collapsed shapes into
        // the cluster group so they cluster alongside point markers. When it is
        // disabled clusterGroup is null and Leaflet.Deflate falls back to its
        // own FeatureGroup.
        this.deflateGroup = L.deflate({
            minSize: 10,
            markerLayer: this.clusterGroup,
            greedyCollapse: false,
        });
        this.map.addLayer(this.deflateGroup);

        jQuery(this.map.getContainer()).trigger('o:geolocation:init_map', this);

        new OmekaFitControl({ position: 'topleft', omekaMap: this }).addTo(this.map);
    }
};

var OmekaFitControl = L.Control.extend({
    onAdd: function () {
        var omekaMap = this.options.omekaMap;
        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-fit-all');
        var link = L.DomUtil.create('a', '', container);
        link.href = '#';
        link.title = omekaMap.options.strings.fitAllLocations;
        link.setAttribute('role', 'button');
        link.setAttribute('aria-label', omekaMap.options.strings.fitAllLocations);
        link.innerHTML = '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M1 1h5v2H3v3H1V1zm11 0h5v5h-2V3h-3V1zM1 12h2v3h3v2H1v-5zm14 3h-3v2h5v-5h-2v3z" fill="currentColor"/></svg>';
        L.DomEvent.on(link, 'click', function (e) {
            L.DomEvent.preventDefault(e);
            L.DomEvent.stopPropagation(e);
            omekaMap.fitLocations();
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
    this.loadLocationsIntoMap(this.options.uri, this.options.params);
}

OmekaMapBrowse.prototype = {

    afterLoadItems: function () {
        if (this.options.fitLocations) {
            this.fitLocations();
        }

        if (!this.options.list) {
            return;
        }
        var listDiv = jQuery('#' + this.options.list);

        if (!listDiv.length) {
            alert('Error: You have no map links div!');
        } else {
            this.buildListLinks(listDiv);
        }
    },

    loadLocationsIntoMap: function (url, params) {
        var that = this;
        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            data: params,
            success: function(locations) {
                if (locations.length) {
                    jQuery.each(locations, function (index, locationData) {
                        that.buildLayerFromLocation(locationData);
                    });
                    return that.afterLoadItems();
                }
            }
        });
    },

    buildLayerFromLocation: function (locationData) {
        var geometry = JSON.parse(locationData.geometry_json);
        var layer = this.addLayerFromGeometry(geometry, {title: locationData.title, alt: locationData.title}, this.buildLocationContent(locationData));
        // _geolocationTitle is the label this location shows in the sidebar list.
        layer._geolocationTitle = locationData.title || '';
    },

    buildLocationContent: function (locationData) {
        var popup = jQuery('<div class="geolocation-popup">');
        var headerText = locationData.label || locationData.title;
        popup.append(jQuery('<div class="geolocation-popup-header">').text(headerText));
        if (locationData.thumbnailUrl) {
            var img = jQuery('<img>').attr({src: locationData.thumbnailUrl, alt: ''});
            var thumbLink = jQuery('<a>').addClass('view-item').attr('href', locationData.itemUrl).append(img);
            popup.append(jQuery('<div class="geolocation-popup-thumbnail">').append(thumbLink));
        }
        var titleLink = jQuery('<a>').addClass('view-item').attr('href', locationData.itemUrl).text(locationData.title);
        popup.append(jQuery('<div class="geolocation-popup-title">').append(titleLink));
        if (locationData.snippet) {
            popup.append(jQuery('<p class="geolocation-popup-description">').text(locationData.snippet));
        }
        return popup[0];
    },

    buildListLinks: function (container) {
        var that = this;
        var list = jQuery('<ul></ul>');
        list.appendTo(container);

        // this.locations holds points and shapes in load order, so the list
        // interleaves them as they were added rather than grouping by type.
        jQuery.each(this.locations, function (index, layer) {
            that._buildListItem(list, layer._geolocationTitle, function () {
                if (layer instanceof L.Marker) {
                    if (that.clusterGroup) {
                        that.clusterGroup.zoomToShowLayer(layer, function () {
                            layer.fire('click');
                        });
                    } else {
                        that.map.once('moveend', function () {
                            layer.fire('click');
                        });
                        that.map.flyTo(layer.getLatLng());
                    }
                } else {
                    that.map.once('moveend', function () {
                        layer.openPopup();
                    });
                    that.map.fitBounds(layer.getBounds());
                }
            });
        });
    },

    _buildListItem: function (list, title, onClick) {
        var link = jQuery('<a></a>')
            .addClass('item-link')
            .attr('href', 'javascript:void(0);')
            .attr('role', 'button')
            .text(title);
        link.bind('click', {}, function () {
            link.toggleClass('current');
            onClick();
        });
        jQuery('<li></li>').append(link).appendTo(list);
    }
};

function OmekaMapSingle(mapDivId, center, options) {
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();
    if (options.locations && options.locations.length) {
        for (var i = 0; i < options.locations.length; i++) {
            var pt = options.locations[i];
            this.addLayerFromGeometry(JSON.parse(pt.geometry_json), {title: pt.label, alt: pt.label}, pt.popupHtml);
        }
        this.fitLocations();
    }
}

function OmekaMapForm(mapDivId, center, options) {
    var that = this;
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();

    this.jsonInput = document.getElementById('geolocation-locations-json');

    // Leaflet.draw's edit and delete toolbars require a FeatureGroup to operate on.
    this.drawnItems = new L.FeatureGroup();
    this.map.addLayer(this.drawnItems);

    L.drawLocal.edit.toolbar.buttons.edit = options.strings.editLocations;
    L.drawLocal.edit.toolbar.buttons.editDisabled = options.strings.noLocationsToEdit;
    L.drawLocal.edit.toolbar.buttons.remove = options.strings.deleteLocations;
    L.drawLocal.edit.toolbar.buttons.removeDisabled = options.strings.noLocationsToDelete;

    var drawControl = new L.Control.Draw({
        position: 'topleft',
        draw: {
            marker: true,
            polyline: true,
            polygon: true,
            rectangle: true,
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
        if (event.layerType === 'marker') {
            var latlng = event.layer.getLatLng();
            var marker = that.addLocation(latlng.lat, latlng.lng, that.map.getZoom(), null, '', '');
            marker.openPopup();
        } else {
            that.addShape(JSON.stringify(event.layer.toGeoJSON().geometry));
        }
    });

    this.map.on(L.Draw.Event.EDITED, function (event) {
        event.layers.eachLayer(function (layer) {
            layer._locationData.geometry_json = JSON.stringify(layer.toGeoJSON().geometry);
            if (layer instanceof L.Marker) {
                var latlng = layer.getLatLng();
                layer._locationData.latitude = latlng.lat;
                layer._locationData.longitude = latlng.lng;
            }
        });
    });

    this.map.on('zoomend', function () {
        var zoom = that.map.getZoom();
        that.drawnItems.eachLayer(function (layer) {
            layer._locationData.zoom_level = zoom;
        });
    });

    jQuery(this.jsonInput).closest('form').on('submit', function () {
        var data = [];
        that.drawnItems.eachLayer(function (layer) {
            data.push(layer._locationData);
        });
        jQuery(that.jsonInput).val(JSON.stringify(data));
    });
}

OmekaMapForm.prototype = {

    addLocation: function (lat, lng, zoom, id, address, label) {
        var marker = L.marker([lat, lng]);
        this.drawnItems.addLayer(marker);
        this.locations.push(marker);
        this.locationBounds.extend([lat, lng]);

        marker._locationData = {
            id: id,
            latitude: lat,
            longitude: lng,
            zoom_level: zoom,
            address: address,
            label: label,
            geometry_json: JSON.stringify({type: 'Point', coordinates: [lng, lat]})
        };

        this._bindLabelPopup(marker, label);

        return marker;
    },

    addShape: function (geometryJson, id, label) {
        var layer = L.GeoJSON.geometryToLayer(JSON.parse(geometryJson));
        this.drawnItems.addLayer(layer);
        this.locationBounds.extend(layer.getBounds());

        var center = layer.getBounds().getCenter();
        layer._locationData = {
            id: id || null,
            latitude: center.lat,
            longitude: center.lng,
            zoom_level: 0,
            address: '',
            label: label || '',
            geometry_json: geometryJson
        };

        this._bindLabelPopup(layer, label || '');
    },

    _bindLabelPopup: function (layer, initialLabel) {
        var labelInput = jQuery('<input type="text" class="geolocation-popup-label">').val(initialLabel);
        var popupContent = jQuery('<div></div>')
            .append(jQuery('<label></label>').text(this.options.strings.label + ': ').append(labelInput));
        layer.bindPopup(popupContent[0], {autoPanPadding: [50, 50]});
        labelInput.on('input', function () {
            layer._locationData.label = jQuery(this).val();
        });
    },

    getLocationCount: function () {
        return this.drawnItems.getLayers().length;
    },

    resize: function () {
        this.map.invalidateSize();
    }
};
