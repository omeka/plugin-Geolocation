	var OmekaMap = Class.create();
	
	OmekaMap.prototype = {
		initialize: function(mapDiv, options) {
			this.options = options;

			this.mapDiv = $(mapDiv);
			
			this.markers = [];
			
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
		
						
				this.populateMap(mapObj);
				
				//clickable option
				if(this.options.clickable) {
					var that = this;
					GEvent.addListener(mapObj, 'click', function(marker, point) {
						that.onClickMap(marker, point);
					});					
				}
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
		
		//Right now this will process form submissions but it could also do any arbitrary thing (maybe)
		onClickMap: function(marker, point) {
			
			//If no pre-existing marker has been clicked, marker == null
			if(marker == null) {
				this.sendClickToForm(marker, point);
				
				//Add a marker to the form (or move the existing marker)
				
				//If there are no markers, add one
				if(!this.markers.length) {
					
					var newMarker = new GMarker(point);
					this.mapObj.addOverlay(newMarker);
					this.markers.push(newMarker);
					
				}
				//If there is one marker, then move it around the screen
				else if(this.markers.length == 1) {
					
					var oldMarker = this.markers[0];
					oldMarker.setPoint(point);
				}
			}
			
		},
		
		sendClickToForm: function(marker, point) {
			//If we are processing a form
			if(this.options.form) {
				
				var prefix = this.options.form;
				
				var latitude = document.getElementsByName(prefix + '[' + 'latitude' + ']')[0];
				
				latitude.value = point.lat();
				
				var longitude = document.getElementsByName(prefix + '[longitude]')[0];
				
				longitude.value = point.lng();
				
				var zoom_level = document.getElementsByName(prefix + '[zoom_level]')[0];
				
				zoom_level.value = this.mapObj.getZoom();
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
				
			link.onclick = function() {
				GEvent.trigger(marker, 'click');
			}
		}
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
	