import { Map, Marker, NavigationControl, FullscreenControl, GeolocateControl, Popup } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { STYLES, STYLE_FALLBACKS, createStyleSwitcher, isDarkMode } from './basemaps';

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

    let currentStyleId = 'google-satelit';
    let tileErrors = 0;
    let fallbackUsed = false;

    const map = new Map({
        container,
        style: STYLES[currentStyleId],
        center: [initialLng ?? defaultLng, initialLat ?? defaultLat],
        zoom: initialLat !== null ? 15 : defaultZoom,
        attributionControl: {
            compact: true,
        },
    });

    map.addControl(new NavigationControl({ visualizePitch: true }), 'top-right');
    map.addControl(new FullscreenControl({ container }), 'top-right');
    map.addControl(new GeolocateControl({
        positionOptions: { enableHighAccuracy: true },
        trackUserLocation: true,
    }), 'top-right');

    const applyStyle = (styleId) => {
        currentStyleId = styleId;
        tileErrors = 0;
        map.setStyle(STYLES[styleId]);
    };

    const styleSwitcher = createStyleSwitcher(currentStyleId, (styleId) => {
        applyStyle(styleId);
        syncSwitcherActive();
    });

    map.addControl(styleSwitcher, 'top-left');

    map.on('error', (event) => {
        if (!event.tile || fallbackUsed) {
            return;
        }

        tileErrors += 1;

        if (tileErrors >= 8) {
            fallbackUsed = true;
            applyStyle(STYLE_FALLBACKS[currentStyleId] ?? 'google');
            syncSwitcherActive();
        }
    });

    const setInputs = (lat, lng) => {
        if (latInput) {
            latInput.value = lat.toFixed(7);
        }
        if (lngInput) {
            lngInput.value = lng.toFixed(7);
        }
    };

    const placeMarker = (lat, lng, { fly = false } = {}) => {
        if (marker) {
            marker.setLngLat([lng, lat]);
        } else {
            marker = new Marker({ draggable: true })
                .setLngLat([lng, lat])
                .addTo(map);

            marker.on('dragend', () => {
                const { lng: newLng, lat: newLat } = marker.getLngLat();
                setInputs(newLat, newLng);
            });
        }

        setInputs(lat, lng);

        if (fly) {
            map.flyTo({ center: [lng, lat], zoom: Math.max(map.getZoom(), 16) });
        }
    };

    let marker = null;

    if (initialLat !== null && initialLng !== null) {
        placeMarker(initialLat, initialLng);
    }

    map.on('click', (event) => {
        const { lat, lng } = event.lngLat;
        placeMarker(lat, lng);
    });

    const searchControl = createSearchControl((result) => {
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);

        placeMarker(lat, lng, { fly: true });

        if (marker && result.display_name) {
            const popup = new Popup({ offset: 25 })
                .setLngLat([lng, lat])
                .setHTML(`<strong>${result.display_name}</strong>`)
                .addTo(map);
            setTimeout(() => popup.remove(), 6000);
        }
    });

    map.addControl(searchControl, 'top-left');

    const darkModeObserver = new MutationObserver(() => {
        if (fallbackUsed) {
            return;
        }

        if (isDarkMode() && (currentStyleId === 'jalan' || currentStyleId === 'google')) {
            applyStyle('gelap');
            syncSwitcherActive();
        } else if (!isDarkMode() && currentStyleId === 'gelap') {
            applyStyle('google');
            syncSwitcherActive();
        }
    });
    darkModeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    function syncSwitcherActive() {
        styleSwitcher.setActive?.(currentStyleId);
    }

    return map;
}

function createSearchControl(onSelect) {
    const control = { onAdd: () => buildSearchBox(onSelect), onRemove: () => {} };

    return control;
}

function buildSearchBox(onSelect) {
    const container = document.createElement('div');
    container.className = 'maplibregl-ctrl maplibregl-ctrl-group odc-search';

    const wrapper = document.createElement('div');
    wrapper.className = 'odc-search-wrapper';

    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Cari lokasi / alamat...';
    input.autocomplete = 'off';
    input.className = 'odc-search-input';

    const results = document.createElement('div');
    results.className = 'odc-search-results';
    results.style.display = 'none';

    let requestTimer = null;
    let activeResult = -1;

    const closeResults = () => {
        results.style.display = 'none';
        results.innerHTML = '';
        activeResult = -1;
    };

    const search = async () => {
        const query = input.value.trim();

        if (query.length < 3) {
            closeResults();
            return;
        }

        try {
            const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&q=${encodeURIComponent(query)}`;
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Geocoder request failed');
            }

            const data = await response.json();

            results.innerHTML = '';
            activeResult = -1;

            if (data.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'odc-search-empty';
                empty.textContent = 'Lokasi tidak ditemukan';
                results.appendChild(empty);
            }

            data.forEach((item) => {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'odc-search-result';
                row.textContent = item.display_name;

                row.addEventListener('click', () => {
                    onSelect(item);
                    input.value = item.display_name;
                    closeResults();
                });

                results.appendChild(row);
            });

            results.style.display = 'block';
        } catch (error) {
            results.innerHTML = '';
            const empty = document.createElement('div');
            empty.className = 'odc-search-empty';
            empty.textContent = 'Pencarian gagal, coba lagi';
            results.appendChild(empty);
            results.style.display = 'block';
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(requestTimer);
        requestTimer = setTimeout(search, 500);
    });

    input.addEventListener('keydown', (event) => {
        const items = results.querySelectorAll('.odc-search-result');

        if (event.key === 'ArrowDown' && items.length > 0) {
            event.preventDefault();
            activeResult = (activeResult + 1) % items.length;
            items.forEach((item, i) => item.classList.toggle('active', i === activeResult));
            items[activeResult].scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'ArrowUp' && items.length > 0) {
            event.preventDefault();
            activeResult = (activeResult - 1 + items.length) % items.length;
            items.forEach((item, i) => item.classList.toggle('active', i === activeResult));
            items[activeResult].scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter' && activeResult >= 0 && items[activeResult]) {
            event.preventDefault();
            items[activeResult].click();
        } else if (event.key === 'Escape') {
            closeResults();
        }
    });

    document.addEventListener('click', (event) => {
        if (!container.contains(event.target)) {
            closeResults();
        }
    });

    container.appendChild(wrapper);
    wrapper.appendChild(input);
    wrapper.appendChild(results);

    return container;
}