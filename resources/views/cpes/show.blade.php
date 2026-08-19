<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $cpe->model_name ?? $cpe->genieacs_id }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Detail perangkat CPE dari GenieACS</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="refreshCpe()" class="app-btn-success px-4 py-2.5 text-sm">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh dari ACS
                </button>
                <a href="{{ route('cpes.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="cpeDetail()">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif

        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Device Information</h3>
                @if($cpe->isOnline())
                    <x-badge variant="success">Online</x-badge>
                @elseif($cpe->status === 'offline')
                    <x-badge variant="danger">Offline</x-badge>
                @else
                    <x-badge variant="default">Unknown</x-badge>
                @endif
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">GenieACS ID</dt>
                    <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->genieacs_id }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Serial Number</dt>
                    <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->serial_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Manufacturer</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->manufacturer ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Model</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->model_name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Model Number</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->model_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Hardware Version</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->hardware_version ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Firmware Version</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->software_version ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">IP Address</dt>
                    <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->ip_address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">MAC Address</dt>
                    <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->mac_address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">SSID</dt>
                    <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->ssid ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">PPPoE Username</dt>
                    <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->ppp_username ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Uptime</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->formatted_uptime }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">RX Power</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->rx_power ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Inform Terakhir</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->last_inform_at?->format('d/m/Y H:i') ?? '-' }} <span class="text-gray-500">({{ $cpe->last_inform_at?->diffForHumans() ?? '-' }})</span></dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Terakhir Sinkron</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->synced_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="WiFi">
            <form method="POST" action="{{ route('cpes.update', $cpe) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="ssid" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SSID</label>
                        <input type="text" name="ssid" id="ssid" value="{{ old('ssid', $cpe->ssid) }}" placeholder="SSID WiFi perangkat" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('ssid')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ showPassword: false }">
                        <label for="wifi_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password WiFi</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="wifi_password" id="wifi_password" value="{{ old('wifi_password', $cpe->wifi_password) }}" placeholder="Password WiFi perangkat" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10">
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('wifi_password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Simpan WiFi</button>
                </div>
            </form>
        </x-card>

        <x-card title="Customer">
            @if($cpe->customer)
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nama</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            <a href="{{ route('customers.show', $cpe->customer) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $cpe->customer->name }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Kode Customer</dt>
                        <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->customer->customer_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">No. Telepon</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $cpe->customer->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">PPPoE Username</dt>
                        <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $cpe->customer->ppp_username }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Device ini belum terhubung ke customer. Auto-match dilakukan berdasarkan username PPPoE pada koneksi WAN device saat sinkronisasi.
                </p>
            @endif
        </x-card>

        @if(!empty($cpe->signal_parameters))
            <x-card title="Parameter Sinyal & Virtual">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Parameter</th>
                                <th class="text-left">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cpe->signal_parameters as $parameter)
                                <tr>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $parameter['label'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $parameter['value'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif

        @if(!empty($cpe->tags))
            <x-card title="Tags">
                <div class="flex flex-wrap gap-2">
                    @foreach($cpe->tags as $tag)
                        <x-badge variant="info">{{ $tag }}</x-badge>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif
            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif
        });

        function cpeDetail() {
            return {
                refreshing: false,
            };
        }

        function refreshCpe() {
            showToast('Mengambil data terbaru dari GenieACS...', 'info');

            fetch('{{ route('cpes.refresh', $cpe) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Refresh error:', error);
                showToast('Gagal refresh: ' + error.message, 'error');
            });
        }
    </script>
    @endpush
</x-admin-layout>