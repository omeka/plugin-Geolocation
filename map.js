Event.observe(window, 'unload', GUnload);

	var OmekaMap = Class.create();
	
	OmekaMap.prototype = {
		initialize: function(mapDiv, options) {
			this.options = options;

			this.mapDiv = $(mapDiv);
			
			this.markers = $A();
						
			this.options.address_zoom = 13;
						
			
			
			//Determine what kind of nonsense to run on the map depending on what is passed as an argument
						
			switch(this.options.type) {
				case 'show':
					var strategy = this.Show;
				break;
				case 'form':
					var strategy = this.Form;
					break;
				case 'browse':
				default:
				 	var strategy = this.Browse;
				break;
			}
			
			//When the window loads, generate the map
			Event.observe(window,'load', this.setup.bind(this, this.mapDiv, this.options, strategy));
		},
				
		populate: function(strategy) {			
			if(typeof this.options.uri != 'undefined') {
				var url = this.options.uri;
				
				var that = this;
				
				var query = $H(this.options.params).toQueryString();
		
				if(url.indexOf('?') != -1) {
					url += "&" + query;
				}else {
					url += "?" + query;
				}				

				var that = this;

				GDownloadUrl(url, function(data, responseCode) {
					var xml = GXml.parse(data);
					strategy.processXml.call(that, xml);
				});								
			};
			
		},
		
		setMarkerForItem: function(item, callback) {
			
			var coord = this.getCoordFromItem(item);
			
			if(!coord) return false;
			
			var point = new GLatLng(coord[0], coord[1]);
			var marker = new GMarker(point);
			
			this.map.addOverlay(marker);
	
			callback.call(this, marker, coord[2], item);
		},
				
		//Show involves one marker
		Show: {
			marker: null,
			//Take the XML and put markers out
			processXml: function(xml) {				
				var item = xml.documentElement;	
				this.setMarkerForItem(item, this.Show.processMarker);
			},
			processMarker: function(marker, zoom, item) {
				this.Show.marker = marker;
				this.map.setCenter(marker.getPoint());
				this.map.setZoom(zoom);
			},
			//Custom map setup for showing an item
			setup: function(map, options) {}
		},
		
		Browse: {
			markers: $A(),
			
			//Loop through all the items and add a clickable balloon to each one
			processXml: function(xml) {
				var items = xml.documentElement.getElementsByTagName('item');
	
				for (var i=0; i < items.length; i++) {
					var item = items[i];
					this.setMarkerForItem(item, this.Browse.processMarker);
				};							
			},
			
			//Build a balloon for the marker that will open when clicked
			//Build a link at the bottom of the browse page
			//Push the browse marker on to the stack
			processMarker: function(marker, zoom, item) {
				var balloon = browseBalloon(item);
				
				GEvent.addListener(marker, "click", function() {
					marker.openInfoWindowHtml(balloon);
				});
				
				this.Browse.pageLink(item, marker);
				
				this.Browse.markers.push(marker);
			},
			
			setup: function(map, options) {},

			//Create a link to the item that will show the corresponding info window when clicked
			pageLink: function(item, marker) {
			
				var linkDiv = $('map-links');
			
				var itemId = parseInt(item.getAttribute('id'));		
				var linkId = 'item_link_' + itemId;
				
				var title = Xml.getValue(item, 'title') || '[Untitled]';
				var shortDescription = Xml.getValue(item, 'short_description') || '';			
			
				var html = '<p class="item_link"><a href="javascript:void(0)" id="' + linkId +
				'">Find this item --&gt;</a>(' + title + ') ' + shortDescription + '</p>';

				new Insertion.Bottom(linkDiv, html);
			
				var link = $(linkId);
			
				var that = this;
				
				link.onclick = function() {
					GEvent.trigger(marker, 'click');
					that.map.panTo(marker.getPoint());
				}
			}			
		},
		
		Form: {
			marker: null,
			processXml: function(xml) {},
			//Scriptaculous display hack,
			//Map clicks should add/move/remove the single marker
			//Geocoding
			setup: function(map, options) {
				var that = this;
				
				this.Form.geocoder = new GClientGeocoder();
				
				this.Form.getFormElements(options.form);
				
				//Here's a hack that should fix the map on mouseover if Scriptaculous has messed it up
				var resizeHandler = null;
								
				resizeHandler = GEvent.addListener(map, 'mouseover', function() {
					//Resize the map on mouseover and then unregister this event so we don't do that anymore
					map.checkResize();
					that.Form.setPointFromOptions(map, options);
					GEvent.removeListener(resizeHandler);
				});	
				
				//Listen to all clicks on the map
				GEvent.addListener(map, 'click', function(marker, point) {
					
					//If we are clicking a new spot on the map
					if(marker == null) {
						that.Form.setFormToPoint(point, map);
		
						//Add a marker to the form (or move the existing marker)
						that.Form.moveMarkerToPoint(map, point);
					}
					//If we clicked on the first marker
					else {
						//Remove marker and clear the form
						map.removeOverlay(marker);
						that.Form.marker = null;
						that.Form.clearForm();
					}
					
				});					
			
			
				//Geocoding
				
				//Process the find_by_address feature
				$('find_location_by_address').onclick = function() {
					var address = $F('geolocation_address');

					that.Form.locateAddress(map, address, that.options.address_zoom);
					
					//Don't submit the form
					return false;
				}
				
				
				//When we submit the form, we want to make sure the map form has the coordinates of the marker
				Event.observe('item-form', 'submit', function() {
					var marker = that.Form.marker;
					if(marker) {
						that.Form.setFormToPoint(marker.getPoint(), map);
					}else {
						that.Form.clearForm();
					}
				});
				
			},	
			
			setPointFromOptions: function(map, options) {
				if(typeof options.point != 'undefined') {
					var point = new GLatLng(options.point.lat, options.point.lng);
					var marker = new GMarker(point);
				
					map.addOverlay(marker);
					map.setZoom(options.point.zoom);
					map.setCenter(marker.getPoint());
					this.marker = marker;							
				}				
			},
			
			locateAddress: function(map, address, zoom) {	
				//Variable scope hack
				var that = this;		
			
				if(!address.length) return false;
			
				this.geocoder.getLatLng(address, function(point) {
								
					//If the point was found, then put the marker on that spot
					if(point != null) {
						var marker = that.moveMarkerToPoint(map, point);
					
						//Open a little window that verifies the address
					
						var html = addressBalloon(address);
										
						marker.openInfoWindowHtml(html);
					
						//Clicking 'Yes' should set the form and close the window
						$('confirm_address').onclick = function() {
							that.setFormToPoint(point, map);
							marker.closeInfoWindow();
						}
					
						//Clicking 'No' should erase the form, clear the map and the marker
						$('wrong_address').onclick = function() {
							marker.closeInfoWindow();
							map.clearOverlays();
							that.marker = null;
							that.clearForm();
						}
					
						//reset the zoom on the map
						map.setZoom(parseInt(zoom));
					
					}else {
					
						//Address was not found
					
						alert(address + ' was not found!');
					}
				});
			},
		
			moveMarkerToPoint: function(map, point) {
				//If there are no markers, add one
				if(!this.marker) {
			
					var newMarker = new GMarker(point);
					map.addOverlay(newMarker);
					this.marker = newMarker;
				
					return newMarker;
				}
				//If there is one marker, then move it around the screen
				else{
			
					var oldMarker = this.marker;
					oldMarker.setPoint(point);
				
					return oldMarker;
				}							
			},
		
			clearForm: function() {			
				this.elements.each( function(each) {
					each[1].value = '';
				});
			},
		
			getFormElements: function(prefix) {
				var elements = $H();
										
				elements.latitude = document.getElementsByName(prefix + '[0][latitude]')[0];
				elements.longitude = document.getElementsByName(prefix + '[0][longitude]')[0];
				elements.zoom_level = document.getElementsByName(prefix + '[0][zoom_level]')[0];
				elements.address = document.getElementsByName(prefix + '[0][address]')[0];
				
				this.elements = elements;
			},
		
			setFormToPoint: function(point, map) {
				var els = this.elements;
							
				els.latitude.value = point.lat();
			
				els.longitude.value = point.lng();
			
				els.zoom_level.value = map.getZoom();
			}					
		},

		//Create the map object then delegate to whatever strategy for displaying the map
		setup: function(div, options, strategy) {
			if (GBrowserIsCompatible()) {
				
				div.setStyle({width: (options.width+'px'), height: (options.height+'px')});
			
				var map = new GMap2(div);
			
				//Determine what kinds of controls to give the map based on its dimensions
				var width = parseInt(options.width);
				var height = parseInt(options.height);
			
				if(width > 300) {
					//Add controls to the map
				    map.addControl(new GLargeMapControl());
					map.addControl(new GMapTypeControl());
				}else {
					map.addControl(new GSmallMapControl());
				}
			
				//Have to set the center of the map
				var center = {};
				center.lng = parseFloat(this.options.default.longitude);
				center.lat = parseFloat(this.options.default.latitude);
				center.zoom = parseInt(this.options.default.zoomLevel);
		
				center.point = new GLatLng(center.lat, center.lng);

				map.setCenter(center.point);
				map.setZoom(center.zoom);
			
				//Have to set the maptype manually (for some strange reason)
				var mapType = map.getMapTypes()[0];
				map.setMapType(mapType);
			
				this.map = map;
				
				strategy.map = map;
				strategy.setup.call(this, map, options);
				
				this.populate(strategy);
			}
		},

		getLocationFromItem: function(item) {
			var location = item.getElementsByTagName('location')[0];
			
			return location;
		},

		getCoordFromItem: function(item) {
			var loc = this.getLocationFromItem(item);
			
			if(loc) {
				var lat = Xml.getFloat(loc, 'latitude');
				var lng = Xml.getFloat(loc, 'longitude');
				var zoom = Xml.getInt(loc, 'zoom_level');
			
				return [lat, lng, zoom];					
			}
			
			return null;
		
		},
	}

function addressBalloon(address) {
	var html = '<p>Is this address correct?</p>';
	
	html += "<p><em>" + address + '</em></p>';
	
	html += '<a id="confirm_address">Yes</a>';
	
	html += '<a id="wrong_address">No</a>';
	
	return html;
}

function browseBalloon(item) {
	var description = Xml.getValue(item, 'short_description') || '';

	var img = Xml.getValue(item, 'thumbnail');

	var link = Xml.getValue(item, 'link_to_item');

	var html = '<div style="width:250px;min-height:80px;" class="balloon">';

	html += '<div class="title">' + link + '</div>';
	html += "<p>" + description + "</p>";
	html += img;
	html += '</div>';
	return html;
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
	