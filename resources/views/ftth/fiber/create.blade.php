<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isset($fiber) ? "Edit Jalur Fiber" : "Tambah Jalur Fiber" }}</h1>
            <a href="{{ route('ftth.fiber.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        </div>
    </x-slot>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <div class="max-w-3xl">
        <form method="POST" action="{{ isset($fiber) ? route('ftth.fiber.update', $fiber) : route('ftth.fiber.store') }}" class="space-y-6">
            @csrf
            @if(isset($fiber)) @method('PUT') @endif

            @if($errors->any())
                <x-alert variant="error" dismissible>
                    <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </x-alert>
            @endif

            <div class="app-card p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="nama">Nama Jalur *</x-input-label>
                        <x-text-input id="nama" name="nama" value="{{ old('nama', $fiber->nama ?? '') }}" placeholder="Fiber ODC-001 ke ODP-001" required class="mt-1 block w-full"/>
                    </div>
                    <div>
                        <x-input-label for="tipe_kabel">Tipe Kabel</x-input-label>
                        <x-text-input id="tipe_kabel" name="tipe_kabel" value="{{ old('tipe_kabel', $fiber->tipe_kabel ?? '') }}" placeholder="SM G.652D" class="mt-1 block w-full"/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="source_type">Sumber (Tipe)</x-input-label>
                        <select id="source_type" name="source_type" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm">
                            <option value="">-- Pilih --</option>
                            <option value="odc" {{ old('source_type', $fiber->source_type ?? '') === 'odc' ? 'selected' : '' }}>ODC</option>
                            <option value="odp" {{ old('source_type', $fiber->source_type ?? '') === 'odp' ? 'selected' : '' }}>ODP</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="source_id">Sumber (ID)</x-input-label>
                        <x-text-input id="source_id" name="source_id" type="number" value="{{ old('source_id', $fiber->source_id ?? '') }}" placeholder="ID ODC/ODP" class="mt-1 block w-full"/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="destination_type">Tujuan (Tipe)</x-input-label>
                        <select id="destination_type" name="destination_type" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm">
                            <option value="">-- Pilih --</option>
                            <option value="odc" {{ old('destination_type', $fiber->destination_type ?? '') === 'odc' ? 'selected' : '' }}>ODC</option>
                            <option value="odp" {{ old('destination_type', $fiber->destination_type ?? '') === 'odp' ? 'selected' : '' }}>ODP</option>
                            <option value="customer" {{ old('destination_type', $fiber->destination_type ?? '') === 'customer' ? 'selected' : '' }}>Customer</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="destination_id">Tujuan (ID)</x-input-label>
                        <x-text-input id="destination_id" name="destination_id" type="number" value="{{ old('destination_id', $fiber->destination_id ?? '') }}" placeholder="ID tujuan" class="mt-1 block w-full"/>
                    </div>
                </div>
                <div>
                    <x-input-label for="status">Status *</x-input-label>
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm">
                        @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $fiber->status ?? 'ACTIVE') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="keterangan">Keterangan</x-input-label>
                    <textarea id="keterangan" name="keterangan" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm">{{ old('keterangan', $fiber->keterangan ?? '') }}</textarea>
                </div>
            </div>

            {{-- Map untuk gambar jalur --}}
            <div class="app-card p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Gambar Jalur pada Peta</h3>
                <p class="text-xs text-gray-500">Klik pada peta untuk menambahkan titik jalur. Double-klik untuk mengakhiri.</p>
                <div id="fiber-draw-map" style="height:350px;border-radius:12px;overflow:hidden;"></div>
                <textarea id="geometry" name="geometry" class="hidden">{{ old('geometry', $fiber->geometry ?? '') }}</textarea>
                <div class="flex gap-2">
                    <button type="button" id="clear-line" class="app-btn-soft px-3 py-2 text-xs">Hapus Jalur</button>
                    <span id="point-count" class="text-xs text-gray-500 self-center"></span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="app-btn-primary px-6 py-2.5">Simpan</button>
                <a href="{{ route('ftth.fiber.index') }}" class="app-btn-soft px-6 py-2.5">Batal</a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    (function() {
        const map = L.map('fiber-draw-map', { center: [-2.5, 118.0], zoom: 5 });
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, attribution: '&copy; Esri' }).addTo(map);
        L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20 }).addTo(map);

        const geoInput = document.getElementById('geometry');
        const countEl = document.getElementById('point-count');
        let points = [];
        let polyline = null;
        let markers = [];

        // Load existing geometry
        if (geoInput.value) {
            try {
                const geo = JSON.parse(geoInput.value);
                if (geo.coordinates) {
                    points = geo.coordinates.map(c => [c[1], c[0]]);
                    drawLine();
                    if (points.length) { map.fitBounds(L.latLngBounds(points), { padding: [30, 30] }); }
                }
            } catch(e) {}
        }

        function drawLine() {
            if (polyline) { map.removeLayer(polyline); }
            markers.forEach(m => map.removeLayer(m));
            markers = [];
            if (points.length < 1) return;
            if (points.length > 1) {
                polyline = L.polyline(points, { color: '#FBBF24', weight: 4, dashArray: '6,4' }).addTo(map);
            }
            points.forEach((p, i) => {
                const m = L.circleMarker(p, { radius: 5, fillColor: i === 0 ? '#2563eb' : '#FBBF24', color: '#fff', weight: 2, fillOpacity: 1 }).addTo(map);
                markers.push(m);
            });
            countEl.textContent = `${points.length} titik`;
            updateGeometry();
        }

        function updateGeometry() {
            if (points.length < 2) { geoInput.value = ''; return; }
            const geojson = { type: 'LineString', coordinates: points.map(p => [p[1], p[0]]) };
            geoInput.value = JSON.stringify(geojson);
        }

        map.on('click', e => {
            points.push([e.latlng.lat, e.latlng.lng]);
            drawLine();
        });

        document.getElementById('clear-line').addEventListener('click', () => {
            points = [];
            drawLine();
            geoInput.value = '';
        });
    })();
    </script>
    @endpush
</x-admin-layout>
