<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isset($odc) ? "Edit ODC: {$odc->kode}" : "Tambah ODC" }}</h1>
            </div>
            <a href="{{ route('ftth.odc.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        </div>
    </x-slot>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <div class="max-w-3xl">
        <form method="POST" action="{{ isset($odc) ? route('ftth.odc.update', $odc) : route('ftth.odc.store') }}" class="space-y-6">
            @csrf
            @if(isset($odc)) @method('PUT') @endif

            @if($errors->any())
                <x-alert variant="error" dismissible>
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </x-alert>
            @endif

            <div class="app-card p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="kode">Kode ODC *</x-input-label>
                        <x-text-input id="kode" name="kode" value="{{ old('kode', $odc->kode ?? '') }}" placeholder="ODC-001" required class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('kode')" class="mt-1"/>
                    </div>
                    <div>
                        <x-input-label for="nama">Nama ODC *</x-input-label>
                        <x-text-input id="nama" name="nama" value="{{ old('nama', $odc->nama ?? '') }}" placeholder="ODC Kecamatan A" required class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('nama')" class="mt-1"/>
                    </div>
                </div>
                <div>
                    <x-input-label for="alamat">Alamat</x-input-label>
                    <textarea id="alamat" name="alamat" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('alamat', $odc->alamat ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="kapasitas">Kapasitas (Core) *</x-input-label>
                        <x-text-input id="kapasitas" name="kapasitas" type="number" value="{{ old('kapasitas', $odc->kapasitas ?? 144) }}" min="1" required class="mt-1 block w-full"/>
                    </div>
                    <div>
                        <x-input-label for="status">Status *</x-input-label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm">
                            @foreach($statusOptions as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $odc->status ?? 'ACTIVE') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="keterangan">Keterangan</x-input-label>
                    <textarea id="keterangan" name="keterangan" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('keterangan', $odc->keterangan ?? '') }}</textarea>
                </div>
            </div>

            {{-- Koordinat + Mini Map --}}
            <div class="app-card p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Lokasi / Koordinat</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="latitude">Latitude</x-input-label>
                        <x-text-input id="latitude" name="latitude" type="text" value="{{ old('latitude', $odc->latitude ?? '') }}" placeholder="-6.200000" class="mt-1 block w-full" @change="updateMapMarker()"/>
                    </div>
                    <div>
                        <x-input-label for="longitude">Longitude</x-input-label>
                        <x-text-input id="longitude" name="longitude" type="text" value="{{ old('longitude', $odc->longitude ?? '') }}" placeholder="106.816666" class="mt-1 block w-full" @change="updateMapMarker()"/>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">💡 Klik pada peta untuk menentukan lokasi, atau isi koordinat secara manual.</p>
                <div id="odc-pick-map" style="height:300px;border-radius:12px;overflow:hidden;"></div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="app-btn-primary px-6 py-2.5">Simpan</button>
                <a href="{{ route('ftth.odc.index') }}" class="app-btn-soft px-6 py-2.5">Batal</a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    (function() {
        const lat = document.getElementById('latitude');
        const lng = document.getElementById('longitude');
        const defaultLat = parseFloat(lat.value) || -2.5;
        const defaultLng = parseFloat(lng.value) || 118.0;
        const map = L.map('odc-pick-map', { center: [defaultLat, defaultLng], zoom: lat.value ? 16 : 5 });
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, attribution: '&copy; Esri' }).addTo(map);
        const labelsLayer = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20 }).addTo(map);
        let marker = null;
        if (lat.value && lng.value) {
            marker = L.marker([parseFloat(lat.value), parseFloat(lng.value)], { draggable: true }).addTo(map);
            marker.on('dragend', e => { lat.value = e.target.getLatLng().lat.toFixed(7); lng.value = e.target.getLatLng().lng.toFixed(7); });
        }
        map.on('click', e => {
            lat.value = e.latlng.lat.toFixed(7);
            lng.value = e.latlng.lng.toFixed(7);
            if (marker) { marker.setLatLng(e.latlng); } else {
                marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                marker.on('dragend', ev => { lat.value = ev.target.getLatLng().lat.toFixed(7); lng.value = ev.target.getLatLng().lng.toFixed(7); });
            }
        });
    })();
    </script>
    @endpush
</x-admin-layout>
