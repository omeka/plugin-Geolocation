<?php head(); ?>

<script type="text/javascript" charset="utf-8">
//<![CDATA[
	var mapsUri = "<?php echo $_SERVER['REQUEST_URI']; ?>";
	
	//If a query string has been passed to the current URI, then append the 'output' param
	if(mapsUri.indexOf('?') != -1) {
		mapsUri += "&output=rest";
	}else {
		mapsUri += "?output=rest";
	}
		
	Event.observe( window, 'load', function() {
		var mapDiv = $('map');
		var map = new GMap2(mapDiv);
		
		populateMap(map, mapsUri);
	});
	
	function populateMap(map, uri) {
		GDownloadUrl(uri, function(data, responseCode) {
			var xml = GXml.parse(data);
			var items = xml.documentElement.getElementsByTagName('item');
			for (var i=0; i < items.length; i++) {
				var item = items[i];
				var location = item.getElementsByTagName('location')[0];
				
				var latitude = Xml.getFloat(location, 'latitude');
				var longitude = Xml.getFloat(location, 'longitude');
				var zoomlevel = Xml.getInt(location, 'zoom_level');
				
				var point = new GLatLng(latitude, longitude);				
				
				var balloon = buildBalloonHtml(item);
				
				var marker = createMarker(point, balloon);
				
				map.setCenter(point, zoomlevel);
				map.addOverlay(marker);
			};
		});			
	}
	
	
	function buildBalloonHtml(item) {
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
		marker.html = html;

	//	markerArray.push( marker );
		
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

//]]>	
</script>

<div id="map" style="width:500px;height:500px;"></div>

<?php echo pagination_links(5, null,null,null,null, uri('map/browse/')); ?>

<?php //google_map(400, 500); ?>

<?php foot(); ?>