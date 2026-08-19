import L from 'leaflet';

export function initMapPicker({
    containerId,
    latInputId,
    lngInputId,
    defaultLat = -2.5,
    defaultLng = 118.0,
    defaultZoom = 5,
}) {
    const container = document.getElementById(containerId);
    if (!container) {
        return null;
    }

    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);

    const initialLat = latInput && latInput.value ? parseFloat(latInput.value) : null;
    const initialLng = lngInput && lngInput.value ? parseFloat(lngInput.value) : null;

    const map = L.map(container, {
        center: [initialLat ?? defaultLat, initialLng ?? defaultLng],
        zoom: initialLat !== null ? 15 : defaultZoom,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    let marker = null;

    if (initialLat !== null && initialLng !== null) {
        marker = L.marker([initialLat, initialLng]).addTo(map);
    }

    map.on('click', (event) => {
        const { lat, lng } = event.latlng;

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }

        if (latInput) {
            latInput.value = lat.toFixed(7);
        }
        if (lngInput) {
            lngInput.value = lng.toFixed(7);
        }
    });

    return map;
}