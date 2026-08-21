<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $odp->kode }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $odp->nama }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ftth.odp.edit', $odp) }}" class="app-btn-primary px-4 py-2.5 text-sm">Edit</a>
                @if($odp->odc)
                <a href="{{ route('ftth.odc.show', $odp->odc) }}" class="app-btn-soft px-4 py-2.5 text-sm">← {{ $odp->odc->kode }}</a>
                @endif
                <a href="{{ route('ftth.odp.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </div>
        </div>
    </x-slot>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Info ODP --}}
            <div class="app-card p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Informasi ODP</h2>
                <dl class="space-y-3">
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Kode</dt><dd class="text-sm font-mono font-bold text-gray-900 dark:text-white">{{ $odp->kode }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Nama</dt><dd class="text-sm text-gray-900 dark:text-white">{{ $odp->nama }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">ODC</dt><dd class="text-sm text-gray-900 dark:text-white">{{ $odp->odc ? $odp->odc->kode.' — '.$odp->odc->nama : '-' }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Alamat</dt><dd class="text-sm text-gray-700 dark:text-gray-300">{{ $odp->alamat ?: '-' }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Kapasitas</dt><dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $odp->kapasitas }} port</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Terpakai</dt><dd class="text-sm text-orange-600 font-semibold">{{ $odp->port_terpakai }} port</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Tersedia</dt><dd class="text-sm text-green-600 font-semibold">{{ $odp->port_available }} port</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Status</dt><dd>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $odp->status === 'ACTIVE' ? 'bg-green-100 text-green-700' : ($odp->status === 'DOWN' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $odp->status }}
                        </span>
                    </dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Koordinat</dt><dd class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $odp->latitude ? "{$odp->latitude}, {$odp->longitude}" : '-' }}</dd></div>
                </dl>

                {{-- Port usage bar --}}
                @php $pct = $odp->kapasitas > 0 ? ($odp->port_terpakai / $odp->kapasitas * 100) : 0; @endphp
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Penggunaan Port</span>
                        <span>{{ number_format($pct, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="h-3 rounded-full transition-all {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-500' : 'bg-orange-400') }}"
                            style="width:{{ $pct }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Mini Map --}}
            @if($odp->latitude)
            <div class="app-card overflow-hidden" style="min-height:300px">
                <div id="odp-map" style="height:300px"></div>
            </div>
            @endif
        </div>

        {{-- Visual Port Grid --}}
        <div class="app-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Visual Port</h2>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-green-500 inline-block"></span>Available</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-500 inline-block"></span>Used</span>
                </div>
            </div>

            @php
                $customersByPort = $odp->customers->keyBy('port_odp');
            @endphp

            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr))">
                @for($port = 1; $port <= $odp->kapasitas; $port++)
                    @php $customer = $customersByPort->get($port); @endphp
                    <div class="rounded-xl border-2 p-3 transition-all
                        {{ $customer
                            ? 'border-red-200 bg-red-50 dark:border-red-800/40 dark:bg-red-900/10'
                            : 'border-green-200 bg-green-50 dark:border-green-800/40 dark:bg-green-900/10' }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Port {{ str_pad($port, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="w-2.5 h-2.5 rounded-full {{ $customer ? 'bg-red-500' : 'bg-green-500' }}"></span>
                        </div>
                        @if($customer)
                            <a href="{{ route('customers.show', $customer) }}" class="block">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate hover:underline">{{ $customer->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $customer->customer_code }}</p>
                                <span class="mt-1.5 inline-flex px-1.5 py-0.5 rounded text-xs font-semibold
                                    {{ $customer->service_status?->value === 'isolated' ? 'bg-red-100 text-red-700' :
                                       ($customer->service_status?->value === 'overdue' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                                    {{ $customer->service_status?->label() ?? $customer->status }}
                                </span>
                            </a>
                        @else
                            <p class="text-xs text-green-600 dark:text-green-400 font-medium">Available</p>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </div>

    @if($odp->latitude)
    @push('scripts')
    <script>
    (function() {
        const map = L.map('odp-map', { zoomControl: false }).setView([{{ $odp->latitude }}, {{ $odp->longitude }}], 17);
        L.control.zoom({ position: 'topright' }).addTo(map);
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, attribution: '&copy; Esri' }).addTo(map);
        L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20 }).addTo(map);
        L.circleMarker([{{ $odp->latitude }}, {{ $odp->longitude }}], { radius: 12, fillColor: '#f97316', color: '#fff', weight: 3, fillOpacity: 1 })
            .bindPopup('<b>{{ $odp->kode }}</b><br>{{ $odp->nama }}<br>Port: {{ $odp->port_terpakai }}/{{ $odp->kapasitas }}').addTo(map).openPopup();
    })();
    </script>
    @endpush
    @endif
</x-admin-layout>
