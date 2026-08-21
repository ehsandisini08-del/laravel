<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">FTTH Monitoring</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Peta jaringan fiber optik interaktif & monitoring status pelanggan</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="$dispatch('toggle-ftth-fullscreen')" class="app-btn-primary px-3 py-2 text-xs flex items-center gap-1.5 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <span>Perluas Peta (Layar Penuh)</span>
                </button>
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

    <div class="space-y-4" x-data="ftthMap()" @toggle-ftth-fullscreen.window="toggleFullscreen()" @keydown.escape.window="if(isFullscreen) toggleFullscreen()">

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7" x-show="!isFullscreen">
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

        {{-- Filter & Search Bar (Normal mode) --}}
        <div class="app-card p-4" x-show="!isFullscreen">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search --}}
                <div class="relative flex-1 min-w-64">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    <input
                        id="ftth-search"
                        type="text"
                        x-model="searchQuery"
                        @input.debounce.300ms="performSearch()"
                        placeholder="Cari nama pelanggan, kode ODP, ODC, dll..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    {{-- Search Results Dropdown --}}
                    <div x-show="searchResults.length > 0" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 max-h-72 overflow-y-auto">
                        <template x-for="result in searchResults" :key="result.type + result.id">
                            <button @click="flyToResult(result)" class="w-full px-4 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors">
                                <span class="w-16 shrink-0 text-xs font-semibold px-2 py-0.5 rounded text-center"
                                    :class="result.type === 'odc' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : result.type === 'odp' ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300' : 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300'"
                                    x-text="result.type.toUpperCase()"></span>
                                <span x-text="result.label" class="text-gray-900 dark:text-white truncate font-medium"></span>
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
                    <option value="">Semua Status Pelanggan</option>
                    <option value="active">Online</option>
                    <option value="overdue">Gangguan / Overdue</option>
                    <option value="isolated">Isolir</option>
                </select>

                {{-- Reset --}}
                <button @click="resetFilters()" class="app-btn-soft px-3 py-2 text-sm">Reset</button>

                {{-- Fullscreen toggle button --}}
                <button @click="toggleFullscreen()" class="app-btn-primary px-3 py-2 text-sm flex items-center gap-1.5 ml-auto" title="Perluas peta ke layar penuh">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <span>Layar Penuh</span>
                </button>
            </div>
        </div>

        {{-- Map Container Wrapper (Supports Fullscreen toggle) --}}
        <div :class="isFullscreen ? 'fixed inset-0 z-[9999] w-screen h-screen bg-gray-950 flex flex-col' : 'relative rounded-2xl overflow-hidden shadow-xl border border-gray-200 dark:border-gray-700'"
             :style="isFullscreen ? '' : 'height: 72vh; min-height: 520px;'">

            {{-- Floating Toolbar when Fullscreen --}}
            <div x-show="isFullscreen" x-cloak class="p-3 bg-gray-900/90 backdrop-blur-md border-b border-gray-800 flex flex-wrap items-center justify-between gap-3 text-white z-[1000]">
                <div class="flex items-center gap-3 flex-1 max-w-2xl">
                    <span class="font-bold text-sm text-blue-400 flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        FTTH Fullscreen
                    </span>
                    <div class="relative flex-1">
                        <input
                            type="text"
                            x-model="searchQuery"
                            @input.debounce.300ms="performSearch()"
                            placeholder="Cari nama pelanggan, ODP, ODC..."
                            class="w-full pl-8 pr-4 py-1.5 text-xs rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:ring-1 focus:ring-blue-500"
                        />
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        <div x-show="searchResults.length > 0" x-cloak class="absolute z-50 w-full mt-1 bg-gray-800 rounded-lg shadow-2xl border border-gray-700 max-h-64 overflow-y-auto">
                            <template x-for="result in searchResults" :key="result.type + result.id">
                                <button @click="flyToResult(result)" class="w-full px-3 py-2 text-left text-xs hover:bg-gray-700 flex items-center gap-2 border-b border-gray-700/50 last:border-0">
                                    <span class="w-14 shrink-0 font-semibold px-1.5 py-0.5 rounded text-[10px]"
                                        :class="result.type === 'odc' ? 'bg-blue-900/60 text-blue-300' : result.type === 'odp' ? 'bg-orange-950/60 text-orange-300' : 'bg-green-950/60 text-green-300'"
                                        x-text="result.type.toUpperCase()"></span>
                                    <span x-text="result.label" class="text-gray-200 truncate"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <select x-model="filterOdc" @change="loadOdps(); loadCustomers()" class="px-2.5 py-1.5 text-xs bg-gray-800 border border-gray-700 text-white rounded-lg">
                        <option value="">Semua ODC</option>
                        @foreach($odcs as $odc)
                            <option value="{{ $odc->id }}">{{ $odc->kode }} — {{ $odc->nama }}</option>
                        @endforeach
                    </select>
                    <select x-model="filterStatus" @change="loadCustomers()" class="px-2.5 py-1.5 text-xs bg-gray-800 border border-gray-700 text-white rounded-lg">
                        <option value="">Semua Status</option>
                        <option value="active">Online</option>
                        <option value="overdue">Gangguan</option>
                        <option value="isolated">Isolir</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="resetFilters()" class="px-2.5 py-1.5 text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg">Reset</button>
                    <button @click="toggleFullscreen()" class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg flex items-center gap-1.5 shadow">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Keluar Layar Penuh (Esc)</span>
                    </button>
                </div>
            </div>

            {{-- Actual Leaflet Map DIV --}}
            <div id="ftth-map" class="w-full flex-1" style="min-height: 480px;"></div>

            {{-- Floating Expand Button inside Map (top right, visible in normal mode) --}}
            <div x-show="!isFullscreen" class="absolute top-3 right-3 z-[900]">
                <button type="button" @click="toggleFullscreen()" class="bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 text-gray-800 dark:text-white p-2.5 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 flex items-center gap-1.5 text-xs font-semibold backdrop-blur-sm transition-all hover:scale-105" title="Perluas Peta ke Layar Penuh">
                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <span>Layar Penuh</span>
                </button>
            </div>

            {{-- Loading overlay --}}
            <div x-show="isLoading" x-cloak class="absolute inset-0 bg-black/30 backdrop-blur-[2px] flex items-center justify-center z-[1000]">
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-3 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700">
                    <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Memuat data jaringan & pelanggan...</span>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="app-card p-4" x-show="!isFullscreen">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2.5">Legenda Peta FTTH</p>
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">ODC (Optical Distribution Cabinet)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-orange-500 border-2 border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">ODP (Port Tersedia)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-red-500 border-2 border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">ODP (Port Penuh / Down)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-green-500 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300">Pelanggan Online</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-amber-500 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300">Pelanggan Gangguan</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-red-600 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300">Pelanggan Isolir</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-gray-500 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300">Pelanggan Nonaktif</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-8 h-1 bg-yellow-400 rounded-full border border-yellow-500 shadow-sm"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Jalur Kabel Fiber</span>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        #ftth-map { z-index: 0; }
        .leaflet-popup-content-wrapper { border-radius: 14px; padding: 0; overflow: hidden; box-shadow: 0 20px 45px rgba(0,0,0,0.3); }
        .leaflet-popup-content { margin: 0; }
        .ftth-popup { font-family: inherit; min-width: 240px; }
        .ftth-popup-header { padding: 12px 16px; color: #fff; font-weight: 700; font-size: 13px; letter-spacing: 0.02em; }
        .ftth-popup-body { padding: 14px 16px; }
        .ftth-popup-body table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .ftth-popup-body td { padding: 3px 0; vertical-align: top; }
        .ftth-popup-body td:first-child { color: #6b7280; width: 95px; font-weight: 500; }
        .ftth-popup-footer { padding: 8px 16px 14px; }
        .ftth-btn { display: block; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; text-align: center; transition: opacity 0.2s; }
        .ftth-btn:hover { opacity: 0.9; }
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
            isFullscreen: false,

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

            toggleFullscreen() {
                this.isFullscreen = !this.isFullscreen;
                this.$nextTick(() => {
                    setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 200);
                });
            },

            initMap() {
                // Pusat Indonesia default
                this.map = L.map('ftth-map', {
                    center: [-2.5, 118.0],
                    zoom: 5,
                    zoomControl: false,
                });

                // Zoom control top-left
                L.control.zoom({ position: 'topleft' }).addTo(this.map);

                // === 1. Google Maps Hybrid (Satellite + Clear Labels) ===
                const googleHybrid = L.tileLayer(
                    'https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                    {
                        maxZoom: 20,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '&copy; Google Maps'
                    }
                );

                // === 2. Google Maps Satellite Only ===
                const googleSat = L.tileLayer(
                    'https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
                    {
                        maxZoom: 20,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '&copy; Google Maps'
                    }
                );

                // === 3. Google Maps Streets ===
                const googleStreets = L.tileLayer(
                    'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
                    {
                        maxZoom: 20,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '&copy; Google Maps'
                    }
                );

                // === 4. ESRI Satellite + Labels & Places ===
                const esriSat = L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                    { maxZoom: 20, attribution: '&copy; Esri World Imagery' }
                );
                const esriLabels = L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
                    { maxZoom: 20, opacity: 0.95 }
                );
                const esriTrans = L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}',
                    { maxZoom: 20, opacity: 0.9 }
                );
                const esriHybrid = L.layerGroup([esriSat, esriLabels, esriTrans]);

                // === 5. OpenStreetMap ===
                const osm = L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    { attribution: '&copy; OpenStreetMap contributors', maxZoom: 19 }
                );

                // Add Google Hybrid as default base map
                googleHybrid.addTo(this.map);

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
                    '🛰 Google Satelit + Label (Hybrid)': googleHybrid,
                    '🛰 Google Satelit Polos': googleSat,
                    '🗺 Google Peta Jalan (Streets)': googleStreets,
                    '🛰 ESRI Satelit + Label': esriHybrid,
                    '🗺 OpenStreetMap': osm,
                };
                const overlays = {
                    '📡 ODC': this.odcLayer,
                    '🔶 ODP': this.odpLayer,
                    '👥 Pelanggan': this.customerLayer,
                    '〰 Jalur Fiber': this.fiberLayer,
                };
                L.control.layers(baseMaps, overlays, { position: 'topright', collapsed: false }).addTo(this.map);

                // Load semua data
                this.loadAll();

                // Pan saat map bergerak (debounce untuk performa)
                let moveTimer;
                this.map.on('moveend', () => {
                    clearTimeout(moveTimer);
                    moveTimer = setTimeout(() => this.loadCustomers(), 400);
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
                            color: '#ffffff',
                            weight: 3,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.odcPopup(odc), { maxWidth: 300 });
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
                            color: '#ffffff',
                            weight: 2.5,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.odpPopup(odp), { maxWidth: 300 });
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
                    if (this.filterOdc) params.set('odc_id', this.filterOdc);
                    
                    const res = await fetch(`{{ route("ftth.api.customers") }}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const customers = await res.json();
                    this.customerLayer.clearLayers();
                    this.markerIndex.customer = {};
                    customers.forEach(c => {
                        const marker = L.circleMarker([c.lat, c.lng], {
                            radius: 7,
                            fillColor: this.customerColor(c.status, c.service_status),
                            color: '#ffffff',
                            weight: 2,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.customerPopup(c), { maxWidth: 300 });
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
                                weight: 3.5,
                                opacity: 0.85,
                                dashArray: '6,4',
                            }).bindPopup(`<div class="p-2.5"><b>${f.nama}</b><br><small class="text-gray-500">${f.tipe_kabel || 'Fiber Line'}</small></div>`).addTo(this.fiberLayer);
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
                }, 450);
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
                            <tr><td>Nama</td><td><b>${odc.nama}</b></td></tr>
                            <tr><td>Alamat</td><td>${odc.alamat || '-'}</td></tr>
                            <tr><td>Kapasitas</td><td>${odc.kapasitas} Core</td></tr>
                            <tr><td>Jumlah ODP</td><td>${odc.odp_count}</td></tr>
                            <tr><td>Status</td><td><span style="color:${this.odcColor(odc.status)};font-weight:700">${odc.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${odc.url}" class="ftth-btn" style="background:#2563eb;color:#fff;">Detail ODC →</a>
                    </div>
                </div>`;
            },

            odpPopup(odp) {
                const odcInfo = odp.odc ? `${odp.odc.kode} — ${odp.odc.nama}` : '-';
                const headerColor = this.odpColor(odp.status, odp.port_available);
                return `<div class="ftth-popup">
                    <div class="ftth-popup-header" style="background:${headerColor}">
                        🔶 ${odp.kode}
                    </div>
                    <div class="ftth-popup-body">
                        <table>
                            <tr><td>Nama</td><td><b>${odp.nama}</b></td></tr>
                            <tr><td>ODC</td><td>${odcInfo}</td></tr>
                            <tr><td>Kapasitas</td><td>${odp.kapasitas} port</td></tr>
                            <tr><td>Terpakai</td><td>${odp.port_terpakai} port</td></tr>
                            <tr><td>Tersedia</td><td><b style="color:#16a34a">${odp.port_available} port</b></td></tr>
                            <tr><td>Status</td><td><span style="color:${headerColor};font-weight:700">${odp.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${odp.url}" class="ftth-btn" style="background:#f97316;color:#fff;">Detail ODP & Port →</a>
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
                            <tr><td>Nama</td><td><b>${c.name}</b></td></tr>
                            <tr><td>Alamat</td><td>${c.address || '-'}</td></tr>
                            <tr><td>Status</td><td><span style="color:${color};font-weight:700">${statusLabel}</span></td></tr>
                            <tr><td>ODP</td><td><b>${c.odp ? c.odp.kode : '-'}</b></td></tr>
                            <tr><td>Port ODP</td><td>${c.port_odp ? 'Port ' + c.port_odp : '-'}</td></tr>
                            <tr><td>Paket</td><td>${c.package || '-'}</td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${c.url}" class="ftth-btn" style="background:${color};color:#fff;">Detail Customer →</a>
                    </div>
                </div>`;
            },
        };
    }
    </script>
    @endpush
</x-admin-layout>

