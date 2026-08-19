<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">CPE Devices</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar perangkat CPE yang terhubung ke GenieACS (TR-069)</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="syncCpes()" class="app-btn-success px-4 py-2.5 text-sm">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif

        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        @if(!$genieacsConfigured)
            <x-alert variant="warning" dismissible>
                GenieACS belum dikonfigurasi. Isi URL NBI & kredensial pada menu <a href="{{ route('settings.index') }}" class="font-semibold underline">Settings → GenieACS</a> agar sinkronisasi dapat berjalan.
            </x-alert>
        @endif

        <x-card>
            <form method="GET" action="{{ route('cpes.index') }}" class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari serial, username PPPoE, model, IP..." class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="unknown" {{ request('status') === 'unknown' ? 'selected' : '' }}>Unknown</option>
                </select>
                <select name="link" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Device</option>
                    <option value="linked" {{ request('link') === 'linked' ? 'selected' : '' }}>Terhubung Customer</option>
                    <option value="unlinked" {{ request('link') === 'unlinked' ? 'selected' : '' }}>Tanpa Customer</option>
                </select>
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
                @if(request()->hasAny(['search', 'status', 'link']))
                    <a href="{{ route('cpes.index') }}" class="btn-sm btn-neutral">Reset</a>
                @endif
            </form>
        </x-card>

        @if($cpes->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Device</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Klik tombol Sync untuk mengambil daftar device dari GenieACS.</p>
                    <div class="mt-6">
                        <button onclick="syncCpes()" class="app-btn-success px-4 py-2.5 text-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Sync dari GenieACS
                        </button>
                    </div>
                </div>
            </x-card>
        @else
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Device</th>
                                <th class="text-left">Serial</th>
                                <th class="text-left">IP Address</th>
                                <th class="text-left">RX Power</th>
                                <th class="text-left">Customer</th>
                                <th class="text-left">Status</th>
                                <th class="text-left">Inform Terakhir</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cpes as $cpe)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $cpe->model_name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $cpe->manufacturer ?? 'Unknown' }}@if($cpe->software_version) · v{{ $cpe->software_version }}@endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">{{ $cpe->serial_number ?? '-' }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">{{ $cpe->ip_address ?? '-' }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">{{ $cpe->rx_power ?? '-' }}</td>
                                    <td class="whitespace-nowrap">
                                        @if($cpe->customer)
                                            <a href="{{ route('customers.show', $cpe->customer) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ $cpe->customer->name }}
                                            </a>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $cpe->customer->ppp_username }}</div>
                                        @else
                                            <x-badge variant="warning">Tanpa Customer</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if($cpe->isOnline())
                                            <x-badge variant="success">Online</x-badge>
                                        @elseif($cpe->status === 'offline')
                                            <x-badge variant="danger">Offline</x-badge>
                                        @else
                                            <x-badge variant="default">Unknown</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $cpe->last_inform_at?->diffForHumans() ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('cpes.show', $cpe) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" title="Detail">
                                            <svg class="h-5 w-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $cpes->links() }}
            </div>
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

        function syncCpes() {
            showToast('Menyinkronkan device dari GenieACS...', 'info');

            fetch('{{ route('cpes.sync') }}', {
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
                console.error('Sync error:', error);
                showToast('Gagal sync: ' + error.message, 'error');
            });
        }
    </script>
    @endpush
</x-admin-layout>