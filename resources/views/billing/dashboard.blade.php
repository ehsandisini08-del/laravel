<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Dashboard Billing</h1>
            <form method="POST" action="{{ route('billing.generate') }}">
                @csrf
                <button type="submit" class="app-btn-success px-4 py-2.5 text-sm">
                    Buat Invoice
                </button>
            </form>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Bulan Ini</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_this_month'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum Bayar</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_unpaid'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telat Bayar</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_overdue'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Lunas</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_paid'] }}</p>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Customer Aktif</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_customers'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telat Bayar</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['overdue_customers'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Isolir</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['isolated_customers'] }}</p>
                    </div>
                </div>
            </x-card>
        </div>

        <x-card title="Aksi Cepat">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('billing.invoices.index', ['status' => 'unpaid']) }}" class="block px-4 py-3 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    <span class="font-medium text-red-700 dark:text-red-300">Invoice Belum Bayar</span>
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $stats['total_unpaid'] }} invoice</p>
                </a>
                <a href="{{ route('billing.invoices.index', ['status' => 'overdue']) }}" class="block px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors">
                    <span class="font-medium text-yellow-700 dark:text-yellow-300">Invoice Telat Bayar</span>
                    <p class="text-sm text-yellow-600 dark:text-yellow-400">{{ $stats['total_overdue'] }} invoice</p>
                </a>
                <a href="{{ route('billing.invoices.index') }}" class="block px-4 py-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                    <span class="font-medium text-blue-700 dark:text-blue-300">Semua Invoice</span>
                    <p class="text-sm text-blue-600 dark:text-blue-400">Lihat semua data</p>
                </a>
            </div>
        </x-card>
    </div>
</x-admin-layout>