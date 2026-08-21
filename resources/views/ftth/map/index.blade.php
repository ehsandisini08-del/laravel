<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">FTTH Monitoring</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Peta jaringan fiber optik interaktif</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ftth.odc.index') }}" class="app-btn-soft px-3 py-2 text-xs">ODC</a>
                <a href="{{ route('ftth.odp.index') }}" class="app-btn-soft px-3 py-2 text-xs">ODP</a>
                <a href="{{ route('ftth.fiber.index') }}" class="app-btn-soft px-3 py-2 text-xs">Fiber</a>
            </div>
        </div>
    </x-slot>

    {{-- Leaflet CSS & JS via CDN --}}
    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    @endpush

    <div class="space-y-4" x-data="ftthMap()">

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">ODC</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($stats['total_odc']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">ODP</p>
                <p class="text-2xl font-bold text-orange-500 dark:text-orange-400 mt-1">{{ number_format($stats['total_odp']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pelanggan</p>
                <p class="text-2xl font-bold text-gray-700 dark:text-gray-200 mt-1">{{ number_format($stats['total_customers']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Online</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($stats['customers_online']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Gangguan</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($stats['customers_gangguan']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Isolir</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ number_format($stats['customers_isolir']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Nonaktif</p>
                <p class="text-2xl font-bold text-gray-500 dark:text-gray-500 mt-1">{{ number_format($stats['customers_nonaktif']) }}</p>
            </div>
        </div>

        {{-- Filter & Search Bar --}}
        <div class="app-card p-4">
            <div class="flex flex-wrap gap-3">
                {{-- Search --}}
                <div class="relative flex-1 min-w-64">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    <input
                        id="ftth-search"
                        type="text"
                        x-model="searchQuery"
                        @input.debounce.300ms="performSearch()"
                        placeholder="Cari pelanggan, ODP, ODC..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    {{-- Search Results Dropdown --}}
                    <div x-show="searchResults.length > 0" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto">
                        <template x-for="result in searchResults" :key="result.type + result.id">
                            <button @click="flyToResult(result)" class="w-full px-4 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <span class="w-14 shrink-0 text-xs font-semibold px-1.5 py-0.5 rounded"
                                    :class="result.type === 'odc' ? 'bg-blue-100 text-blue-700' : result.type === 'odp' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'"
                                    x-text="result.type.toUpperCase()"></span>
                                <span x-text="result.label" class="text-gray-900 dark:text-white truncate"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Filter ODC --}}
                <select id="filter-odc" x-model="filterOdc" @change="loadOdps(); loadCustomers()" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Semua ODC</option>
                    @foreach($odcs as $odc)
                        <option value="{{ $odc->id }}">{{ $odc->kode }} — {{ $odc->nama }}</option>
                    @endforeach
                </select>

                {{-- Filter Status --}}
                <select id="filter-status" x-model="filterStatus" @change="loadCustomers()" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="active">Online</option>
                    <option value="overdue">Gangguan</option>
                    <option value="isolated">Isolir</option>
                </select>

                {{-- Reset --}}
                <button @click="resetFilters()" class="app-btn-soft px-3 py-2 text-sm">Reset</button>
            </div>
        </div>

        {{-- Map Container --}}
        <div class="relative rounded-2xl overflow-hidden shadow-xl" style="height: 65vh; min-height: 500px;">
            <div id="ftth-map" class="w-full h-full"></div>

            {{-- Loading overlay --}}
            <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 flex items-center justify-center z-[1000]">
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-3 rounded-xl shadow-lg">
                    <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Memuat data peta...</span>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="app-card p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Legenda</p>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-5 h-5 rounded-full bg-blue-600 border-2 border-white shadow"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">ODC</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-5 h-5 rounded-full bg-orange-500 border-2 border-white shadow"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">ODP</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-green-500 border-2 border-white shadow"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">Online</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-amber-500 border-2 border-white shadow"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">Gangguan</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-red-600 border-2 border-white shadow"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">Isolir</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-gray-500 border-2 border-white shadow"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">Nonaktif</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-8 h-0.5 bg-yellow-400 border-b-2 border-dashed border-yellow-400"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300">Jalur Fiber</span>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        #ftth-map { z-index: 0; }
        .leaflet-popup-content-wrapper { border-radius: 12px; padding: 0; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .leaflet-popup-content { margin: 0; }
        .ftth-popup { font-family: inherit; min-width: 220px; }
        .ftth-popup-header { padding: 12px 14px; color: #fff; font-weight: 600; font-size: 13px; }
        .ftth-popup-body { padding: 12px 14px; }
        .ftth-popup-body table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .ftth-popup-body td { padding: 2px 0; vertical-align: top; }
        .ftth-popup-body td:first-child { color: #6b7280; width: 90px; }
        .ftth-popup-footer { padding: 8px 14px 12px; }
        .ftth-btn { display: inline-block; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; text-align: center; }
    </style>

    <script>
    function ftthMap() {
        return {
            map: null,
            searchQuery: '',
            searchResults: [],
            filterOdc: '',
            filterStatus: '',
            isLoading: false,

            // Layer groups
            odcLayer: null,
            odpLayer: null,
            customerLayer: null,
            fiberLayer: null,

            // Marker refs for fly-to
            markerIndex: {},

            init() {
                this.$nextTick(() => this.initMap());
            },

            initMap() {
                // Pusat Indonesia default
                this.map = L.map('ftth-map', {
                    center: [-2.5, 118.0],
                    zoom: 5,
                    zoomControl: false,
                });

                // Zoom control top-right
                L.control.zoom({ position: 'topright' }).addTo(this.map);

                // === Tile Layers ===
                const esriSat = L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                    { attribution: 'Tiles &copy; Esri &mdash; Source: Esri, USGS, NOAA', maxZoom: 20 }
                );

                const esriLabels = L.tileLayer(
                    'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
                    { maxZoom: 20, opacity: 0.9 }
                );

                const osm = L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    { attribution: '&copy; OpenStreetMap contributors', maxZoom: 19 }
                );

                // Satellite + Labels (hybrid) by default
                const satHybrid = L.layerGroup([esriSat, esriLabels]).addTo(this.map);

                // === Layer groups ===
                this.odcLayer = L.layerGroup().addTo(this.map);
                this.odpLayer = L.layerGroup().addTo(this.map);
                this.customerLayer = L.markerClusterGroup({
                    maxClusterRadius: 60,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                }).addTo(this.map);
                this.fiberLayer = L.layerGroup().addTo(this.map);

                // Layer Control
                const baseMaps = {
                    '🛰 Satellite + Label': satHybrid,
                    '🛰 Satellite Only': esriSat,
                    '🗺 Street Map': osm,
                };
                const overlays = {
                    '📡 ODC': this.odcLayer,
                    '🔶 ODP': this.odpLayer,
                    '👥 Pelanggan': this.customerLayer,
                    '〰 Jalur Fiber': this.fiberLayer,
                };
                L.control.layers(baseMaps, overlays, { position: 'topleft', collapsed: false }).addTo(this.map);

                // Load semua data
                this.loadAll();

                // Pan saat map bergerak (debounce untuk performa)
                let moveTimer;
                this.map.on('moveend', () => {
                    clearTimeout(moveTimer);
                    moveTimer = setTimeout(() => this.loadCustomers(), 500);
                });
            },

            async loadAll() {
                await Promise.all([
                    this.loadOdcs(),
                    this.loadOdps(),
                    this.loadCustomers(),
                    this.loadFibers(),
                ]);
            },

            async loadOdcs() {
                try {
                    const res = await fetch('{{ route("ftth.api.odcs") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const odcs = await res.json();
                    this.odcLayer.clearLayers();
                    this.markerIndex.odc = {};
                    odcs.forEach(odc => {
                        const marker = L.circleMarker([odc.lat, odc.lng], {
                            radius: 14,
                            fillColor: this.odcColor(odc.status),
                            color: '#fff',
                            weight: 3,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.odcPopup(odc), { maxWidth: 280 });
                        this.odcLayer.addLayer(marker);
                        this.markerIndex.odc = this.markerIndex.odc || {};
                        this.markerIndex.odc[odc.id] = marker;
                    });
                } catch (e) { console.error('ODC load error', e); }
            },

            async loadOdps(odcId = null) {
                try {
                    let url = '{{ route("ftth.api.odps") }}';
                    const params = {};
                    if (odcId || this.filterOdc) { params.odc_id = odcId || this.filterOdc; }
                    if (Object.keys(params).length) { url += '?' + new URLSearchParams(params); }
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const odps = await res.json();
                    this.odpLayer.clearLayers();
                    this.markerIndex.odp = {};
                    odps.forEach(odp => {
                        const marker = L.circleMarker([odp.lat, odp.lng], {
                            radius: 10,
                            fillColor: this.odpColor(odp.status, odp.port_available),
                            color: '#fff',
                            weight: 2,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.odpPopup(odp), { maxWidth: 280 });
                        this.odpLayer.addLayer(marker);
                        this.markerIndex.odp[odp.id] = marker;
                    });
                } catch (e) { console.error('ODP load error', e); }
            },

            async loadCustomers() {
                try {
                    this.isLoading = true;
                    const bounds = this.map.getBounds();
                    const params = new URLSearchParams({
                        south: bounds.getSouth(),
                        west: bounds.getWest(),
                        north: bounds.getNorth(),
                        east: bounds.getEast(),
                    });
                    if (this.filterStatus) params.set('service_status', this.filterStatus);
                    if (this.filterOdc) {
                        // filter by odp in this odc would need extra, skip for bounding box simplicity
                    }
                    const res = await fetch(`{{ route("ftth.api.customers") }}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const customers = await res.json();
                    this.customerLayer.clearLayers();
                    this.markerIndex.customer = {};
                    customers.forEach(c => {
                        const marker = L.circleMarker([c.lat, c.lng], {
                            radius: 7,
                            fillColor: this.customerColor(c.status, c.service_status),
                            color: '#fff',
                            weight: 2,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.customerPopup(c), { maxWidth: 280 });
                        this.customerLayer.addLayer(marker);
                        this.markerIndex.customer[c.id] = marker;
                    });
                } catch (e) { console.error('Customer load error', e); } finally {
                    this.isLoading = false;
                }
            },

            async loadFibers() {
                try {
                    const res = await fetch('{{ route("ftth.api.fibers") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const fibers = await res.json();
                    this.fiberLayer.clearLayers();
                    fibers.forEach(f => {
                        if (f.coordinates && f.coordinates.length >= 2) {
                            L.polyline(f.coordinates, {
                                color: '#FBBF24',
                                weight: 3,
                                opacity: 0.8,
                                dashArray: '6,4',
                            }).bindPopup(`<div class="p-2"><b>${f.nama}</b><br><small>${f.tipe_kabel || ''}</small></div>`).addTo(this.fiberLayer);
                        }
                    });
                } catch (e) { console.error('Fiber load error', e); }
            },

            async performSearch() {
                if (this.searchQuery.length < 2) { this.searchResults = []; return; }
                try {
                    const res = await fetch(`{{ route("ftth.api.search") }}?q=${encodeURIComponent(this.searchQuery)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    this.searchResults = await res.json();
                } catch (e) { this.searchResults = []; }
            },

            flyToResult(result) {
                this.searchResults = [];
                this.searchQuery = result.label;
                this.map.setView([result.lat, result.lng], 18);
                setTimeout(() => {
                    const marker = this.markerIndex[result.type]?.[result.id];
                    if (marker) {
                        marker.openPopup();
                    }
                }, 500);
            },

            resetFilters() {
                this.filterOdc = '';
                this.filterStatus = '';
                this.searchQuery = '';
                this.searchResults = [];
                this.loadAll();
            },

            // === Colors ===
            odcColor(status) {
                const colors = { ACTIVE: '#2563eb', WARNING: '#f59e0b', DOWN: '#dc2626', MAINTENANCE: '#8b5cf6', INACTIVE: '#6b7280' };
                return colors[status] || '#2563eb';
            },
            odpColor(status, portAvailable) {
                if (status !== 'ACTIVE') { return { WARNING: '#f59e0b', DOWN: '#dc2626', MAINTENANCE: '#8b5cf6', INACTIVE: '#6b7280' }[status] || '#6b7280'; }
                return portAvailable > 0 ? '#f97316' : '#ef4444';
            },
            customerColor(status, serviceStatus) {
                if (serviceStatus === 'isolated') return '#dc2626';
                if (serviceStatus === 'overdue') return '#f59e0b';
                if (serviceStatus === 'active' && status === 'Active') return '#16a34a';
                if (status === 'Active') return '#16a34a';
                return '#6b7280';
            },

            // === Popups ===
            odcPopup(odc) {
                return `<div class="ftth-popup">
                    <div class="ftth-popup-header" style="background:${this.odcColor(odc.status)}">
                        📡 ${odc.kode}
                    </div>
                    <div class="ftth-popup-body">
                        <table>
                            <tr><td>Nama</td><td>${odc.nama}</td></tr>
                            <tr><td>Alamat</td><td>${odc.alamat || '-'}</td></tr>
                            <tr><td>Kapasitas</td><td>${odc.kapasitas} Core</td></tr>
                            <tr><td>Jumlah ODP</td><td>${odc.odp_count}</td></tr>
                            <tr><td>Status</td><td><span style="color:${this.odcColor(odc.status)};font-weight:600">${odc.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${odc.url}" class="ftth-btn" style="background:#2563eb;color:#fff;width:100%">Detail ODC</a>
                    </div>
                </div>`;
            },

            odpPopup(odp) {
                const odcInfo = odp.odc ? `${odp.odc.kode} — ${odp.odc.nama}` : '-';
                return `<div class="ftth-popup">
                    <div class="ftth-popup-header" style="background:#f97316">
                        🔶 ${odp.kode}
                    </div>
                    <div class="ftth-popup-body">
                        <table>
                            <tr><td>Nama</td><td>${odp.nama}</td></tr>
                            <tr><td>ODC</td><td>${odcInfo}</td></tr>
                            <tr><td>Kapasitas</td><td>${odp.kapasitas} port</td></tr>
                            <tr><td>Terpakai</td><td>${odp.port_terpakai} port</td></tr>
                            <tr><td>Tersedia</td><td><b style="color:#16a34a">${odp.port_available} port</b></td></tr>
                            <tr><td>Status</td><td>${odp.status}</td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${odp.url}" class="ftth-btn" style="background:#f97316;color:#fff;width:100%">Detail ODP</a>
                    </div>
                </div>`;
            },

            customerPopup(c) {
                const color = this.customerColor(c.status, c.service_status);
                const statusLabel = c.service_status === 'isolated' ? 'Isolir' : c.service_status === 'overdue' ? 'Gangguan' : c.status === 'Active' ? 'Online' : 'Nonaktif';
                return `<div class="ftth-popup">
                    <div class="ftth-popup-header" style="background:${color}">
                        👤 ${c.customer_code}
                    </div>
                    <div class="ftth-popup-body">
                        <table>
                            <tr><td>Nama</td><td>${c.name}</td></tr>
                            <tr><td>Alamat</td><td>${c.address || '-'}</td></tr>
                            <tr><td>Status</td><td><span style="color:${color};font-weight:600">${statusLabel}</span></td></tr>
                            <tr><td>ODP</td><td>${c.odp ? c.odp.kode : '-'}</td></tr>
                            <tr><td>Port</td><td>${c.port_odp || '-'}</td></tr>
                            <tr><td>Paket</td><td>${c.package || '-'}</td></tr>
                            <tr><td>Lat</td><td>${c.lat}</td></tr>
                            <tr><td>Lng</td><td>${c.lng}</td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${c.url}" class="ftth-btn" style="background:${color};color:#fff;width:100%">Detail Pelanggan</a>
                    </div>
                </div>`;
            },
        };
    }
    </script>
    @endpush
</x-admin-layout>
