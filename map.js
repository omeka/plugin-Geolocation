	var OmekaMap = Class.create();
	
	OmekaMap.prototype = {
		initialize: function(mapDiv, options) {
			this.options = options;

			this.mapDiv = $(mapDiv);
			
			this.markers = $A();
						
			this.options.address_zoom = 13;
						
			this.geocoder = new GClientGeocoder();
			
			//When the window loads, generate the map
			Event.observe(window,'load', this.makeMap.bindAsEventListener(this));
		},
		
		setUri: function(uri) {
			this.options.uri = uri;
		},
		
		//Make the map
		makeMap: function() {
			if (GBrowserIsCompatible()) {
				
		 		this.mapDiv.setStyle({width: (this.options.width+'px'), height: (this.options.height+'px')});

		      	var mapObj = new GMap2(this.mapDiv);
				
				this.setControls(mapObj);
				
				this.mapObj = mapObj;
				this.setCenter();
				
				//Have to set the maptype manually (for some strange reason)
				var mapType = mapObj.getMapTypes()[0];
				mapObj.setMapType(mapType);
		
				var that = this;
				
				//Here's a hack that should fix the map on mouseover if Scriptaculous has messed it up
				var resizeHandler = null;
								
				resizeHandler = GEvent.addListener(mapObj, 'mouseover', function() {
					//Resize the map on mouseover and then unregister this event so we don't do that anymore
					mapObj.checkResize();
					GEvent.removeListener(resizeHandler);
				});
						
				this.populateMap(mapObj);
				
				//Process the map form
				if(this.options.form) {					
					
					//Process clicks on the map
					GEvent.addListener(mapObj, 'click', function(marker, point) {
						
						//If we are clicking a new spot on the map
						if(marker == null) {
							that.setFormToPoint(point);
			
							//Add a marker to the form (or move the existing marker)
							that.moveMarkerToPoint(point);
						}
						//If we clicked on the first marker
						else {
							//Remove marker and clear the form
							mapObj.removeOverlay(marker);
							that.markers = $A();
							that.clearForm();
						}
						
					});					
				
					//Process the find_by_address feature
					$('find_location_by_address').onclick = function() {
						var address = $F('geolocation_address');

						that.locateAddress(address);
						
						//Don't submit the form
						return false;
					}
				}
		    }

		},
		
		locateAddress: function(address) {	
			//Variable scope hack
			var that = this;		
			
			if(!address.length) return false;
			
			this.geocoder.getLatLng(address, function(point) {
								
				//If the point was found, then put the marker on that spot
				if(point != null) {
					var marker = that.moveMarkerToPoint(point);
					
					//Open a little window that verifies the address
					
					var html = addressBalloon(address);
										
					marker.openInfoWindowHtml(html);
					
					$('confirm_address').onclick = function() {
						that.setFormToPoint(point);
						marker.closeInfoWindow();
					}
					
					$('wrong_address').onclick = function() {
						marker.closeInfoWindow();
						that.mapObj.clearOverlays();
						that.markers = $A();
						that.clearForm();
					}
					
					//reset the zoom on the map
					that.mapObj.setZoom(parseInt(that.options.address_zoom));
					
				}else {
					
					//Address was not found
					
					alert(address + ' was not found!');
				}
			});
		},
		
		moveMarkerToPoint: function(point) {
			//If there are no markers, add one
			if(!this.markers.length) {
			
				var newMarker = new GMarker(point);
				this.mapObj.addOverlay(newMarker);
				this.markers.push(newMarker);
				
				return newMarker;
			}
			//If there is one marker, then move it around the screen
			else if(this.markers.length == 1) {
			
				var oldMarker = this.markers[0];
				oldMarker.setPoint(point);
				
				return oldMarker;
			}							
		},
		
		//Basically, set the style of controls that appear based on how big the map is
		setControls: function(mapObj) {
			var width = parseInt(this.options.width);
			var height = parseInt(this.options.height);
			
			if(width > 300) {
				//Add controls to the map
			    mapObj.addControl(new GLargeMapControl());
				mapObj.addControl(new GMapTypeControl());
			}else {
				mapObj.addControl(new GSmallMapControl());
			}
		},
		
		clearForm: function() {
			if(this.options.form) {
				this.getFormElements();
			}
			
			this.form.each( function(each) {
				each[1].value = '';
			});
		},
		
		getFormElements: function() {
			this.form = $H();
			
			var prefix = this.options.form;
			
			this.form.latitude = document.getElementsByName(prefix + '[0][latitude]')[0];
			this.form.longitude = document.getElementsByName(prefix + '[0][longitude]')[0];
			this.form.zoom_level = document.getElementsByName(prefix + '[0][zoom_level]')[0];
			this.form.address = document.getElementsByName(prefix + '[0][address]')[0];
		},
		
		setFormToPoint: function(point) {
			//If we are processing a form
			if(this.options.form) {
				
				this.getFormElements();
								
				this.form.latitude.value = point.lat();
				
				this.form.longitude.value = point.lng();
				
				this.form.zoom_level.value = this.mapObj.getZoom();
			}					
		},
		
		setCenter: function() {						
			var longitude = parseFloat(this.options.default.longitude);
			var latitude = parseFloat(this.options.default.latitude);
			var zoomLevel = parseInt(this.options.default.zoomLevel);
			
			var point = new GLatLng(latitude, longitude);

			this.mapObj.setCenter(point, zoomLevel);
			this.mapObj.setZoom(zoomLevel);
		},
		
		populateMap: function(mapObj) {

			if(this.options.uri) {
				
				var uri = this.options.uri;
				
				var that = this;
			
				var url = uri.href;
			
				var query = $H(uri.params).toQueryString();
			
				if(url.indexOf('?') != -1) {
					url += "&" + query;
				}else {
					url += "?" + query;
				}				

				GDownloadUrl(url, function(data, responseCode) {
					var xml = GXml.parse(data);
					
					switch(uri.type) {
						case 'browse':
							that.populateBrowse(xml);
							break;
						case 'show': 
							that.populateShow(xml);
							break;
					}	
				});							
			}
		},
		
		//All we need to do for populating a single-item view is add an overlay where the item is, then set the center
		populateShow: function(xml) {
			var item = xml.documentElement;
			
			var location = item.getElementsByTagName('location')[0];
			
			if(location) {
				//Make a marker from this XML location and center it
				var xyz = this.getCoordFromLocation(location);
				
				var point = new GLatLng(xyz[0], xyz[1]);
				var marker = new GMarker(point);
				this.markers.push(marker);
				
				this.mapObj.addOverlay(marker);
				this.mapObj.setCenter(point);
				this.mapObj.setZoom(xyz[2]);			
			}
		},

		getCoordFromLocation: function(loc) {
			var lat = Xml.getFloat(loc, 'latitude');
			var lng = Xml.getFloat(loc, 'longitude');
			var zoom = Xml.getInt(loc, 'zoom_level');
			
			return [lat, lng, zoom];			
		},
		
		//Loop through all the items and add a clickable balloon to each one
		populateBrowse: function(xml) {
			var items = xml.documentElement.getElementsByTagName('item');
			this.items = items;
	
			for (var i=0; i < items.length; i++) {
				var item = items[i];
				var location = item.getElementsByTagName('location')[0];
	
				var latitude = Xml.getFloat(location, 'latitude');
				var longitude = Xml.getFloat(location, 'longitude');
				var zoomlevel = Xml.getInt(location, 'zoom_level');
	
				var point = new GLatLng(latitude, longitude);				
	
				var balloon = buildBalloon(item);
	
				var marker = createMarker(point, balloon);
				
				this.buildLink(item, marker);
				
				this.mapObj.addOverlay(marker);
				
				this.markers.push(marker);
			};							
		},
		
		//Create a link to the item that will show the corresponding info window when clicked
		buildLink: function(item, marker) {
			
			var linkDiv = $('map-links');
			
			var itemId = parseInt(item.getAttribute('id'));		
			var linkId = 'item_link_' + itemId;
			var shortDescription = Xml.getValue(item, 'short_description');
			
			
			var html = '<p class="item_link"><a href="javascript:void(0)" id="' + linkId +
			'">Find this item --&gt;</a>' + shortDescription + '</p>';

			new Insertion.Bottom(linkDiv, html);
			
			var link = $(linkId);
			
			var that = this;
				
			link.onclick = function() {
				GEvent.trigger(marker, 'click');
				that.mapObj.setCenter(marker.getPoint());
			}
		}
	}

function addressBalloon(address) {
	var html = '<p>Is this address correct?</p><p>';
	
	html += "<p><em>" + address + '</em></p>';
	
	html += '<a id="confirm_address">Yes</a>';
	
	html += '<a id="wrong_address">No</a>';
	
	return html;
}

function buildBalloon(item) {
	var description = Xml.getValue(item, 'short_description');

	var img = Xml.getValue(item, 'thumbnail');

	var link = Xml.getValue(item, 'link_to_item');

	var html = '<div style="width:250px;min-height:80px;" class="balloon">';

	html += '<div class="title">' + link + '</div>';
	html += "<p>" + description + "</p>";
	html += img;
	html += '</div>';
	return html;
}		

function createMarker(point, html) {
	var marker = new GMarker(point);
	//marker.html = html;

	//markerArray.push( marker );

	GEvent.addListener(marker, "click", function() {
		marker.openInfoWindowHtml(html);
	});

	return marker;
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
	