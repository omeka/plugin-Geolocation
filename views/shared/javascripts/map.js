Event.observe(window, 'unload', GUnload);

OmekaMap = {};

OmekaMap.Base = Class.create();

OmekaMap.Base.prototype = {
    
    mapDiv: null,
    mapSize: 'small',
    center: {},
    markers: [],
    options: {},
    
    initialize: function(div, center, options)
    {
        // do stuff
        this.mapDiv = $(div);
        this.center = center;
        this.options = options;
        
        if (!GBrowserIsCompatible()) {
            alert("Your browser is not compatible with the Google Maps API.");
            return;
        };
        
        // Build the map.
        this.map = new GMap2(this.mapDiv); 
        switch(this.mapSize) {
            case 'small':
                this.map.addControl(new GSmallMapControl());
            break;
            case 'large':
            default:
                this.map.addControl(new GLargeMapControl());
            break;
        }
        
        if(!this.center) {
            alert('Error: The center of the map has not been set!');
            return;
        }

        // Set the center of the map.
        this.map.setCenter(new GLatLng( this.center.latitude, this.center.longitude ), this.center.zoomLevel);
        
        // Show the center marker if we have that enabled.
        if (this.center.show) {
            this.addMarker(this.center.latitude, this.center.longitude, {title: "(" + this.center.latitude + ',' + this.center.longitude + ")"}, this.center.markerHtml);
        };        
    },
    
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
}

OmekaMap.Browse = Class.create(OmekaMap.Base, {
    initialize: function($super, div, center, options)
    {
        $super(div, center, options);
        
        var kmlUrl = this.makeQuery(this.options.uri, this.options.params);
       //XML loads asynchronously, so need to call for further config only after it has executed
       this.loadKmlIntoMap(kmlUrl);
    },
    
    afterLoadItems: function()
    {
        var listDiv = $(this.options.list);

        if(!listDiv) {
          alert('Error: You have no map links div!');
        }else {
          //Create HTML links for each of the markers
          this.buildListLinks(listDiv); 
        }
    },
    
    /* Note to self: have to parse KML manually b/c GMaps API cannot access the KML behind the admin interface */
    loadKmlIntoMap: function(kmlUrl) {    
        // Try to load the KML file.
        // This doesn't work b/c Omeka builds relative URLs instead of absolute URLs (thanks, Zend!).
        // var geoXml = new GGeoXml(kmlUrl);
        // return this.map.addOverlay(geoXml);
                
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
                var placeMarks = xml.getElementsByTagName('Placemark');
            
                //If we have some placemarks, load them
                if(placeMarks && placeMarks.length > 0) {
                
                    //Make the placemarks Prototype compatible, so we can do magic on it
                    placeMarks = $A(placeMarks);
                
                    //Build each marker, given a placemark (make sure scope is bound to the OmekaMap class)
                    var buildMarker = that.buildMarkerFromPlacemark.bind(that);
                
                    //Retrieve the balloon styling from the KML file
                    that.browseBalloon = that.getBalloonStyling(xml);
                
                    placeMarks.each(buildMarker);
                
                    //We have successfully loaded some map points, so continue setting up the map object
                    return that.afterLoadItems.bind(that)();
                }else {
                
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
        
        //Build a marker, add HTML for it
            
        //Use the KML formatting (do some string sub magic)
        var gBalloon = this.browseBalloon;
        gBalloon = gBalloon.replace('$[namewithlink]', titleWithLink).replace('$[description]', body).replace('$[Snippet]', snippet);
        
        var gMarker = this.addMarker(latitude, longitude, {title: title}, gBalloon);
    },
    
    makeQuery: function(uri, params) {
        var url = uri;
        var query = 'searchJson=' + Object.toJSON(params);

		if(url.indexOf('?') != -1) {
			url += "&" + query;
		}else {
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
     var list = document.createElement('ul');
     container.appendChild(list);

     //Loop through all the markers
     this.markers.each(function(marker) {

         var listElement = $(document.createElement('li'));

         //Each <li> starts with the title of the item            

         //Make an <a> tag, give it a class for styling
         var link = $(document.createElement('a'));
         link.addClassName('item-link');

         //Links open up the markers on the map, clicking them doesn't actually go anywhere
         link.setAttribute('href', 'javascript:void(0);');
         link.innerHTML = marker.getTitle();

         //Clicking the link should take us to the map
         Event.observe(link, 'click', function() {
            GEvent.trigger(marker, 'click');
    		   this.map.panTo(marker.getPoint()); 
         }.bind(this));     

         listElement.appendChild(link);
         list.appendChild(listElement);       
     }.bind(this));
    }
});

OmekaMap.Single = Class.create(OmekaMap.Base, {
    mapSize: 'small'
});



OmekaMap.Form = Class.create(OmekaMap.Base, {
    
    mapSize: 'large',
    
    initialize: function($super, div, center, options)
    {        
        $super(div, center, options);
        
        this.formDiv = $(this.options.form.id);
             
       var that = this;
       //Make the map clickable to add location point (or points, in future)
		GEvent.addListener(this.map, 'click', function(marker, point) {
			
			//If we are clicking a new spot on the map
			if(marker == null) {
			    if (confirm('Are you sure you want to change the location of the item?')) {
    			    that.setOrMoveMarker(point);
    			    $('geolocation_address').value = '';			        
			    }
			}else {
			    that.clearForm();
			}
		});
		
		
		//Make the map update on zoom changes
    	GEvent.addListener(this.map, 'zoomend', function(oldLevel, newLevel) {
    		that.updateZoomForm();
    	});
		
		
		//Geocoder address lookup
		Event.observe('geolocation_find_location_by_address', 'click', function(){
			var address = $F('geolocation_address');

			that.findAddress(address);
			
			//Don't submit the form
			return false;
		});
		
		// make the return key in the geolocation address input box click the button to find the address
        Event.observe('geolocation_address', 'keypress', function(event){ 
            if (event.keyCode == Event.KEY_RETURN) {
                $('geolocation_find_location_by_address').click();
            }    
        });
		
		// Add the existing map point.
		if (this.options.point) {
		    var formMarker = this.addMarker(this.options.point.latitude, this.options.point.longitude);
		    this.map.setCenter(formMarker.getLatLng(), this.options.point.zoomLevel);
		};
    },
    
    findAddress: function(address) {
        if(!this.geocoder) {
            this.geocoder = new GClientGeocoder();
        }
                
        //var balloonHtml = function(address) {
        //    var html = '<div id="geocoder_confirm">';	
        //	    html += "<p>Your searched for <strong>" + address + '</strong> Is this location correct?</p>';
        //	    html += '<p><a id="confirm_address">Yes</a> <a id="wrong_address">No</a></p>';
        //	    html += '</div>';
        //	return html;
        //}
                
        //This is what happens when the geocoder finds/does not find the address
        var confirmPointChange = function(point) {

            //If the point was found, then put the marker on that spot
			if(point != null) {

			    this.clearForm();
		        var marker = this.addMarker(point.lat(), point.lng());
		        this.map.panTo(point);
    		                     
    			if (confirm('Are you sure you want to change the location of the item?')) {
			        var marker = this.setOrMoveMarker(point);
    			} else {
                    this.clearForm();
                    $('geolocation_address').focus();
    			}
                  
			    //var confirmation = $('geolocation-geocoder-confirmation');
			    //confirmation.update(balloonHtml(address));
			    
			    //Update the form and close the window
			    //Event.observe('confirm_address', 'click', function(){
			    //    var marker = this.setOrMoveMarker(point);
			    //    confirmation.update();
			    //}.bind(this));
			    
			    //Clear the form and the map
			    //Event.observe('wrong_address', 'click', function(){
			    //    this.clearForm();
			    //    confirmation.update();
				//	$('geolocation_address').focus();
			    //}.bind(this));
			
			} else {
			    
			  	//If no point was found, give us an alert
			    alert('Error: "' + address + '" was not found!');
			}
        }.bind(this);        
        
        this.geocoder.getLatLng(address, confirmPointChange);
    },
       
    setOrMoveMarker: function(point) {
        
        // Get rid of existing markers.
        if(this.markers.size() > 0) {
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
        if(point) {
            latElement.value = point.lat();
            lngElement.value = point.lng();
            zoomElement.value = this.map.getZoom();          
        }
        else {
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
        this.markers = $A();
    }
});


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
}