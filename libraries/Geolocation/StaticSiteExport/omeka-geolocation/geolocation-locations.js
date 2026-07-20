document.addEventListener('DOMContentLoaded', function(event) {
    // Iterate all locations maps on the page.
    document.querySelectorAll('.geolocation-locations-map').forEach(async (mapDiv) => {
        const relUrl = mapDiv.dataset.relUrl;
        const locationsResponse = await fetch(mapDiv.dataset.locationsUrl);
        const locationsData = await locationsResponse.json();

        const map = L.map(mapDiv, {
            center: [0, 0],
            zoom: 4
        });
        const featureGroup = L.featureGroup();

        // Get the locations data and add the locations to the map.
        let lastGeometry = null;
        locationsData.forEach((locationData) => {
            const popupDiv = document.createElement('div');
            const popupHeading = document.createElement('h2');
            const popupHeadingLink = document.createElement('a');
            const popupHeadingText = document.createTextNode(locationData.itemTitle);
            popupHeadingLink.href =  relUrl + 'items/' + locationData.itemID
            popupHeadingLink.appendChild(popupHeadingText);
            popupHeading.appendChild(popupHeadingLink);
            popupDiv.appendChild(popupHeading);
            if (locationData.hasThumbnail) {
                const popupImg = document.createElement('img');
                popupImg.src = relUrl + 'files/' + locationData.fileID + '/thumbnail.jpg';
                popupDiv.appendChild(popupImg);
            }

            lastGeometry = JSON.parse(locationData.geometry_json);
            const layer = L.geoJSON(lastGeometry);
            layer.bindPopup(popupDiv);
            layer.addTo(featureGroup);
        });

        map.fitBounds(featureGroup.getBounds());
        if (locationsData.length === 1 && lastGeometry.type === 'Point') {
            map.setZoom(locationsData[0].zoomLevel ?? 15);
        }

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        featureGroup.addTo(map);
    });
});
