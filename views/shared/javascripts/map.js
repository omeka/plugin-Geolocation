jQuery(window).bind('unload', GUnload);

function OmekaMap(mapDivId, center, options) {
    // do stuff
    this.center = center;
    this.options = options;
    
    if (!GBrowserIsCompatible()) {
        alert("Your browser is not compatible with the Google Maps API.");
        return;
    }    
    
    // Build the map.
    this.map = new GMap2(document.getElementById(mapDivId)); 
    switch(this.mapSize) {
        case 'small':
            this.map.addControl(new GSmallMapControl());
        break;
        case 'large':
        default:
            this.map.addControl(new GLargeMapControl());
        break;
    }    
    
    if (!this.center) {
        alert('Error: The center of the map has not been set!');
        return;
    }

    // Set the center of the map.
    this.map.setCenter(new GLatLng( this.center.latitude, this.center.longitude ), this.center.zoomLevel);
    
    // Show the center marker if we have that enabled.
    if (this.center.show) {
        this.addMarker(this.center.latitude, this.center.longitude, {title: "(" + this.center.latitude + ',' + this.center.longitude + ")"}, this.center.markerHtml);
    }
}

OmekaMap.prototype = {
    
    map: null,
    mapDiv: null,
    mapSize: 'small',
    center: {},
    markers: [],
    options: {},
    
    addMarker: function(lat, lng, options, bindHtml) 
    {
        //Make the center point into an overlay
        var gPoint = new GLatLng(lat, lng);

        //Give it a random title
        var gMarker = new GMarker(gPoint, options);
        
        if (bindHtml) {
            gMarker.bindInfoWindowHtml(bindHtml);
        };
        
        this.map.addOverlay(gMarker);
        this.markers.push(gMarker);
        
        return gMarker;
    }
};

function OmekaMapBrowse(mapDiv, center, options) {
     var omekaMap = new OmekaMap(mapDiv, center, options);
     jQuery.extend(true, this, omekaMap);
     
     var kmlUrl = this.makeQuery(this.options.uri, this.options.params);
    //XML loads asynchronously, so need to call for further config only after it has executed
    this.loadKmlIntoMap(kmlUrl);
}

OmekaMapBrowse.prototype = {
    
    afterLoadItems: function()
    {
        var listDiv = jQuery(this.options.list);

        if (!listDiv) {
          alert('Error: You have no map links div!');
        } else {
          //Create HTML links for each of the markers
          this.buildListLinks(listDiv); 
        }
    },
    
    /* Note to self: have to parse KML manually b/c GMaps API cannot access the KML behind the admin interface */
    loadKmlIntoMap: function(kmlUrl) {    
                
        var that = this; 
        geoXml = new GDownloadUrl(kmlUrl, function(data, responseCode) {
        
            //Store the raw XML (debugging purposes)
            that.xml = data;
        
            //Parse the XML into an XMLDocument
            var xml = GXml.parse(data);
        
            /* KML can be parsed as:
                kml - root element
                    Placemark
                        namewithlink
                        description
                        Point - longitude,latitude
            */
            var placeMarks = jQuery(xml.getElementsByTagName('Placemark'));
        
            // If we have some placemarks, load them
            if (placeMarks.size()) {
            
                // Retrieve the balloon styling from the KML file
                that.browseBalloon = that.getBalloonStyling(xml);
            
                // Build the markers from the placemarks
                jQuery.each(placeMarks, function(index, placeMark) {
                    that.buildMarkerFromPlacemark(placeMark);
                });
            
                //We have successfully loaded some map points, so continue setting up the map object
                return that.afterLoadItems();
            } else {
                //@todo Elaborate with an error message
                return false;
            }
        });        
    },
    
    getBalloonStyling: function(xml) {
        var balloonTag = xml.getElementsByTagName('BalloonStyle')[0];
        return Xml.getValue(balloonTag, 'text');
    },
    
    //Build a marker given the KML XML Placemark data
    //I wish we could use the KML file directly, but it's behind the admin interface so no go
    buildMarkerFromPlacemark: function(placeMark) {
       //Get the info for each location on the map
        var title = Xml.getValue(placeMark, 'name');
        var titleWithLink = Xml.getValue(placeMark, 'namewithlink');
        var body = Xml.getValue(placeMark, 'description');
        var snippet = Xml.getValue(placeMark, 'Snippet');
            
        //Extract the lat/long from the KML-formatted data
        var point = placeMark.getElementsByTagName('Point')[0];
        var coordinates = Xml.getValue(point, 'coordinates').split(',');
        var longitude = coordinates[0];
        var latitude = coordinates[1];
        
        //Use the KML formatting (do some string sub magic)
        var gBalloon = this.browseBalloon;
        gBalloon = gBalloon.replace('$[namewithlink]', titleWithLink).replace('$[description]', body).replace('$[Snippet]', snippet);
        
        //Build a marker, add HTML for it
        var gMarker = this.addMarker(latitude, longitude, {title: title}, gBalloon);
    },
    
    makeQuery: function(uri, params) {
        var url = uri;
        var query = 'searchJson=' + Object.toJSON(params);
		if (url.indexOf('?') != -1) {
			url += "&" + query;
		} else {
			url += "?" + query;
		}
		return url;
    },
    
    //Calculate the zoom level given the 'range' value
    //Not currently used by this class, but possibly useful
    //http://throwless.wordpress.com/2008/02/23/gmap-geocoding-zoom-level-and-accuracy/
    calculateZoom: function(range, width, height) {
        var zoom = 18-Math.log(3.3*range/Math.sqrt(width*width+height*height))/Math.log(2);
        return zoom;
    },
    
    buildListLinks: function(container) {
        var that = this;
        var list = jQuery('<ul></ul>');
        list.appendTo(container);

        //Loop through all the markers
        jQuery.each(this.markers, function(index, marker) {
            var listElement = jQuery('<li></li>');

            // Make an <a> tag, give it a class for styling
            var link = jQuery('<a></a>');
            link.addClass('item-link');

            // Links open up the markers on the map, clicking them doesn't actually go anywhere
            link.attr('href', 'javascript:void(0);');

            // Each <li> starts with the title of the item            
            link.html(marker.getTitle());

            //Clicking the link should take us to the map
            link.bind('click', {map: that.map}, function(event) {
                GEvent.trigger(marker, 'click');
                map.panTo(marker.getPoint()); 
            });     

            link.appendTo(listElement);
            listElement.appendTo(list);
        });
    }
};

function OmekaMapSingle(mapDiv, center, options) {
    var omekaMap = new OmekaMap(mapDiv, center, options);
    jQuery.extend(true, this, omekaMap);
}
OmekaMapSingle.prototype = {
    mapSize: 'small',
};

function OmekaMapForm(mapDiv, center, options) {
    var omekaMap = new OmekaMap(mapDiv, center, options);
    jQuery.extend(true, this, omekaMap);
    
    var that = this;
    this.formDiv = jQuery(this.options.form.id);       
    //Make the map clickable to add location point (or points, in future)
    GEvent.addListener(this.map, 'click', function(marker, point) {
		//If we are clicking a new spot on the map
		if (marker == null) {
		    if (confirm('Are you sure you want to change the location of the item?')) {
			    that.setOrMoveMarker(point);
			    jQuery('#geolocation_address').val('');			        
		    }
		} else {
		    that.clearForm();
		}
	});
	
	//Make the map update on zoom changes
	GEvent.addListener(this.map, 'zoomend', function(oldLevel, newLevel) {
		that.updateZoomForm();
	});
	
	//Geocoder address lookup
	jQuery('#geolocation_find_location_by_address').bind('click', function(event){
		var address = jQuery('#geolocation_address').val();
		that.findAddress(address);
		//Don't submit the form
        event.stopPropagation();
		return false;
	});
	
	// make the return key in the geolocation address input box click the button to find the address
    jQuery('#geolocation_address').bind('keyup', function(event){ 
        if (event.which == 13) {
            jQuery('#geolocation_find_location_by_address').click();
        }
    });
	
	// Add the existing map point.
	if (this.options.point) {
	    var formMarker = this.addMarker(this.options.point.latitude, this.options.point.longitude);
	    this.map.setCenter(formMarker.getLatLng(), this.options.point.zoomLevel);
	}
}

OmekaMapForm.prototype = {
    mapSize: 'large',
    
    findAddress: function(address) {
        if (!this.geocoder) {
            this.geocoder = new GClientGeocoder();
        }
                
        //This is what happens when the geocoder finds/does not find the address
        var that = this;
        var confirmPointChange = function(point) {
            //If the point was found, then put the marker on that spot
			if (point != null) {
			    that.clearForm();
		        var marker = that.addMarker(point.lat(), point.lng());
		        that.map.panTo(point);
    		                     
    			if (confirm('Are you sure you want to change the location of the item?')) {
			        var marker = that.setOrMoveMarker(point);
    			} else {
                    that.clearForm();
                    jQuery('#geolocation_address').focus();
    			}
			} else {
			  	//If no point was found, give us an alert
			    alert('Error: "' + address + '" was not found!');
			}
        };        
        
        this.geocoder.getLatLng(address, confirmPointChange);
    },
       
    setOrMoveMarker: function(point) {
        // Get rid of existing markers.
        if (this.markers.size()) {
            this.clearForm();
        }
        var marker = this.addMarker(point.lat(), point.lng());
        this.updateForm(point);
        return marker;
    },
    
    //This is coupled to the structure of the form
    //It would be nice if it wasn't
    updateForm: function(point) {
        var latElement = document.getElementsByName('geolocation[0][latitude]')[0];
        var lngElement = document.getElementsByName('geolocation[0][longitude]')[0];
        var zoomElement = document.getElementsByName('geolocation[0][zoom_level]')[0];
        
        //If we passed a point, then set the form to that.  If there is no point, clear the form
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
    
    updateZoomForm: function() {
        var zoomElement = document.getElementsByName('geolocation[0][zoom_level]')[0];
        zoomElement.value = this.map.getZoom();
    },
    
    clearForm: function() {
        this.map.clearOverlays();
        this.markers.clear();
        this.updateForm();
    },
    
    removeFormMarker: function(marker) {
        this.map.removeOverlay(marker);
        this.updateForm();
        this.markers = new Array();
    }
};

var Xml = {};
Xml = {
	getValue: function(xml, nodeName) {
		var node = xml.getElementsByTagName(nodeName)[0];
		if(node.childNodes.length) {
			return node.childNodes[0].nodeValue;
		}
	},
	getFloat: function(xml, nodeName) {
		return parseFloat(this.getValue(xml, nodeName));
	},
	getInt: function(xml, nodeName) {
		return parseInt(this.getValue(xml, nodeName));
	}
};