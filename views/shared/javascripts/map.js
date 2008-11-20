Event.observe(window, 'unload', GUnload);

var OmekaMap = Class.create();

OmekaMap.prototype = {
    initialize: function(mapDiv, options) {
    	this.options = options;

    	this.mapDiv = $(mapDiv);
        this.map = {};        
        this.xml = '';
        
        this.markers = $A();
        
        if (GBrowserIsCompatible()) {
            this.map = new GMap2(this.mapDiv); 
            
            switch(this.options.size) {
                case 'small':
                    this.map.addControl(new GSmallMapControl());
                    break;
                case 'large':
                default:
                    this.map.addControl(new GLargeMapControl());
                break;
            }
            
            if(!this.options.center) {
                alert('Error: The center of the map has not been set!');
            }
            
            this.map.setCenter(new GLatLng( this.options.center.latitude, this.options.center.longitude ), this.options.center.zoomLevel); 
            
            if(this.options.showCenter) {
                this.addCenterMarker();
            }
                       
           //Load KML if a URI has been passed
           if(this.options.uri) {
               var kmlUrl = this.makeQuery(this.options.uri, this.options.params);
               //XML loads asynchronously, so need to call for further config only after it has executed
               this.loadKmlIntoMap(kmlUrl);
           }else {
               this.setUp();
           }
        }
    },
    
    //Extension of initialize().  This either runs asynchronously after loading the KML file
    //or it runs immediately after initializing
    setUp: function() {
                    
            //Load the links in the sidebar
           if(this.options.list) {
              var listDiv = $(this.options.list);
              
              if(!listDiv) {
                  alert('Error: You have no map links div!');
              }else {
                  //Create HTML links for each of the markers
                  this.buildListLinks(listDiv); 
              }              
           }      
           
           //Handle the form-specific nonsense
           if(this.options.form) {
               this.formDiv = $(this.options.form.id);
               
               //If data was passed back to the form via POST, then use that instead of the KML
               //*Note: Required for persistence across invalid form submission
               if(this.options.form.posted) {
                   this.addCenterMarker();
               } 
                 
               var that = this;
               //Make the map clickable to add location point (or points, in future)
				GEvent.addListener(this.map, 'click', function(marker, point) {
					
					//If we are clicking a new spot on the map
					if(marker == null) {
						that.setOrMoveMarker(point);
					}else {
					    that.removeFormMarker(marker);
					}
				});	
				
				//Geocoder address lookup
				Event.observe('find_location_by_address', 'click', function(){
					var address = $F('geolocation_address');

					that.findAddress(address);
					
					//Don't submit the form
					return false;
				});
					
           } 
    },
    
    findAddress: function(address) {
        if(!this.geocoder) {
            this.geocoder = new GClientGeocoder();
        }
        
        var balloonHtml = function(address) {
            var html = '<div id="geocoder_balloon"><p>Is this address correct?</p>';	
        	    html += "<p><em>" + address + '</em></p>';
        	    html += '<a id="confirm_address">Yes</a>';
        	    html += '<a id="wrong_address">No</a></div>';
        	return html;
        }
                
        //This is what happens when the geocoder finds/does not find the address
        var openBalloonForPoint = function(point) {
            //If the point was found, then put the marker on that spot
			if(point != null) {
			    var marker = this.setOrMoveMarker(point);
			    
			    marker.openInfoWindowHtml(balloonHtml(address));
			    
			    //Update the form and close the window
			    Event.observe('confirm_address', 'click', function(){
			        this.setOrMoveMarker(point);
			        marker.closeInfoWindow();
			    }.bind(this));
			    
			    //Clear the form and the map
			    Event.observe('wrong_address', 'click', function(){
			        this.markers = $A();
			        this.map.clearOverlays();
			        this.updateForm();
			        marker.closeInfoWindow();
			    }.bind(this));
			}
			//If no point was found, give us an alert
			else {
			    alert('Error: "' + address + '" was not found!');
			}
        }.bind(this);        
        
        this.geocoder.getLatLng(address, openBalloonForPoint);
    },
       
    setOrMoveMarker: function(point) {
        
        //If we have a marker, move it
        if(this.markers.size() > 0) {
            
            var marker = this.markers.pop();            
            marker.setLatLng(point);
            this.markers.push(marker);
        }
        //Otherwise make a new marker
        else {
            var marker = new GMarker(point);
            this.addMarker(marker);
        }
        
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
            zoomElement.value = '';
        }        
    },
        
    //This just adds a single marker at the center spot on the form
    addCenterMarker: function() {
        
        var lat = this.options.center.latitude;
        var lng = this.options.center.longitude;
               
       //Make the center point into an overlay
       var gPoint = new GLatLng(lat, lng);
       
       //Give it a random title
       var gMarker = new GMarker(gPoint, {title: "(" + lat + ',' + lng + ")"});       
       
       this.addMarker(gMarker);
       
       return gMarker;
    },
    
    addMarker: function(marker) {
        this.map.addOverlay(marker);
        this.markers.push(marker);
    },
    
    removeFormMarker: function(marker) {
        this.map.removeOverlay(marker);
        this.updateForm();
        this.markers = $A();
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
            link.setAttribute('href', 'javascript:void(0)');
            link.innerHTML = marker.title;
                    
            //Clicking the link should take us to the map
            Event.observe(link, 'click', function() {
               GEvent.trigger(marker, 'click');
    		   this.map.panTo(marker.getPoint()); 
            }.bind(this));     
            
            listElement.appendChild(link);
            list.appendChild(listElement);       
        }.bind(this));
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
                            name
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
                    var styling = that.getBalloonStyling(xml);
                    that.browseBalloon = styling;
                
                    placeMarks.each(buildMarker);
                
                    //We have successfully loaded some map points, so continue setting up the map object
                    return that.setUp();
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
        var body = Xml.getValue(placeMark, 'description');
        var snippet = Xml.getValue(placeMark, 'Snippet');
            
        //Extract the lat/long from the KML-formatted data
        var point = placeMark.getElementsByTagName('Point')[0];
        var coordinates = Xml.getValue(point, 'coordinates').split(',');
        var longitude = coordinates[0];
        var latitude = coordinates[1];
        
        //Build a marker, add HTML for it
        var gPoint = new GLatLng(latitude, longitude);
        var gMarker = new GMarker(gPoint, {title: title});
            
        //Use the KML formatting (do some string sub magic on that bitch)
        var gBalloon = this.browseBalloon;
        gBalloon = gBalloon.replace('$[name]', title).replace('$[description]', body).replace('$[Snippet]', snippet);
        
        //Make the marker clickable to show the info on the map
        //Note that we only do this if we aren't already on the form
        if(!this.options.form) {
            gMarker.bindInfoWindowHtml(gBalloon);
        }
        
        gMarker.title = title;
        gMarker.body = body;
        gMarker.latitude = latitude;
        gMarker.longitude = longitude;
                
        this.addMarker(gMarker);       
    },
    
    makeQuery: function(uri, params) {
        var url = uri;
        var query = $H(params).toQueryString();

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
    } 
}

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