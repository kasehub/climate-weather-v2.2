import L from 'leaflet';

export function initializeMap() {
    const map = L.map('map').setView(CONFIG.MAP.CENTER, CONFIG.MAP.ZOOM);
    L.tileLayer(CONFIG.MAP.TILE_LAYER).addTo(map);
    return {
        map,
        markersLayer: L.layerGroup().addTo(map)
    };
}

export function updateMap(markersLayer, data) {
    markersLayer.clearLayers();
    data.forEach(entry => {
        // Your existing map update logic
    });
}