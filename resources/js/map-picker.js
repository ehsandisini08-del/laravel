import L from 'leaflet';
import 'leaflet-control-geocoder';
import 'leaflet-control-geocoder/dist/Control.Geocoder.css';

const SATELLITE_LAYER = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics',
});

const LABELS_LAYER = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Labels &copy; Esri',
});

const STREET_LAYER = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
});

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
        zoomControl: false,
        attributionControl: true,
    });

    L.control.zoom({ position: 'topright' }).addTo(map);

    LABELS_LAYER.addTo(map);
    SATELLITE_LAYER.addTo(map);

    L.control.layers({
        'Satelit': SATELLITE_LAYER,
        'Jalan': STREET_LAYER,
    }, null, { position: 'topleft', collapsed: false }).addTo(map);

    let marker = null;

    if (initialLat !== null && initialLng !== null) {
        marker = L.marker([initialLat, initialLng]).addTo(map);
    }

    L.Control.geocoder({
        position: 'topright',
        placeholder: 'Cari lokasi / alamat...',
        defaultMarkGeocode: false,
        queryMinLength: 3,
        errorMessage: 'Lokasi tidak ditemukan',
        showResultIcons: true,
    }).on('markgeocode', (event) => {
        const { center, name, bbox } = event.geocode;

        if (marker) {
            marker.setLatLng(center);
        } else {
            marker = L.marker(center).addTo(map);
        }

        marker.bindPopup(name).openPopup();

        if (latInput) {
            latInput.value = center.lat.toFixed(7);
        }
        if (lngInput) {
            lngInput.value = center.lng.toFixed(7);
        }

        if (bbox) {
            map.fitBounds(L.latLngBounds(bbox));
        } else {
            map.setView(center, Math.max(map.getZoom(), 15));
        }
    }).addTo(map);

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

    const fullscreenControl = L.control({ position: 'topright' });

    fullscreenControl.onAdd = function () {
        const button = L.DomUtil.create('button', 'leaflet-bar leaflet-control leaflet-control-custom');

        button.style.cssText = 'width:34px;height:34px;line-height:34px;text-align:center;background:#fff;border:2px solid rgba(0,0,0,0.2);border-radius:4px;cursor:pointer;font-size:16px;margin-top:6px;';
        button.title = 'Perbesar map (fullscreen)';
        button.innerHTML = '<span style="display:inline-block">&#x26F6;</span>';

        button.addEventListener('click', () => {
            const target = container;

            if (!document.fullscreenElement) {
                if (target.requestFullscreen) {
                    target.requestFullscreen();
                } else if (target.webkitRequestFullscreen) {
                    target.webkitRequestFullscreen();
                }
                target.style.borderRadius = '0';
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
                target.style.borderRadius = '';
            }
        });

        L.DomEvent.disableClickPropagation(button);

        return button;
    };

    fullscreenControl.addTo(map);

    return map;
}