<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">FTTH Monitoring</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Peta jaringan fiber optik interaktif, status koneksi PPP aktif & monitoring redaman CPE</p>
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
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8" x-show="!isFullscreen">
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">ODC</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1" x-text="stats.total_odc">{{ number_format($stats['total_odc']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">ODP</p>
                <p class="text-2xl font-bold text-orange-500 dark:text-orange-400 mt-1" x-text="stats.total_odp">{{ number_format($stats['total_odp']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pelanggan</p>
                <p class="text-2xl font-bold text-gray-700 dark:text-gray-200 mt-1" x-text="stats.total_customers">{{ number_format($stats['total_customers']) }}</p>
            </div>
            <div class="app-card p-3 text-center bg-green-50/50 dark:bg-green-950/20 border-green-200/60 dark:border-green-800/40">
                <p class="text-xs text-green-700 dark:text-green-400 uppercase font-semibold tracking-wide">Online (PPP)</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1" x-text="stats.customers_online">{{ number_format($stats['customers_online']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Offline</p>
                <p class="text-2xl font-bold text-slate-500 dark:text-slate-400 mt-1" x-text="stats.customers_offline">{{ number_format($stats['customers_offline']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wide">Gangguan</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1" x-text="stats.customers_gangguan">{{ number_format($stats['customers_gangguan']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-red-600 dark:text-red-400 uppercase tracking-wide">Isolir</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1" x-text="stats.customers_isolir">{{ number_format($stats['customers_isolir']) }}</p>
            </div>
            <div class="app-card p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wide">Nonaktif</p>
                <p class="text-2xl font-bold text-gray-400 dark:text-gray-500 mt-1" x-text="stats.customers_nonaktif">{{ number_format($stats['customers_nonaktif']) }}</p>
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
                        placeholder="Cari pelanggan, kode ODP, ODC, dll..."
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
                    <option value="online">🟢 Online (PPP Active)</option>
                    <option value="offline">⚪ Offline (Terputus)</option>
                    <option value="overdue">🟡 Gangguan / Overdue</option>
                    <option value="isolated">🔴 Isolir</option>
                </select>

                {{-- Cable Animation Toggle --}}
                <button type="button" @click="toggleCables()" class="app-btn-soft px-3 py-2 text-sm flex items-center gap-1.5" :class="showCables ? 'text-blue-600 dark:text-blue-400 font-semibold ring-1 ring-blue-500/30' : 'text-gray-500'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Kabel: <b x-text="showCables ? 'ON' : 'OFF'"></b></span>
                </button>

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
            <div x-show="isFullscreen" x-cloak class="p-3 bg-gray-900/95 backdrop-blur-md border-b border-gray-800 flex flex-wrap items-center justify-between gap-3 text-white z-[1000]">
                <div class="flex items-center gap-3 flex-1 max-w-4xl">
                    <span class="font-bold text-sm text-blue-400 flex items-center gap-1.5 shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        FTTH Monitoring
                    </span>
                    <div class="relative flex-1">
                        <input
                            type="text"
                            x-model="searchQuery"
                            @input.debounce.300ms="performSearch()"
                            placeholder="Cari pelanggan, ODP, ODC..."
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
                        <option value="online">🟢 Online (PPP)</option>
                        <option value="offline">⚪ Offline</option>
                        <option value="overdue">🟡 Gangguan</option>
                        <option value="isolated">🔴 Isolir</option>
                    </select>
                    <button type="button" @click="toggleCables()" class="px-2.5 py-1.5 text-xs bg-gray-800 border border-gray-700 rounded-lg flex items-center gap-1 shrink-0" :class="showCables ? 'text-blue-400 font-semibold' : 'text-gray-400'">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Kabel: <b x-text="showCables ? 'ON' : 'OFF'"></b></span>
                    </button>
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

            {{-- Non-intrusive Syncing Indicator (Floating top-left badge) --}}
            <div x-show="isSyncing" x-cloak class="absolute top-4 left-14 z-[900] flex items-center gap-2 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md px-3 py-1.5 rounded-full shadow-lg border border-gray-200/80 dark:border-gray-700/80 text-xs font-semibold text-gray-700 dark:text-gray-200 transition-opacity">
                <svg class="animate-spin h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span>Sinkronisasi data jaringan...</span>
            </div>

            {{-- Floating Controls inside Map (top right, visible in normal mode) --}}
            <div x-show="!isFullscreen" class="absolute top-3 right-3 z-[900] flex items-center gap-2">
                <button type="button" @click="toggleCables()" class="bg-white/95 dark:bg-gray-800/95 hover:bg-white dark:hover:bg-gray-800 p-2.5 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 flex items-center gap-1.5 text-xs font-semibold backdrop-blur-sm transition-all hover:scale-105" :class="showCables ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500'" title="Nyalakan/Matikan Animasi Kabel">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Kabel: <b x-text="showCables ? 'ON' : 'OFF'"></b></span>
                </button>
                <button type="button" @click="toggleFullscreen()" class="bg-white/95 dark:bg-gray-800/95 hover:bg-white dark:hover:bg-gray-800 text-gray-800 dark:text-white p-2.5 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 flex items-center gap-1.5 text-xs font-semibold backdrop-blur-sm transition-all hover:scale-105" title="Perluas Peta ke Layar Penuh">
                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <span>Layar Penuh</span>
                </button>
            </div>
        </div>

        {{-- Legend --}}
        <div class="app-card p-4" x-show="!isFullscreen">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2.5">Legenda Peta, Koneksi & Redaman FTTH</p>
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">ODC</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-orange-500 border-2 border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">ODP (Tersedia)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full bg-red-500 border-2 border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">ODP (Penuh)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-emerald-500 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">🟢 User Online (PPP)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-slate-400 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">⚪ User Offline</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-amber-500 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300">🟡 User Gangguan</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full bg-red-600 border border-white shadow"></span>
                    <span class="text-gray-700 dark:text-gray-300">🔴 User Isolir</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-8 h-1 bg-cyan-500 rounded-full border border-cyan-300 shadow-sm animate-pulse"></span>
                    <span class="text-cyan-700 dark:text-cyan-300 font-medium">⚡ Feeder ODC ➔ ODP</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-8 h-1 bg-emerald-500 rounded-full border border-emerald-300 shadow-sm animate-pulse"></span>
                    <span class="text-emerald-700 dark:text-emerald-300 font-medium">⚡ Dropcore Online (Flow)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-8 h-1 bg-slate-400 rounded-full border border-slate-300 shadow-sm"></span>
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Dropcore Offline</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-8 h-1 bg-yellow-400 rounded-full border border-yellow-500 shadow-sm"></span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Jalur Backbone</span>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        #ftth-map { z-index: 0; }
        .leaflet-popup-content-wrapper { border-radius: 16px; padding: 0; overflow: hidden; box-shadow: 0 20px 45px rgba(0,0,0,0.35); }
        .leaflet-popup-content { margin: 0; }
        .ftth-popup { font-family: inherit; min-width: 260px; max-width: 320px; }
        .ftth-popup-header { padding: 12px 16px; color: #fff; font-weight: 700; font-size: 13px; letter-spacing: 0.02em; }
        .ftth-popup-body { padding: 14px 16px; }
        .ftth-popup-body table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .ftth-popup-body td { padding: 3.5px 0; vertical-align: top; }
        .ftth-popup-body td:first-child { color: #6b7280; width: 105px; font-weight: 500; }
        .ftth-popup-footer { padding: 8px 16px 14px; }
        .ftth-btn { display: block; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; text-align: center; transition: opacity 0.2s; }
        .ftth-btn:hover { opacity: 0.9; }

        /* === Animated Cable Lines (SVG stroke animation) === */
        @keyframes ftthFlow {
            from {
                stroke-dashoffset: 40;
            }
            to {
                stroke-dashoffset: 0;
            }
        }

        /* Feeder Cable (ODC -> ODP): Cyan Neon Pulse Flow */
        .ftth-cable-feeder {
            stroke: #0284c7;
            stroke-width: 3.5px;
            stroke-dasharray: 8, 6;
            stroke-linecap: round;
            animation: ftthFlow 1.1s linear infinite;
            filter: drop-shadow(0 0 3px rgba(2, 132, 199, 0.85));
            cursor: pointer;
            transition: stroke-width 0.2s;
        }
        .ftth-cable-feeder:hover {
            stroke: #38bdf8;
            stroke-width: 5.5px;
        }

        /* Drop Core (ODP -> Online Customer): Vivid Emerald Green */
        .ftth-cable-drop-online {
            stroke: #10b981;
            stroke-width: 2.2px;
            stroke-dasharray: 5, 5;
            stroke-linecap: round;
            animation: ftthFlow 1.4s linear infinite;
            filter: drop-shadow(0 0 2.5px rgba(16, 185, 129, 0.75));
            cursor: pointer;
        }
        .ftth-cable-drop-online:hover {
            stroke: #34d399;
            stroke-width: 4.5px;
        }

        /* Drop Core (ODP -> Offline Customer): Slate Gray */
        .ftth-cable-drop-offline {
            stroke: #94a3b8;
            stroke-width: 1.8px;
            stroke-dasharray: 4, 4;
            opacity: 0.75;
            cursor: pointer;
        }
        .ftth-cable-drop-offline:hover {
            stroke: #64748b;
            stroke-width: 3.5px;
            opacity: 1;
        }

        /* Drop Core (ODP -> Overdue / Gangguan): Amber */
        .ftth-cable-drop-overdue {
            stroke: #f59e0b;
            stroke-width: 2.2px;
            stroke-dasharray: 5, 5;
            stroke-linecap: round;
            animation: ftthFlow 1.4s linear infinite;
            filter: drop-shadow(0 0 2.5px rgba(245, 158, 11, 0.75));
            cursor: pointer;
        }
        .ftth-cable-drop-overdue:hover {
            stroke: #fbbf24;
            stroke-width: 4.5px;
        }

        /* Drop Core (ODP -> Isolated Customer): Red */
        .ftth-cable-drop-isolated {
            stroke: #ef4444;
            stroke-width: 2px;
            stroke-dasharray: 4, 4;
            stroke-linecap: round;
            animation: ftthFlow 2.8s linear infinite;
            filter: drop-shadow(0 0 2px rgba(239, 68, 68, 0.6));
            cursor: pointer;
        }
        .ftth-cable-drop-isolated:hover {
            stroke: #f87171;
            stroke-width: 4px;
        }

        /* Custom Leaflet Tooltip */
        .ftth-tooltip {
            font-size: 11px;
            padding: 5px 9px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
            line-height: 1.4;
        }
    </style>

    <script>
    function ftthMap() {
        return {
            map: null,
            searchQuery: '',
            searchResults: [],
            filterOdc: '',
            filterStatus: '',
            isSyncing: false,
            isFullscreen: false,
            showCables: true,

            stats: {
                total_odc: {{ $stats['total_odc'] }},
                total_odp: {{ $stats['total_odp'] }},
                total_customers: {{ $stats['total_customers'] }},
                customers_online: {{ $stats['customers_online'] }},
                customers_offline: {{ $stats['customers_offline'] }},
                customers_gangguan: {{ $stats['customers_gangguan'] }},
                customers_isolir: {{ $stats['customers_isolir'] }},
                customers_nonaktif: {{ $stats['customers_nonaktif'] }},
            },

            // Layer groups
            odcLayer: null,
            odpLayer: null,
            customerLayer: null,
            fiberLayer: null,
            cableFeederLayer: null,
            cableDropLayer: null,

            // Local cache dictionaries to prevent disappearance
            odpsById: {},
            odcsById: {},
            markerIndex: { odc: {}, odp: {}, customer: {} },

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

            toggleCables() {
                this.showCables = !this.showCables;
                if (this.showCables) {
                    if (!this.map.hasLayer(this.cableFeederLayer)) this.map.addLayer(this.cableFeederLayer);
                    if (!this.map.hasLayer(this.cableDropLayer)) this.map.addLayer(this.cableDropLayer);
                } else {
                    if (this.map.hasLayer(this.cableFeederLayer)) this.map.removeLayer(this.cableFeederLayer);
                    if (this.map.hasLayer(this.cableDropLayer)) this.map.removeLayer(this.cableDropLayer);
                }
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
                this.cableFeederLayer = L.layerGroup().addTo(this.map);
                this.cableDropLayer = L.layerGroup().addTo(this.map);
                this.odcLayer = L.layerGroup().addTo(this.map);
                this.odpLayer = L.layerGroup().addTo(this.map);
                this.customerLayer = L.markerClusterGroup({
                    maxClusterRadius: 50,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    disableClusteringAtZoom: 17,
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
                    '⚡ Kabel Feeder (ODC ➔ ODP)': this.cableFeederLayer,
                    '⚡ Kabel Dropcore (ODP ➔ User)': this.cableDropLayer,
                    '〰 Jalur Backbone': this.fiberLayer,
                };
                L.control.layers(baseMaps, overlays, { position: 'topright', collapsed: false }).addTo(this.map);

                // Load all data
                this.loadAll();
            },

            async loadAll() {
                this.isSyncing = true;
                try {
                    await Promise.all([
                        this.loadOdcs(),
                        this.loadOdps(),
                        this.loadFibers(),
                    ]);
                    // Load customers after ODPs are loaded to ensure drop cables connect properly
                    await this.loadCustomers();
                    this.refreshStats();
                } finally {
                    this.isSyncing = false;
                }
            },

            async refreshStats() {
                try {
                    const res = await fetch('{{ route("ftth.api.stats") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (res.ok) {
                        const newStats = await res.json();
                        this.stats = newStats;
                    }
                } catch (e) {
                    console.error('Stats refresh error', e);
                }
            },

            async loadOdcs() {
                try {
                    const res = await fetch('{{ route("ftth.api.odcs") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const odcs = await res.json();
                    this.odcLayer.clearLayers();
                    this.odcsById = {};
                    this.markerIndex.odc = {};

                    const bounds = [];

                    odcs.forEach(odc => {
                        this.odcsById[odc.id] = odc;
                        bounds.push([odc.lat, odc.lng]);

                        const marker = L.circleMarker([odc.lat, odc.lng], {
                            radius: 13,
                            fillColor: this.odcColor(odc.status),
                            color: '#ffffff',
                            weight: 3,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.odcPopup(odc), { maxWidth: 300 });
                        this.odcLayer.addLayer(marker);
                        this.markerIndex.odc[odc.id] = marker;
                    });

                    // Auto fit bounds on initial load if markers exist
                    if (bounds.length > 0 && !this.filterOdc && !this.filterStatus) {
                        this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                    }
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
                    this.cableFeederLayer.clearLayers();
                    this.markerIndex.odp = {};
                    this.odpsById = {};

                    odps.forEach(odp => {
                        this.odpsById[odp.id] = odp;

                        // 1. Gambar Kabel Feeder ODC -> ODP jika ada relasi ODC dan koordinat
                        const odcLat = odp.odc?.lat || this.odcsById[odp.odc_id]?.lat;
                        const odcLng = odp.odc?.lng || this.odcsById[odp.odc_id]?.lng;
                        const odcKode = odp.odc?.kode || this.odcsById[odp.odc_id]?.kode || 'ODC';

                        if (odcLat && odcLng && odp.lat && odp.lng) {
                            const feederLine = L.polyline([[odcLat, odcLng], [odp.lat, odp.lng]], {
                                className: 'ftth-cable-feeder',
                                weight: 3.5,
                                opacity: 0.9,
                            });
                            feederLine.bindTooltip(`⚡ <b>Kabel Feeder ODC ➔ ODP</b><br>📡 <b>${odcKode}</b> ➔ 🔶 <b>${odp.kode}</b>`, {
                                sticky: true,
                                className: 'ftth-tooltip'
                            });
                            this.cableFeederLayer.addLayer(feederLine);
                        }

                        // 2. Marker ODP
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
                    this.isSyncing = true;
                    const params = new URLSearchParams();
                    if (this.filterStatus) params.set('service_status', this.filterStatus);
                    if (this.filterOdc) params.set('odc_id', this.filterOdc);
                    
                    const res = await fetch(`{{ route("ftth.api.customers") }}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const customers = await res.json();
                    
                    this.customerLayer.clearLayers();
                    this.cableDropLayer.clearLayers();
                    this.markerIndex.customer = {};

                    customers.forEach(c => {
                        // Koordinat ODP (bisa dari c.odp atau lookup this.odpsById)
                        const odpLat = c.odp?.lat || this.odpsById[c.odp_id]?.lat;
                        const odpLng = c.odp?.lng || this.odpsById[c.odp_id]?.lng;
                        const odpKode = c.odp?.kode || this.odpsById[c.odp_id]?.kode || 'ODP';

                        // 1. Gambar Kabel Dropcore ODP -> Pelanggan
                        if (odpLat && odpLng && c.lat && c.lng) {
                            let cableClass = 'ftth-cable-drop-online';
                            let statusBadge = '🟢 Online';

                            if (c.service_status === 'isolated') {
                                cableClass = 'ftth-cable-drop-isolated';
                                statusBadge = '🔴 Isolir';
                            } else if (c.service_status === 'overdue') {
                                cableClass = 'ftth-cable-drop-overdue';
                                statusBadge = '🟡 Gangguan';
                            } else if (!c.is_online) {
                                cableClass = 'ftth-cable-drop-offline';
                                statusBadge = '⚪ Offline';
                            }

                            const rxDisplay = c.rx_power ? ` | <b>RX:</b> ${this.formatRxPower(c.rx_power)}` : '';

                            const dropLine = L.polyline([[odpLat, odpLng], [c.lat, c.lng]], {
                                className: cableClass,
                                weight: c.is_online ? 2.2 : 1.8,
                                opacity: c.is_online ? 0.9 : 0.75,
                            });
                            dropLine.bindTooltip(`⚡ <b>Kabel Dropcore</b> (${statusBadge})<br>🔶 <b>${odpKode}</b> (Port ${c.port_odp || '-'}) ➔ 👤 <b>${c.name}</b>${rxDisplay}`, {
                                sticky: true,
                                className: 'ftth-tooltip'
                            });
                            this.cableDropLayer.addLayer(dropLine);
                        }

                        // 2. Marker Pelanggan
                        const markerColor = this.customerMarkerColor(c);
                        const marker = L.circleMarker([c.lat, c.lng], {
                            radius: 7,
                            fillColor: markerColor,
                            color: '#ffffff',
                            weight: 2,
                            fillOpacity: 1,
                        });
                        marker.bindPopup(this.customerPopup(c), { maxWidth: 320 });
                        this.customerLayer.addLayer(marker);
                        this.markerIndex.customer[c.id] = marker;
                    });
                } catch (e) { console.error('Customer load error', e); } finally {
                    this.isSyncing = false;
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
                            }).bindPopup(`<div class="p-2.5 font-sans"><b>${f.nama}</b><br><small class="text-gray-500">${f.tipe_kabel || 'Fiber Line'}</small></div>`).addTo(this.fiberLayer);
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
                }, 400);
            },

            resetFilters() {
                this.filterOdc = '';
                this.filterStatus = '';
                this.searchQuery = '';
                this.searchResults = [];
                this.loadAll();
            },

            // === Colors & Formatters ===
            odcColor(status) {
                const colors = { ACTIVE: '#2563eb', WARNING: '#f59e0b', DOWN: '#dc2626', MAINTENANCE: '#8b5cf6', INACTIVE: '#6b7280' };
                return colors[status] || '#2563eb';
            },
            odpColor(status, portAvailable) {
                if (status !== 'ACTIVE') { return { WARNING: '#f59e0b', DOWN: '#dc2626', MAINTENANCE: '#8b5cf6', INACTIVE: '#6b7280' }[status] || '#6b7280'; }
                return portAvailable > 0 ? '#f97316' : '#ef4444';
            },
            customerMarkerColor(c) {
                if (c.service_status === 'isolated') return '#dc2626'; // Isolir = Red
                if (c.service_status === 'overdue') return '#f59e0b'; // Gangguan = Amber
                if (c.is_online) return '#16a34a'; // Online PPP Active = Emerald Green
                return '#64748b'; // Offline = Slate Gray
            },
            formatRxPower(val) {
                if (!val) return '-';
                const str = String(val).trim();
                return str.toLowerCase().includes('dbm') ? str : `${str} dBm`;
            },
            rxPowerStyle(val) {
                if (!val) return 'color: #9ca3af;';
                const num = parseFloat(String(val).replace(/[^0-9.-]/g, ''));
                if (isNaN(num)) return 'color: #9ca3af;';
                if (num >= -24 && num <= -14) return 'color: #16a34a; font-weight: 700;'; // Bagus / Optimal
                if (num > -27 && num < -24) return 'color: #d97706; font-weight: 700;'; // Waspada
                return 'color: #dc2626; font-weight: 700;'; // Kritis / Terlalu rendah/tinggi
            },
            rxPowerQualityBadge(val) {
                if (!val) return '';
                const num = parseFloat(String(val).replace(/[^0-9.-]/g, ''));
                if (isNaN(num)) return '';
                if (num >= -24 && num <= -14) return '<span style="background:#dcfce7;color:#15803d;padding:1px 6px;border-radius:4px;font-size:10px;margin-left:4px;">Optimal</span>';
                if (num > -27 && num < -24) return '<span style="background:#fef3c7;color:#b45309;padding:1px 6px;border-radius:4px;font-size:10px;margin-left:4px;">Waspada</span>';
                return '<span style="background:#fee2e2;color:#b91c1c;padding:1px 6px;border-radius:4px;font-size:10px;margin-left:4px;">Kritis</span>';
            },

            // === Popups ===
            odcPopup(odc) {
                return `<div class="ftth-popup font-sans">
                    <div class="ftth-popup-header" style="background:${this.odcColor(odc.status)}">
                        📡 ${odc.kode}
                    </div>
                    <div class="ftth-popup-body">
                        <table>
                            <tr><td>Nama ODC</td><td><b>${odc.nama}</b></td></tr>
                            <tr><td>Alamat</td><td>${odc.alamat || '-'}</td></tr>
                            <tr><td>Kapasitas</td><td>${odc.kapasitas} Core</td></tr>
                            <tr><td>Jumlah ODP</td><td><b>${odc.odp_count}</b> ODP terpasang</td></tr>
                            <tr><td>Status ODC</td><td><span style="color:${this.odcColor(odc.status)};font-weight:700">${odc.status}</span></td></tr>
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
                return `<div class="ftth-popup font-sans">
                    <div class="ftth-popup-header" style="background:${headerColor}">
                        🔶 ${odp.kode}
                    </div>
                    <div class="ftth-popup-body">
                        <table>
                            <tr><td>Nama ODP</td><td><b>${odp.nama}</b></td></tr>
                            <tr><td>ODC Induk</td><td>${odcInfo}</td></tr>
                            <tr><td>Kapasitas</td><td>${odp.kapasitas} port</td></tr>
                            <tr><td>Terpakai</td><td>${odp.port_terpakai} port</td></tr>
                            <tr><td>Tersedia</td><td><b style="color:#16a34a">${odp.port_available} port</b></td></tr>
                            <tr><td>Status ODP</td><td><span style="color:${headerColor};font-weight:700">${odp.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${odp.url}" class="ftth-btn" style="background:#f97316;color:#fff;">Detail ODP & Port →</a>
                    </div>
                </div>`;
            },

            customerPopup(c) {
                const markerColor = this.customerMarkerColor(c);
                
                let statusLabel = '⚪ Offline';
                let statusColor = '#64748b';
                
                if (c.service_status === 'isolated') {
                    statusLabel = '🔴 Isolir';
                    statusColor = '#dc2626';
                } else if (c.service_status === 'overdue') {
                    statusLabel = '🟡 Gangguan';
                    statusColor = '#f59e0b';
                } else if (c.is_online) {
                    statusLabel = `🟢 Online ${c.uptime ? '(' + c.uptime + ')' : ''}`;
                    statusColor = '#16a34a';
                }

                const rxHtml = c.rx_power 
                    ? `<span style="${this.rxPowerStyle(c.rx_power)} font-family: monospace;">⚡ ${this.formatRxPower(c.rx_power)}</span> ${this.rxPowerQualityBadge(c.rx_power)}`
                    : `<span style="color:#9ca3af;font-style:italic;">Belum ada data CPE</span>`;

                const cpeInfo = c.cpe?.model || c.cpe?.serial 
                    ? `<small style="color:#6b7280;">${c.cpe.model || ''} ${c.cpe.serial ? '(' + c.cpe.serial + ')' : ''}</small>`
                    : '-';

                const odpInfo = c.odp ? `${c.odp.kode} (Port ${c.port_odp || '-'})` : (this.odpsById[c.odp_id] ? `${this.odpsById[c.odp_id].kode} (Port ${c.port_odp || '-'})` : '-');

                return `<div class="ftth-popup font-sans">
                    <div class="ftth-popup-header" style="background:${markerColor}">
                        👤 ${c.customer_code} — ${c.name}
                    </div>
                    <div class="ftth-popup-body">
                        <table>
                            <tr><td>Koneksi PPP</td><td><span style="color:${statusColor};font-weight:700">${statusLabel}</span></td></tr>
                            <tr><td>Redaman (RX)</td><td>${rxHtml}</td></tr>
                            <tr><td>ODP & Port</td><td><b>${odpInfo}</b></td></tr>
                            <tr><td>Perangkat CPE</td><td>${cpeInfo}</td></tr>
                            <tr><td>Paket</td><td>${c.package || '-'}</td></tr>
                            <tr><td>Alamat</td><td><small style="color:#4b5563;">${c.address || '-'}</small></td></tr>
                        </table>
                    </div>
                    <div class="ftth-popup-footer">
                        <a href="${c.url}" class="ftth-btn" style="background:${markerColor};color:#fff;">Detail Customer →</a>
                    </div>
                </div>`;
            },
        };
    }
    </script>
    @endpush
</x-admin-layout>


