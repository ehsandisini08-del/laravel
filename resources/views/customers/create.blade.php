<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Customer</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Register a new ISP customer</p>
            </div>
            <a href="{{ route('customers.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        @if(session('error'))
            <x-alert variant="danger" dismissible class="mb-6">{{ session('error') }}</x-alert>
        @endif

        @if($errors->any())
            <x-alert variant="danger" dismissible class="mb-6">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('customers.store') }}" class="space-y-6" x-data="customerForm()" @submit="saving = true; setTimeout(() => $el.querySelector('button[type=submit]').disabled = true, 0)">
            @csrf

            <x-card title="Customer Data">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address <span class="text-red-500">*</span></label>
                        <textarea name="address" id="address" rows="2" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="area_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Area <span class="text-red-500">*</span></label>
                            <select name="area_id" id="area_id" required x-model="areaId" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Select Area --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Location Coordinates">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude <span class="text-red-500">*</span></label>
                        <input type="text" name="latitude" id="latitude" value="{{ old('latitude', '-6.2088') }}" required step="any" x-model="lat" @input="updateMarkerFromInputs()" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude <span class="text-red-500">*</span></label>
                        <input type="text" name="longitude" id="longitude" value="{{ old('longitude', '106.8456') }}" required step="any" x-model="lng" @input="updateMarkerFromInputs()" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                    </div>
                </div>

                <div id="customer-map" class="h-72 w-full rounded-xl border border-gray-300 dark:border-gray-600 mb-3 overflow-hidden shadow-inner" x-ref="mapContainer" style="min-height: 280px;"></div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">💡 Klik pada peta atau geser pin marker untuk menentukan titik koordinat pelanggan.</p>
                    <button type="button" @click="getCurrentLocation" class="app-btn-soft px-3 py-1.5 text-xs flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Gunakan Lokasi Saya</span>
                    </button>
                </div>
            </x-card>

            <x-card title="Router & Package">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="router_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router <span class="text-red-500">*</span></label>
                        <select name="router_id" id="router_id" required x-model="routerId" @change="onRouterChange" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Router --</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ old('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="package_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Package <span class="text-red-500">*</span></label>
                        <select name="package_id" id="package_id" required x-model="packageId" @change="onPackageChange" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Router First --</option>
                        </select>
                    </div>
                </div>
            </x-card>

            <x-card title="FTTH & Jaringan ODP">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="odp_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ODP (Optical Distribution Point)</label>
                        <select name="odp_id" id="odp_id" x-model="odpId" @change="onOdpChange()" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih ODP (Opsional) --</option>
                            @foreach($odps as $odp)
                                <option value="{{ $odp->id }}" {{ old('odp_id') == $odp->id ? 'selected' : '' }}>
                                    {{ $odp->kode }} — {{ $odp->nama }} (Sisa {{ $odp->port_available }} port)
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hubungkan pelanggan ke ODP terdekat.</p>
                    </div>
                    <div>
                        <label for="port_odp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Port ODP</label>
                        <select name="port_odp" id="port_odp" x-model="portOdp" :disabled="!odpId || odpLoading" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:bg-gray-100 dark:disabled:bg-gray-800">
                            <option value="">-- Pilih ODP Terlebih Dahulu --</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="!odpLoading">Hanya menampilkan nomor port yang masih kosong pada ODP terpilih.</p>
                        <p class="mt-1 text-xs text-blue-500" x-show="odpLoading" style="display: none;">Memuat daftar port kosong...</p>
                    </div>
                </div>
            </x-card>

            <x-card title="PPP Authentication">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="ppp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">PPP Username <span class="text-red-500">*</span></label>
                        <input type="text" name="ppp_username" id="ppp_username" value="{{ old('ppp_username') }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="ppp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">PPP Password <span class="text-red-500">*</span></label>
                        <input type="text" name="ppp_password" id="ppp_password" value="{{ old('ppp_password') }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="hidden" name="create_ppp_secret" value="0">
                        <input type="checkbox" name="create_ppp_secret" value="1" {{ old('create_ppp_secret', '1') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Create PPP Secret automatically on the selected router</span>
                    </label>
                </div>
            </x-card>

            <x-card title="Portal Customer">
                <div class="grid grid-cols-1 gap-4">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="hidden" name="portal_enabled" value="0">
                        <input type="checkbox" name="portal_enabled" value="1" {{ old('portal_enabled', '1') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Aktifkan akses portal pelanggan</span>
                    </label>
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-200">Password portal (3 digit) akan otomatis dibuat dan ditampilkan setelah disimpan. Berikan kepada pelanggan untuk login di portal.</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Installation">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="installation_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Installation Date <span class="text-red-500">*</span></label>
                        <input type="date" name="installation_date" id="installation_date" value="{{ old('installation_date', date('Y-m-d')) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tanggal pemasangan</p>
                    </div>
                    <div>
                        <label for="due_day" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date (Day) <span class="text-red-500">*</span></label>
                        <select name="due_day" id="due_day" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select --</option>
                            @foreach(range(1, 31) as $day)
                                <option value="{{ $day }}" {{ old('due_day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tagihan setiap tanggal</p>
                    </div>
                    <div>
                        <label for="isolation_day" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hari Isolir</label>
                        <select name="isolation_day" id="isolation_day" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Hari --</option>
                            @foreach(range(1, 31) as $day)
                                <option value="{{ $day }}" {{ old('isolation_day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hari dalam bulan ketika pelanggan akan diisolir apabila memiliki tunggakan.</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('customers.index') }}" class="app-btn-ghost px-4 py-2 text-sm">Cancel</a>
                <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving">
                    <svg x-show="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Create Customer'">Create Customer</span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function customerForm() {
            return {
                saving: false,
                lat: '{{ old('latitude', '-6.2088') }}',
                lng: '{{ old('longitude', '106.8456') }}',
                routerId: '{{ old('router_id') }}',
                packageId: '{{ old('package_id') }}',
                areaId: '{{ old('area_id') }}',
                odpId: '{{ old('odp_id') }}',
                portOdp: '{{ old('port_odp') }}',
                odpLoading: false,
                map: null,
                marker: null,

                init() {
                    this.$nextTick(() => {
                        setTimeout(() => this.initMap(), 150);
                    });
                    if (this.routerId) {
                        this.onRouterChange();
                    }
                    if (this.odpId) {
                        this.onOdpChange(true);
                    }
                },

                initMap() {
                    if (this.map) return;
                    const container = this.$refs.mapContainer || document.getElementById('customer-map');
                    if (!container) return;

                    const latNum = parseFloat(this.lat) || -6.2088;
                    const lngNum = parseFloat(this.lng) || 106.8456;
                    const center = [latNum, lngNum];

                    this.map = L.map(container, {
                        center: center,
                        zoom: 15,
                        zoomControl: true,
                    });

                    // Google Maps Hybrid (Satellite + Labels)
                    const googleHybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                        maxZoom: 20,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '&copy; Google Maps'
                    });

                    const googleStreets = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                        maxZoom: 20,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '&copy; Google Maps'
                    });

                    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    });

                    googleHybrid.addTo(this.map);

                    L.control.layers({
                        '🛰 Satelit + Label (Google)': googleHybrid,
                        '🗺 Peta Jalan (Google)': googleStreets,
                        '🌐 OpenStreetMap': osm,
                    }, null, { position: 'topright' }).addTo(this.map);

                    this.marker = L.marker(center, { draggable: true }).addTo(this.map);

                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.lat = pos.lat.toFixed(7);
                        this.lng = pos.lng.toFixed(7);
                    });

                    this.map.on('click', (e) => {
                        this.marker.setLatLng(e.latlng);
                        this.lat = e.latlng.lat.toFixed(7);
                        this.lng = e.latlng.lng.toFixed(7);
                    });

                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 300);
                },

                updateMarkerFromInputs() {
                    const latNum = parseFloat(this.lat);
                    const lngNum = parseFloat(this.lng);
                    if (!isNaN(latNum) && !isNaN(lngNum) && this.map && this.marker) {
                        this.marker.setLatLng([latNum, lngNum]);
                        this.map.setView([latNum, lngNum], Math.max(this.map.getZoom(), 15));
                    }
                },

                getCurrentLocation() {
                    if (!navigator.geolocation) {
                        if (typeof showToast === 'function') {
                            showToast('Geolocation tidak didukung oleh browser Anda.', 'error');
                        } else {
                            alert('Geolocation tidak didukung oleh browser Anda.');
                        }
                        return;
                    }
                    navigator.geolocation.getCurrentPosition((pos) => {
                        this.lat = pos.coords.latitude.toFixed(7);
                        this.lng = pos.coords.longitude.toFixed(7);
                        if (this.marker) this.marker.setLatLng([this.lat, this.lng]);
                        if (this.map) this.map.setView([this.lat, this.lng], 16);
                    }, () => {
                        if (typeof showToast === 'function') {
                            showToast('Gagal mendeteksi lokasi saat ini.', 'error');
                        } else {
                            alert('Gagal mendeteksi lokasi saat ini.');
                        }
                    });
                },

                onRouterChange() {
                    if (!this.routerId) {
                        document.getElementById('package_id').innerHTML = '<option value="">-- Select Router First --</option>';
                        return;
                    }
                    const pkgSelect = document.getElementById('package_id');
                    pkgSelect.innerHTML = '<option value="">Loading...</option>';
                    fetch('/customers/router/' + this.routerId + '/packages')
                        .then(r => r.json())
                        .then(packages => {
                            pkgSelect.innerHTML = '<option value="">-- Select Package --</option>';
                            packages.forEach(p => {
                                const selected = '{{ old('package_id') }}' == p.id ? 'selected' : '';
                                pkgSelect.innerHTML += `<option value="${p.id}" ${selected}>${p.name} (Rp ${Number(p.price).toLocaleString('id')})</option>`;
                            });
                        })
                        .catch(() => {
                            pkgSelect.innerHTML = '<option value="">Failed to load packages</option>';
                        });
                },

                onPackageChange() {
                    if (!this.packageId) return;
                    fetch('/customers/package/' + this.packageId + '/areas')
                        .then(r => r.json())
                        .then(areas => {
                            const areaSelect = document.getElementById('area_id');
                            if (areas.length === 1) {
                                areaSelect.value = areas[0].id;
                                this.areaId = areas[0].id;
                            }
                        })
                        .catch(() => {});
                },

                onOdpChange(isInit = false) {
                    const portSelect = document.getElementById('port_odp');
                    if (!this.odpId) {
                        this.portOdp = '';
                        portSelect.innerHTML = '<option value="">-- Pilih ODP Terlebih Dahulu --</option>';
                        return;
                    }

                    this.odpLoading = true;
                    portSelect.innerHTML = '<option value="">Memuat port kosong...</option>';

                    fetch('/customers/odp/' + this.odpId + '/available-ports')
                        .then(r => r.json())
                        .then(data => {
                            this.odpLoading = false;
                            const ports = data.available_ports || [];
                            if (ports.length === 0) {
                                portSelect.innerHTML = '<option value="">-- Tidak ada port kosong (ODP Penuh) --</option>';
                                this.portOdp = '';
                                return;
                            }

                            let html = '<option value="">-- Pilih Nomor Port --</option>';
                            ports.forEach(p => {
                                const selected = (this.portOdp && Number(this.portOdp) === Number(p)) ? 'selected' : '';
                                html += `<option value="${p}" ${selected}>Port ${p}</option>`;
                            });
                            portSelect.innerHTML = html;

                            if (this.portOdp && ports.includes(Number(this.portOdp))) {
                                portSelect.value = this.portOdp;
                            } else if (!isInit) {
                                this.portOdp = '';
                            }
                        })
                        .catch(() => {
                            this.odpLoading = false;
                            portSelect.innerHTML = '<option value="">Gagal memuat port</option>';
                        });
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>
