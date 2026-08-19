const GOOGLE_ATTRIBUTION = 'Map data &copy; Google';
const CARTO_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
const ESRI_ATTRIBUTION = 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community';

const GOOGLE_ROAD_TILES = [
    'https://mt0.google.com/vt/lyrs=r&x={x}&y={y}&z={z}',
    'https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}',
    'https://mt2.google.com/vt/lyrs=r&x={x}&y={y}&z={z}',
    'https://mt3.google.com/vt/lyrs=r&x={x}&y={y}&z={z}',
];

const GOOGLE_HYBRID_TILES = [
    'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
    'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
    'https://mt2.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
    'https://mt3.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
];

const CARTO_TILES = [
    'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    'https://c.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    'https://d.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
];

const DARK_TILES = [
    'https://a.basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}{r}.png',
    'https://b.basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}{r}.png',
    'https://c.basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}{r}.png',
    'https://d.basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}{r}.png',
];

const ESRI_IMAGERY_TILES = [
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
];

const ESRI_LABELS_TILES = [
    'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
];

function rasterStyle(tileGroups, attribution) {
    const sources = {};
    const layers = [];

    tileGroups.forEach((group, index) => {
        const id = `base-${index}`;

        sources[id] = {
            type: 'raster',
            tiles: group.tiles,
            tileSize: 256,
            maxzoom: group.maxzoom ?? 20,
            attribution,
        };

        layers.push({ id, type: 'raster', source: id });
    });

    return { version: 8, sources, layers };
}

export const STYLES = {
    google: rasterStyle([{ tiles: GOOGLE_ROAD_TILES }], GOOGLE_ATTRIBUTION),
    'google-satelit': rasterStyle([{ tiles: GOOGLE_HYBRID_TILES }], GOOGLE_ATTRIBUTION),
    jalan: rasterStyle([{ tiles: CARTO_TILES }], CARTO_ATTRIBUTION),
    gelap: rasterStyle([{ tiles: DARK_TILES }], CARTO_ATTRIBUTION),
    satelit: rasterStyle([
        { tiles: ESRI_IMAGERY_TILES, maxzoom: 19 },
        { tiles: ESRI_LABELS_TILES, maxzoom: 19 },
    ], ESRI_ATTRIBUTION),
};

export const STYLE_FALLBACKS = {
    google: 'jalan',
    'google-satelit': 'satelit',
    jalan: 'google',
    gelap: 'jalan',
    satelit: 'google-satelit',
};

export const STYLE_OPTIONS = [
    { id: 'google-satelit', label: 'Satelit' },
    { id: 'google', label: 'Google' },
    { id: 'jalan', label: 'Jalan' },
    { id: 'gelap', label: 'Gelap' },
];

export function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}

export function createStyleSwitcher(initialStyleId, onSelect) {
    let activeId = initialStyleId;

    const container = document.createElement('div');
    container.className = 'maplibregl-ctrl maplibregl-ctrl-group odc-style-switcher';

    const updateButtons = () => {
        container.querySelectorAll('.odc-style-btn').forEach((button) => {
            button.classList.toggle('active', button.dataset.styleId === activeId);
        });
    };

    STYLE_OPTIONS.forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.styleId = option.id;
        button.className = 'odc-style-btn';
        button.textContent = option.label;
        button.addEventListener('click', () => {
            activeId = option.id;
            updateButtons();
            onSelect(option.id);
        });
        container.appendChild(button);
    });

    container.setActive = (styleId) => {
        activeId = styleId;
        updateButtons();
    };

    updateButtons();

    return container;
}