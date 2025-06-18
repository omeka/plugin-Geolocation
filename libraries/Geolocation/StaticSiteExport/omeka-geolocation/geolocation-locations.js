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
        locationsData.forEach((locationData) => {
            const marker = L.marker([locationData.latitude, locationData.longitude]);
            marker.addTo(featureGroup);
        });

        map.fitBounds(featureGroup.getBounds());
        if (locationsData.length === 1) {
            // Set the zoom level if there is only one location.
            map.setZoom(locationsData[0].zoomLevel ?? 15);
        }

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        featureGroup.addTo(map);
    });
});
