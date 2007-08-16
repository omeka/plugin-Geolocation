	var OmekaMap = Class.create();
	
	OmekaMap.prototype = {
		initialize: function(mapDiv, options) {
			this.options = options;

			this.mapDiv = mapDiv;
			
			Event.observe(window,'load', this.makeMap.bindAsEventListener(this));
		},
		
		setUri: function(uri) {
			this.options.uri = uri;
		},
		
		//Make the map
		makeMap: function() {
			if (GBrowserIsCompatible()) {
				
				var mapElement = document.getElementById(this.mapDiv);
		 		$(mapElement).setStyle({width: (this.options.width+'px'), height: (this.options.height+'px')});

		      	var mapObj = new GMap2(mapElement);
				
			    mapObj.addControl(new GLargeMapControl());
				mapObj.addControl(new GMapTypeControl());
				
				this.setCenter(mapObj);
				
				var mapType = mapObj.getMapTypes()[0];
				mapObj.setMapType(mapType);
				
				this.mapObj = mapObj;
			
				if(this.options.uri) {
					this.populateMap(mapObj, this.options.uri);
				}else {
					this.setCenter(mapObj);
				}
		    }

		},
		
		setCenter: function(mapObj) {
			//Set the center of the map
			var setOverlay = this.options.centerOverlay;
						
			//If there are no items pulled in, or if there is more than one, then use the default values
			if(!this.items || this.items.length > 1) {
				var longitude = this.options.default.longitude;
				var latitude = this.options.default.latitude;
				var zoomLevel = this.options.default.zoomLevel;
								
			}else {
				var item = this.items[0];
				
				//Argh! Duplication
				var location = item.getElementsByTagName('location')[0];
		
				var latitude = Xml.getFloat(location, 'latitude');
				var longitude = Xml.getFloat(location, 'longitude');
				var zoomLevel = Xml.getInt(location, 'zoom_level');				
			}
			
			var point = new GLatLng(latitude, longitude);

			mapObj.setCenter(point, zoomLevel);
		
			if(setOverlay) {
				var marker = new GMarker(point);
				mapObj.addOverlay(marker);
			}
		},
		
		populateMap: function(mapObj, uri) {
			
			var that = this;
			
/*
				new Ajax.Request(uri.href, {
				parameters: uri.params,
				onComplete: function(t) {
					//Parse the XML returned by the request
					var xml = GXml.parse(t.responseText);					
			
				}
			});
*/				

				var url = uri.href;
				
				if(url.indexOf('?') != -1) {
					url += "&output=rest";
				}else {
					url += "?output=rest";
				}
/*				
				alert($H(uri.params).toQueryString());
*/				

				GDownloadUrl(url, function(data, responseCode) {
					var xml = GXml.parse(data);
				
					var items = xml.documentElement.getElementsByTagName('item');
					that.items = items;
				
					for (var i=0; i < items.length; i++) {
						var item = items[i];
						var location = item.getElementsByTagName('location')[0];
				
						var latitude = Xml.getFloat(location, 'latitude');
						var longitude = Xml.getFloat(location, 'longitude');
						var zoomlevel = Xml.getInt(location, 'zoom_level');
				
						var point = new GLatLng(latitude, longitude);				
				
						var balloon = buildBalloon(item);
				
						var marker = createMarker(point, balloon);
				
						mapObj.addOverlay(marker);
					};
					
					that.setCenter(mapObj);
			});			
	
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
	