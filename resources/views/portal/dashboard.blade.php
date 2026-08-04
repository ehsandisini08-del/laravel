<x-portal-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Halo, {{ $customer->name }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kode Customer: <span class="font-mono font-semibold tracking-wider">{{ $customer->customer_code }}</span></p>
    </div>

    @php
        $statusLabels = ['active' => 'Aktif', 'overdue' => 'Telat Bayar', 'isolated' => 'Terisolir'];
        $serviceLabel = $statusLabels[$customer->service_status?->value] ?? 'Aktif';
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Detail Data Customer">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nama</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kode Customer</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $customer->customer_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Alamat</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->address }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Telepon</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Area</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->area?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Router</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->router?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Paket</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->package?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Harga Paket</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">@currency($customer->package?->price ?? 0)</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Username Internet (PPPoE)</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $customer->ppp_username }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status Layanan</dt>
                        <dd class="mt-1"><x-badge variant="{{ $customer->service_status?->color() ?? 'success' }}">{{ $serviceLabel }}</x-badge></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Jatuh Tempo</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">Setiap tanggal {{ $customer->due_day }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tanggal Isolir</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->isolation_day ? 'Setiap tanggal '.$customer->isolation_day : '-' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Tagihan Aktif">
                @if($activeBills->isEmpty())
                    <div class="text-center py-8">
                        <div class="mx-auto h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">Tidak ada tagihan</p>
                        <p class="mt-1 text-xs text-gray-500">Semua tagihan Anda sudah lunas.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($activeBills as $bill)
                            <a href="{{ route('portal.invoices.show', $bill) }}" class="block rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-blue-400 hover:shadow-sm transition-colors">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $bill->billing_period }}</p>
                                    <x-badge variant="{{ $bill->status_color }}">{{ $bill->status_label }}</x-badge>
                                </div>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">@currency($bill->amount)</p>
                                <p class="mt-1 text-xs text-gray-500">Jatuh tempo {{ $bill->due_date?->format('d M Y') }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card>
                <a href="{{ route('portal.invoices.index') }}" class="block text-center px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    Lihat Riwayat Tagihan
                </a>
            </x-card>
        </div>
    </div>
</x-portal-layout>
