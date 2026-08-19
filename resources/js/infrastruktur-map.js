import { Map, Marker, NavigationControl, FullscreenControl, Popup } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { STYLES, STYLE_FALLBACKS, createStyleSwitcher, isDarkMode } from './basemaps';

const CABLE_COLOR = '#f59e0b';
const CABLE_WIDTH = 3;

export function initInfrastrukturMap({
    containerId,
    odcs = [],
    odps = [],
    defaultLat = -2.5,
    defaultLng = 118.0,
    defaultZoom = 5,
}) {
    const container = document.getElementById(containerId);
    if (!container) {
        return null;
    }

    let currentStyleId = isDarkMode() ? 'gelap' : 'google-satelit';
    let tileErrors = 0;
    let fallbackUsed = false;

    const map = new Map({
        container,
        style: STYLES[currentStyleId],
        center: [defaultLng, defaultLat],
        zoom: defaultZoom,
        attributionControl: { compact: true },
    });

    map.addControl(new NavigationControl({ visualizePitch: true }), 'top-right');
    map.addControl(new FullscreenControl({ container }), 'top-right');

    const applyStyle = (styleId) => {
        currentStyleId = styleId;
        tileErrors = 0;
        cablesSourceAdded = false;
        map.setStyle(STYLES[styleId]);
        map.once('styledata', () => {
            if (cables.length > 0) {
                addCablesToMap();
                startAnimation();
            }
        });
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
            applyStyle(STYLE_FALLBACKS[currentStyleId] ?? 'google-satelit');
            syncSwitcherActive();
        }
    });

    const odcsWithCoords = odcs.filter((odc) => odc.latitude !== null && odc.longitude !== null);
    const odpsWithCoords = odps.filter((odp) => odp.latitude !== null && odp.longitude !== null);

    const odcById = new Map(odcsWithCoords.map((odc) => [odc.id, odc]));

    const cables = odpsWithCoords
        .filter((odp) => odcById.has(odp.odc_id))
        .map((odp) => {
            const odc = odcById.get(odp.odc_id);

            return {
                id: `cable-${odp.id}`,
                from: [parseFloat(odc.longitude), parseFloat(odc.latitude)],
                to: [parseFloat(odp.longitude), parseFloat(odp.latitude)],
                odc,
                odp,
            };
        });

    const addMarker = ({ lat, lng, color, title, html }) => {
        const marker = new Marker({ color, scale: 1.1 })
            .setLngLat([lng, lat])
            .setPopup(new Popup({ offset: 25, closeButton: false }).setHTML(html))
            .addTo(map);

        marker.getElement().title = title;

        return marker;
    };

    let cablesSourceAdded = false;

    const addCablesToMap = () => {
        if (cablesSourceAdded || cables.length === 0) {
            return;
        }

        map.addSource('cables', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: cables.map((cable) => ({
                    type: 'Feature',
                    properties: { id: cable.id },
                    geometry: {
                        type: 'LineString',
                        coordinates: [cable.from, cable.to],
                    },
                })),
            },
        });

        map.addLayer({
            id: 'cable-glow',
            type: 'line',
            source: 'cables',
            paint: {
                'line-color': CABLE_COLOR,
                'line-width': CABLE_WIDTH + 2,
                'line-opacity': 0.15,
            },
        });

        map.addLayer({
            id: 'cable-flow',
            type: 'line',
            source: 'cables',
            paint: {
                'line-color': CABLE_COLOR,
                'line-width': CABLE_WIDTH,
                'line-opacity': 0.9,
                'line-dasharray': [2, 2],
            },
        });

        map.addSource('pulses', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: [],
            },
        });

        map.addLayer({
            id: 'pulse-dot',
            type: 'circle',
            source: 'pulses',
            paint: {
                'circle-radius': 5,
                'circle-color': '#fbbf24',
                'circle-stroke-color': '#ffffff',
                'circle-stroke-width': 2,
            },
        });

        cablesSourceAdded = true;
    };

    const startAnimation = () => {
        if (!map.getSource('pulses')) {
            return;
        }

        animateCables(map, cables);
    };

    map.on('load', () => {
        addCablesToMap();
        startAnimation();

        odcsWithCoords.forEach((odc) => {
            addMarker({
                lat: parseFloat(odc.latitude),
                lng: parseFloat(odc.longitude),
                color: '#dc2626',
                title: `${odc.kode_odc} - ${odc.nama_odc}`,
                html: `
                    <div class="font-medium text-gray-900 dark:text-white">${odc.nama_odc}</div>
                    <div class="text-xs font-mono text-blue-600 dark:text-blue-400">${odc.kode_odc}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">ODC</div>
                `,
            });
        });

        odpsWithCoords.forEach((odp) => {
            const odc = odcById.get(odp.odc_id);

            addMarker({
                lat: parseFloat(odp.latitude),
                lng: parseFloat(odp.longitude),
                color: '#16a34a',
                title: `${odp.kode_odp} - ${odp.nama_odp}`,
                html: `
                    <div class="font-medium text-gray-900 dark:text-white">${odp.nama_odp}</div>
                    <div class="text-xs font-mono text-blue-600 dark:text-blue-400">${odp.kode_odp}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${odc ? `Terhubung ke ${odc.kode_odc} (${odc.nama_odc})` : 'ODP'}</div>
                `,
            });
        });

        const points = [
            ...odcsWithCoords.map((odc) => [parseFloat(odc.longitude), parseFloat(odc.latitude)]),
            ...odpsWithCoords.map((odp) => [parseFloat(odp.longitude), parseFloat(odp.latitude)]),
        ];

        if (points.length > 0) {
            const lngs = points.map(([lng]) => lng);
            const lats = points.map(([, lat]) => lat);
            const bounds = [
                [Math.min(...lngs), Math.min(...lats)],
                [Math.max(...lngs), Math.max(...lats)],
            ];

            map.fitBounds(bounds, { padding: 60, maxZoom: 16 });
        }
    });

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

function animateCables(map, cables) {
    let flowForward = true;

    setInterval(() => {
        if (!map.getSource('cables')) {
            return;
        }

        flowForward = !flowForward;

        map.setPaintProperty('cable-flow', 'line-dasharray', flowForward ? [2, 2] : [4, 2]);
    }, 500);

    const speed = 0.0018;
    let animationId = null;

    const step = () => {
        if (!map.getSource('pulses')) {
            return;
        }

        const features = cables.map((cable) => {
            const progress = (performance.now() * speed + cable.id.length) % 1;
            const [x1, y1] = cable.from;
            const [x2, y2] = cable.to;
            const x = x1 + (x2 - x1) * progress;
            const y = y1 + (y2 - y1) * progress;

            return {
                type: 'Feature',
                properties: {},
                geometry: { type: 'Point', coordinates: [x, y] },
            };
        });

        map.getSource('pulses').setData({
            type: 'FeatureCollection',
            features,
        });

        animationId = requestAnimationFrame(step);
    };

    animationId = requestAnimationFrame(step);
}