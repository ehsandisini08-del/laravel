<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Customer</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $customer->name }}</p>
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

        <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6" x-data="customerForm()" @submit="saving = true; setTimeout(() => $el.querySelector('button[type=submit]').disabled = true, 0)">
            @csrf
            @method('PUT')

            <x-card title="Customer Data">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address <span class="text-red-500">*</span></label>
                        <textarea name="address" id="address" rows="2" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address', $customer->address) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="area_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Area <span class="text-red-500">*</span></label>
                            <select name="area_id" id="area_id" required x-model="areaId" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Select Area --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ old('area_id', $customer->area_id) == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
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
                        <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $customer->latitude) }}" required step="any" x-model="lat" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude <span class="text-red-500">*</span></label>
                        <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $customer->longitude) }}" required step="any" x-model="lng" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                    </div>
                </div>

                <div id="map" class="h-64 rounded-lg border border-gray-300 dark:border-gray-600 mb-3" x-ref="mapContainer"></div>

                <button type="button" @click="getCurrentLocation" class="btn-sm btn-neutral">
                    Use My Location
                </button>
            </x-card>

            <x-card title="Router & Package">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="router_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router <span class="text-red-500">*</span></label>
                        <select name="router_id" id="router_id" required x-model="routerId" @change="onRouterChange" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Router --</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ old('router_id', $customer->router_id) == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="package_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Package <span class="text-red-500">*</span></label>
                        <select name="package_id" id="package_id" required x-model="packageId" @change="onPackageChange" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Router First --</option>
                            @foreach($packages as $pkg)
                                <option value="{{ $pkg->id }}" {{ old('package_id', $customer->package_id) == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-card>

            <x-card title="FTTH & Jaringan ODP">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="odp_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ODP (Optical Distribution Point)</label>
                        <select name="odp_id" id="odp_id" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Tidak Terhubung ke ODP --</option>
                            @foreach($odps as $odp)
                                <option value="{{ $odp->id }}" {{ old('odp_id', $customer->odp_id) == $odp->id ? 'selected' : '' }}>
                                    {{ $odp->kode }} — {{ $odp->nama }} (Sisa {{ $odp->port_available }} port)
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hubungkan pelanggan ke ODP tempat kabel terpasang.</p>
                    </div>
                    <div>
                        <label for="port_odp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Port ODP</label>
                        <input type="number" name="port_odp" id="port_odp" value="{{ old('port_odp', $customer->port_odp) }}" min="1" max="128" placeholder="Contoh: 1" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nomor port pada splitter ODP (misal: 1 s/d 16).</p>
                    </div>
                </div>
            </x-card>

            <x-card title="PPP Authentication">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="ppp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">PPP Username <span class="text-red-500">*</span></label>
                        <input type="text" name="ppp_username" id="ppp_username" value="{{ old('ppp_username', $customer->ppp_username) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="ppp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">PPP Password</label>
                        <input type="text" name="ppp_password" id="ppp_password" value="{{ old('ppp_password') }}" placeholder="Leave empty to keep current" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to keep current password</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Portal Customer">
                <div class="grid grid-cols-1 gap-4">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="hidden" name="portal_enabled" value="0">
                        <input type="checkbox" name="portal_enabled" value="1" {{ old('portal_enabled', $customer->portal_enabled) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Aktifkan akses portal pelanggan</span>
                    </label>
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-200">Password portal (3 digit) akan otomatis dibuat jika belum ada. Gunakan tombol "Kirim Login via WhatsApp" di halaman detail untuk mengirim kode & password ke pelanggan; password tidak berubah saat dikirim.</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Installation">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="installation_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Installation Date <span class="text-red-500">*</span></label>
                        <input type="date" name="installation_date" id="installation_date" value="{{ old('installation_date', $customer->installation_date?->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tanggal pemasangan</p>
                    </div>
                    <div>
                        <label for="due_day" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date (Day) <span class="text-red-500">*</span></label>
                        <select name="due_day" id="due_day" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select --</option>
                            @foreach(range(1, 31) as $day)
                                <option value="{{ $day }}" {{ old('due_day', $customer->due_day) == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tagihan setiap tanggal</p>
                    </div>
                    <div>
                        <label for="isolation_day" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hari Isolir</label>
                        <select name="isolation_day" id="isolation_day" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Hari --</option>
                            @foreach(range(1, 31) as $day)
                                <option value="{{ $day }}" {{ old('isolation_day', $customer->isolation_day) == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hari dalam bulan ketika pelanggan akan diisolir apabila memiliki tunggakan.</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Status & Notes">
                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Active" {{ old('status', $customer->status) === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Isolated" {{ old('status', $customer->status) === 'Isolated' ? 'selected' : '' }}>Isolir</option>
                            <option value="Suspended" {{ old('status', $customer->status) === 'Suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="Terminated" {{ old('status', $customer->status) === 'Terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                        <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('customers.index') }}" class="app-btn-ghost px-4 py-2 text-sm">Cancel</a>
                <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving">
                    <svg x-show="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Update Customer'">Update Customer</span>
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
                lat: '{{ old('latitude', $customer->latitude) }}',
                lng: '{{ old('longitude', $customer->longitude) }}',
                routerId: '{{ old('router_id', $customer->router_id) }}',
                packageId: '{{ old('package_id', $customer->package_id) }}',
                areaId: '{{ old('area_id', $customer->area_id) }}',
                map: null,
                marker: null,
                initMap() {
                    if (this.map) return;
                    const center = [parseFloat(this.lat) || -6.2088, parseFloat(this.lng) || 106.8456];
                    this.map = L.map(this.$refs.mapContainer).setView(center, 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(this.map);
                    this.marker = L.marker(center, { draggable: true }).addTo(this.map);
                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.lat = pos.lat.toFixed(8);
                        this.lng = pos.lng.toFixed(8);
                    });
                    this.map.on('click', (e) => {
                        this.marker.setLatLng(e.latlng);
                        this.lat = e.latlng.lat.toFixed(8);
                        this.lng = e.latlng.lng.toFixed(8);
                    });
                    setTimeout(() => this.map.invalidateSize(), 300);
                },
                getCurrentLocation() {
                    if (!navigator.geolocation) {
                        showToast('Geolocation is not supported by your browser.', 'error');
                        return;
                    }
                    navigator.geolocation.getCurrentPosition((pos) => {
                        this.lat = pos.coords.latitude.toFixed(8);
                        this.lng = pos.coords.longitude.toFixed(8);
                        this.marker.setLatLng([this.lat, this.lng]);
                        this.map.setView([this.lat, this.lng], 15);
                    }, () => {
                        showToast('Failed to get current location.', 'error');
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
                                const selected = '{{ old('package_id', $customer->package_id) }}' == p.id ? 'selected' : '';
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
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('[x-data="customerForm()"]');
            if (form) {
                const alpine = form.__x;
                setTimeout(() => alpine.$data.initMap(), 200);
                if (alpine.$data.routerId) {
                    setTimeout(() => alpine.$data.onRouterChange(), 100);
                }
            }
        });
    </script>
    @endpush
</x-admin-layout>
