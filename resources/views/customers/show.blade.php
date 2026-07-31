<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $customer->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $customer->customer_code }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" x-data @submit.prevent="$dispatch('open-modal', 'delete-customer-confirm')" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </form>
                <a href="{{ route('customers.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Customer Information">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Code</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $customer->customer_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1">
                            <x-badge variant="{{ $customer->status_color }}">{{ $customer->status_badge }}</x-badge>
                        </dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->address }}</dd>
                </div>
            </x-card>

            <x-card title="Package & Router">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Package</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->package?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Price</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->package?->price_formatted }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Router</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->router?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Area</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->area?->name }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="PPP Authentication">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PPP Username</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $customer->ppp_username }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PPP Password</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white" x-data="{ show: false }">
                            <span x-show="!show" class="select-all">••••••••</span>
                            <span x-show="show" class="select-all">{{ $customer->ppp_password }}</span>
                            <button @click="show = !show" type="button" class="ml-2 inline-flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="Toggle password visibility">
                                <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PPP Profile</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->pppSecret?->profile ?? ($customer->package?->pppProfile?->name ?? '-') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Router</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->router?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1">
                            @if($customer->pppSecret)
                                <x-badge variant="{{ $customer->pppSecret->isActive() ? 'success' : 'danger' }}">
                                    {{ $customer->pppSecret->isActive() ? 'Active' : 'Disabled' }}
                                </x-badge>
                            @else
                                <x-badge variant="default">No Secret</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sync Status</dt>
                        <dd class="mt-1">
                            @if($customer->pppSecret)
                                <x-badge variant="success">Synced</x-badge>
                            @else
                                <x-badge variant="warning">Not Synced</x-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Installation">
                <dl class="grid grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Pemasangan</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->installation_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tagihan Setiap Tanggal</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">Setiap {{ $customer->due_day }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Hari Isolir</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->isolation_day ? 'Tanggal '.$customer->isolation_day.' setiap bulan' : '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            @if($customer->notes)
                <x-card title="Notes">
                    <p class="text-sm text-gray-900 dark:text-white">{{ $customer->notes }}</p>
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card title="Location">
                <div id="map" class="h-48 rounded-lg mb-3"></div>
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Latitude</dt>
                        <dd class="text-sm font-mono text-gray-900 dark:text-white">{{ $customer->latitude }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Longitude</dt>
                        <dd class="text-sm font-mono text-gray-900 dark:text-white">{{ $customer->longitude }}</dd>
                    </div>
                </dl>
                @if($customer->latitude && $customer->longitude)
                <a href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 w-full justify-center px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    View on Google Maps
                </a>
                @endif
            </x-card>

            <x-card title="Billing">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Service Status</span>
                        <x-badge variant="{{ $customer->service_status?->color() ?? 'default' }}">{{ $customer->service_status?->label() ?? 'Active' }}</x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Tagihan</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Setiap tanggal {{ $customer->due_day }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Isolir</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $customer->isolation_day ? 'Tanggal '.$customer->isolation_day : '-' }}</span>
                    </div>
                    @if($customer->invoices()->whereIn('status', ['unpaid', 'overdue'])->exists())
                    <a href="{{ route('billing.invoices.index', ['status' => 'unpaid']) }}" class="block text-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">View Unpaid Invoice</a>
                    @endif
                </div>
            </x-card>
        </div>
    </div>

    <x-modal name="delete-customer-confirm" maxWidth="lg" focusable>
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Customer</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Apakah Anda yakin ingin menghapus Customer ini? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
            @if($customer->pppSecret)
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        Customer ini memiliki PPP Secret yang akan dihapus dari MikroTik.
                    </p>
                </div>
            @endif
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close-modal', 'delete-customer-confirm')" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Batal
                </button>
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" x-data @submit="saving = true" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving">
                        <svg x-show="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        Ya, Hapus Customer
                    </button>
                </form>
            </div>
        </div>
    </x-modal>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = {{ $customer->latitude ?? '-6.2088' }};
            const lng = {{ $customer->longitude ?? '106.8456' }};
            const map = L.map('map').setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            L.marker([lat, lng], { draggable: false }).addTo(map);
            setTimeout(() => map.invalidateSize(), 300);
        });
    </script>
    @endpush
</x-admin-layout>
