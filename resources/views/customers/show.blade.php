<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $customer->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $customer->customer_code }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if(!Auth::user()->isAdminArea())
                <a href="{{ route('customers.edit', $customer) }}" class="app-btn-primary px-4 py-2.5 text-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
                @endif
                @php
    $deleteMessage = 'Apakah Anda yakin ingin menghapus customer "'.e($customer->name).'"? Tindakan ini tidak dapat dibatalkan.';
    if ($customer->pppSecret) {
        $deleteMessage .= '\n\nCustomer ini memiliki PPP Secret yang akan dihapus dari MikroTik.';
    }
@endphp
@if(!Auth::user()->isAdminArea())
<form method="POST" action="{{ route('customers.destroy', $customer) }}" x-data="{ deleting: false }" @submit.prevent="async () => { if(deleting) return; const confirmed = await customConfirm('{{ $deleteMessage }}'); if(confirmed) { deleting = true; $el.submit() } }" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="app-btn-danger-ghost px-4 py-2.5 text-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </form>
@endif
                <a href="{{ route('customers.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4 mb-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('portal_password'))
            <x-alert variant="warning" dismissible>
                <p class="font-semibold">Password Portal: <span class="font-mono text-lg tracking-widest">{{ session('portal_password') }}</span></p>
                <p class="mt-1 text-xs">Tampil hanya sekali. Berikan kepada pelanggan untuk login di portal dengan kode customer <span class="font-mono">{{ $customer->customer_code }}</span>.</p>
            </x-alert>
        @endif
    </div>

    <div x-data="{ activeTab: 'detail' }">
        <!-- Mobile tab bar -->
        <div class="sticky top-16 z-30 lg:hidden -mx-4 mb-4 border-b border-gray-200 bg-white/95 px-4 backdrop-blur-lg dark:border-gray-700 dark:bg-gray-900/95">
            <div class="grid grid-cols-3 gap-1">
                <button type="button" @click="activeTab = 'detail'" :class="activeTab === 'detail' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'" class="-mb-px border-b-2 py-3 text-sm font-semibold transition-colors">
                    Detail
                </button>
                <button type="button" @click="activeTab = 'billing'" :class="activeTab === 'billing' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'" class="-mb-px border-b-2 py-3 text-sm font-semibold transition-colors">
                    Tagihan
                </button>
                <button type="button" @click="activeTab = 'wifi'" :class="activeTab === 'wifi' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'" class="-mb-px border-b-2 py-3 text-sm font-semibold transition-colors">
                    Wifi
                </button>
            </div>
        </div>

        <!-- Detail panel -->
        <div x-show="activeTab === 'detail'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Customer Information">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                <a href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" rel="noopener noreferrer" class="app-btn-primary mt-3 w-full px-4 py-2.5 text-sm">
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
                    <a href="{{ route('billing.invoices.index', ['status' => 'unpaid']) }}" class="app-btn-danger-ghost w-full px-4 py-2.5 text-sm">Lihat Invoice Belum Bayar</a>
                    @endif
                </div>
            </x-card>

            <x-card title="Portal Customer">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Kode Customer</span>
                        <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $customer->customer_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Akses Portal</span>
                        <x-badge variant="{{ $customer->portal_enabled ? 'success' : 'default' }}">{{ $customer->portal_enabled ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Login Terakhir</span>
                        <span class="text-sm text-gray-900 dark:text-white">{{ $customer->portal_last_login_at?->format('d M Y H:i') ?? '-' }}</span>
                    </div>
                    <form method="POST" action="{{ route('customers.portal-password.send', $customer) }}" x-data @submit.prevent="async () => { if(await customConfirm('Kirim informasi login portal (kode + password) ke WhatsApp {{ $customer->phone }}?', { confirmLabel: 'Ya, Kirim', confirmColor: 'blue' })) $el.submit() }">
                        @csrf
                        <button type="submit" class="app-btn-primary w-full px-4 py-2.5 text-sm">
                            Kirim Login via WhatsApp
                        </button>
                    </form>
                </div>
            </x-card>
        </div>
        </div>

        <!-- Tagihan panel -->
        <div x-show="activeTab === 'billing'" class="lg:hidden">
            <div class="space-y-4">
                <x-card title="Tagihan Aktif">
                    @if($activeBills->isEmpty())
                        <div class="flex flex-col items-center justify-center px-6 py-10 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-green-50 text-green-600 dark:bg-green-900/30">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-base font-bold text-gray-900 dark:text-white">Tidak Ada Tagihan Aktif</h3>
                            <p class="mt-1 max-w-xs text-sm text-gray-500 dark:text-gray-400">Semua tagihan telah dibayar.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($activeBills as $bill)
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $bill->billing_period }}</p>
                                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">@currency($bill->amount)</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Invoice {{ $bill->invoice_number }}</p>
                                        </div>
                                        <x-badge variant="{{ $bill->status_color }}">{{ $bill->status_label }}</x-badge>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-sm dark:border-gray-800">
                                        <span class="text-gray-500 dark:text-gray-400">Jatuh tempo {{ $bill->due_date?->format('d M Y') }}</span>
                                        <a href="{{ route('billing.invoices.show', $bill) }}" class="font-semibold text-blue-600 hover:text-blue-800">Detail</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card title="Riwayat Tagihan">
                    @if($invoiceHistory->isEmpty())
                        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat tagihan.</p>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($invoiceHistory as $inv)
                                <a href="{{ route('billing.invoices.show', $inv) }}" class="flex items-center justify-between gap-3 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $inv->billing_period }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $inv->invoice_number }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">@currency($inv->amount)</span>
                                        <x-badge variant="{{ $inv->status_color }}">{{ $inv->status_label }}</x-badge>
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>

    <!-- Wifi panel -->
    <div x-show="activeTab === 'wifi'" class="lg:hidden">
        @if($customer->cpes->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-800/50">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-50 text-[#2563eb] dark:bg-blue-900/30">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-gray-900 dark:text-white">Belum Ada Perangkat Wifi</h3>
                <p class="mt-1 max-w-xs text-sm text-gray-500 dark:text-gray-400">Perangkat CPE belum terhubung ke customer ini. Jalankan sinkronisasi GenieACS untuk mencocokkan device berdasarkan username PPPoE.</p>
                <a href="{{ route('cpes.index') }}" class="app-btn-primary mt-5 px-4 py-2.5 text-sm">Lihat CPE Devices</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($customer->cpes as $cpe)
                    <x-card title="Informasi Device">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $cpe->model_name ?? $cpe->genieacs_id }}</p>
                            @if($cpe->isOnline())
                                <x-badge variant="success">Online</x-badge>
                            @elseif($cpe->status === 'offline')
                                <x-badge variant="danger">Offline</x-badge>
                            @else
                                <x-badge variant="default">Unknown</x-badge>
                            @endif
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Serial</dt>
                                <dd class="mt-0.5 font-medium font-mono text-gray-900 dark:text-white">{{ $cpe->serial_number ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">SSID</dt>
                                <dd class="mt-0.5 font-medium font-mono text-gray-900 dark:text-white">{{ $cpe->ssid ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">IP Address</dt>
                                <dd class="mt-0.5 font-medium font-mono text-gray-900 dark:text-white">{{ $cpe->ip_address ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">MAC Address</dt>
                                <dd class="mt-0.5 font-medium font-mono text-gray-900 dark:text-white">{{ $cpe->mac_address ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">RX Power</dt>
                                <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">{{ $cpe->rx_power ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">PPPoE Username</dt>
                                <dd class="mt-0.5 font-medium font-mono text-gray-900 dark:text-white">{{ $cpe->ppp_username ?? '-' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-gray-500 dark:text-gray-400">Inform Terakhir</dt>
                                <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">{{ $cpe->last_inform_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>
                        <a href="{{ route('cpes.show', $cpe) }}" class="mt-4 inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            Lihat Detail Perangkat
                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </x-card>

                    @if(!Auth::user()->isAdminArea())
                    <x-card title="Edit SSID & Password">
                        @if($errors->any())
                            <div class="mb-4 space-y-1">
                                @foreach($errors->all() as $error)
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif
                        <form method="POST" action="{{ route('cpes.update', $cpe) }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="ssid-{{ $cpe->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SSID</label>
                                <input type="text" name="ssid" id="ssid-{{ $cpe->id }}" value="{{ old('ssid', $cpe->ssid) }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div x-data="{ showPassword: false }">
                                <label for="wifi_password-{{ $cpe->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password WiFi</label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" name="wifi_password" id="wifi_password-{{ $cpe->id }}" value="{{ old('wifi_password', $cpe->wifi_password) }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10">
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Perubahan akan langsung dikirim ke perangkat melalui GenieACS.</p>
                            </div>

                            <button type="submit" class="app-btn-primary w-full px-4 py-2.5 text-sm">Simpan & Kirim ke Device</button>
                        </form>
                    </x-card>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
    </div>
</x-admin-layout>
