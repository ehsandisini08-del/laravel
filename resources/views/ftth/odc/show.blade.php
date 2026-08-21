<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $odc->kode }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $odc->nama }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ftth.odc.edit', $odc) }}" class="app-btn-primary px-4 py-2.5 text-sm">Edit</a>
                <a href="{{ route('ftth.odc.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
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
            {{-- Info ODC --}}
            <div class="app-card p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Informasi ODC</h2>
                <dl class="space-y-3">
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Kode</dt><dd class="text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $odc->kode }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Nama</dt><dd class="text-sm text-gray-900 dark:text-white">{{ $odc->nama }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Alamat</dt><dd class="text-sm text-gray-700 dark:text-gray-300">{{ $odc->alamat ?: '-' }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Kapasitas</dt><dd class="text-sm text-gray-900 dark:text-white">{{ number_format($odc->kapasitas) }} Core</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Jumlah ODP</dt><dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $odc->odps->count() }}</dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Status</dt><dd>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $odc->status === 'ACTIVE' ? 'bg-green-100 text-green-700' : ($odc->status === 'DOWN' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $odc->status }}
                        </span>
                    </dd></div>
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Koordinat</dt><dd class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $odc->latitude ? "{$odc->latitude}, {$odc->longitude}" : '-' }}</dd></div>
                    @if($odc->keterangan)
                    <div class="flex gap-3"><dt class="text-sm text-gray-500 w-32 shrink-0">Keterangan</dt><dd class="text-sm text-gray-700 dark:text-gray-300">{{ $odc->keterangan }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Mini Map --}}
            @if($odc->latitude)
            <div class="app-card overflow-hidden" style="min-height:300px">
                <div id="odc-map" style="height:300px"></div>
            </div>
            @endif
        </div>

        {{-- Daftar ODP --}}
        <div class="app-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-700 px-5 py-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Daftar ODP</h2>
                <a href="{{ route('ftth.odp.create', ['odc_id' => $odc->id]) }}" class="app-btn-primary px-3 py-2 text-xs">+ Tambah ODP</a>
            </div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Port</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($odc->odps as $odp)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $odp->kode }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $odp->nama }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2" style="width:80px">
                                    <div class="bg-orange-500 h-2 rounded-full" style="width:{{ $odp->kapasitas > 0 ? ($odp->port_terpakai / $odp->kapasitas * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $odp->port_terpakai }}/{{ $odp->kapasitas }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($odp->customers_count) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $odp->status === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $odp->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ftth.odp.show', $odp) }}" class="text-blue-600 hover:underline text-sm">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada ODP untuk ODC ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($odc->latitude)
    @push('scripts')
    <script>
    (function() {
        const map = L.map('odc-map', { zoomControl: false }).setView([{{ $odc->latitude }}, {{ $odc->longitude }}], 16);
        L.control.zoom({ position: 'topright' }).addTo(map);
        L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '&copy; Google Maps'
        }).addTo(map);
        L.circleMarker([{{ $odc->latitude }}, {{ $odc->longitude }}], { radius: 14, fillColor: '#2563eb', color: '#fff', weight: 3, fillOpacity: 1 })
            .bindPopup('<b>{{ $odc->kode }}</b><br>{{ $odc->nama }}').addTo(map).openPopup();
    })();
    </script>
    @endpush
    @endif
</x-admin-layout>
