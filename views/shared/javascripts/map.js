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
        // The sidebar list groups by _geolocationItemId; _geolocationTitle labels the
        // item (its header or single entry) and _geolocationLabel labels each location
        // sub-entry.
        layer._geolocationTitle = locationData.title || '';
        layer._geolocationItemId = locationData.itemId;
        layer._geolocationLabel = locationData.label || '';
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
        // Kept so a clicked entry can clear the "current" mark from every other
        // entry, including those in nested sub-lists.
        this._listContainer = container;
        var locationString = container.data('locationString') || 'Location';
        var list = jQuery('<ul></ul>');
        list.appendTo(container);

        // Group locations by item, preserving first-appearance (load) order.
        var items = [];
        var itemsById = {};
        jQuery.each(this.locations, function (index, layer) {
            var itemId = layer._geolocationItemId;
            if (!itemsById[itemId]) {
                itemsById[itemId] = {title: layer._geolocationTitle, layers: []};
                items.push(itemsById[itemId]);
            }
            itemsById[itemId].layers.push(layer);
        });

        jQuery.each(items, function (index, item) {
            // A single unlabeled location has nothing to distinguish, so show one
            // clickable line (the item) rather than a redundant header + sub-entry.
            if (item.layers.length === 1 && !item.layers[0]._geolocationLabel) {
                var only = item.layers[0];
                that._buildListItem(list, item.title, function () { that._focusLayer(only); });
                return;
            }
            // Otherwise a non-clickable item header with its location(s) as clickable
            // sub-entries: labels surface here, and multi-location fallbacks are numbered.
            var header = jQuery('<li></li>').appendTo(list);
            jQuery('<span></span>').addClass('item-group').text(item.title).appendTo(header);
            var subList = jQuery('<ul></ul>').appendTo(header);
            jQuery.each(item.layers, function (i, layer) {
                var label = layer._geolocationLabel || (locationString + ' ' + (i + 1));
                that._buildListItem(subList, label, function () { that._focusLayer(layer); });
            });
        });
    },

    // Frame a single location and open its popup.
    _focusLayer: function (layer) {
        var openPopup = function () { layer.openPopup(); };
        if (layer instanceof L.Marker) {
            if (this.clusterGroup) {
                // A clustered marker may be hidden in a cluster. Expand it first,
                // then open the popup from zoomToShowLayer's callback.
                this.clusterGroup.zoomToShowLayer(layer, openPopup);
            } else {
                // openPopup() pans the popup into view by itself, so no separate
                // map move is needed here.
                openPopup();
            }
        } else {
            // A shape may be collapsed (by deflate) until it's framed, so
            // fitBounds brings it into view and the popup opens on 'moveend'.
            // If it's already framed, fitBounds won't move the map and no
            // 'moveend' fires, so detect that via 'movestart' (synchronous for
            // a real move) and open now, clearing the pending handlers so
            // neither can fire on a later move.
            var moved = false;
            var onMoveStart = function () { moved = true; };
            this.map.once('movestart', onMoveStart);
            this.map.once('moveend', openPopup);
            this.map.fitBounds(layer.getBounds());
            if (!moved) {
                this.map.off('movestart', onMoveStart);
                this.map.off('moveend', openPopup);
                openPopup();
            }
        }
    },

    _buildListItem: function (list, title, onClick) {
        var that = this;
        var link = jQuery('<a></a>')
            .addClass('item-link')
            .attr('href', 'javascript:void(0);')
            .attr('role', 'button')
            .text(title);
        var item = jQuery('<li></li>').append(link);
        link.bind('click', {}, function () {
            // Mark just this entry as current so the user can see which location
            // they're viewing.
            that._listContainer.find('.current').removeClass('current');
            item.addClass('current');
            onClick();
        });
        item.appendTo(list);
        return item;
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
        var isMarker = event.layerType === 'marker';
        var layer = that.addLocation({
            geometry_json: JSON.stringify(event.layer.toGeoJSON().geometry),
            zoom_level: isMarker ? that.map.getZoom() : 0
        });
        if (isMarker) {
            layer.openPopup();
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

    // Adds an editable layer (point marker or shape) from a location object and
    // records its _locationData for form submission. locationData needs only a
    // geometry_json string; id/zoom_level/address/label default sensibly.
    addLocation: function (locationData) {
        var geometry = JSON.parse(locationData.geometry_json);
        var layer = L.GeoJSON.geometryToLayer(geometry);
        this.drawnItems.addLayer(layer);
        this.locations.push(layer);

        // A point is represented by its own position; a shape by its bounding
        // box, with the box center standing in as its point (matching how
        // Location::beforeSave derives lat/lng server-side). `extent` is the
        // shape we grow the map's overall bounds by.
        var center, extent;
        if (geometry.type === 'Point') {
            extent = center = layer.getLatLng();
        } else {
            extent = layer.getBounds();
            center = extent.getCenter();
        }
        this.locationBounds.extend(extent);

        layer._locationData = {
            id: locationData.id || null,
            latitude: center.lat,
            longitude: center.lng,
            zoom_level: locationData.zoom_level || 0,
            address: locationData.address || '',
            label: locationData.label || '',
            geometry_json: locationData.geometry_json
        };

        this._bindLabelPopup(layer, layer._locationData.label);

        return layer;
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
