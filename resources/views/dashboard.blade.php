<x-admin-layout>
    <x-slot name="header">
        <div class="hidden md:flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-3xl lg:col-span-2 bg-gradient-to-br from-blue-600 to-blue-700 shadow-lg shadow-blue-600/20">
                <div class="px-6 py-6">
                    <h2 class="text-xl font-bold text-white">
                        Halo, {{ Auth::user()->name }} ({{ Auth::user()->roleLabel() }}) 👋
                    </h2>
                    <p class="mt-2 text-base font-medium text-blue-50">
                        Selamat datang kembali di Dashboard Billnet.
                    </p>
                    <p class="mt-1 text-sm text-blue-200">
                        Kelola pelanggan, layanan, jaringan, dan operasional bisnis internet Anda dengan lebih efisien dari satu dashboard.
                    </p>
                </div>
            </div>
            <div class="app-card h-full flex-col justify-center hidden lg:flex">
                <div class="flex items-center gap-4 px-6 py-6">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-900/30">
                        <svg class="h-7 w-7 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pelanggan</p>
                        <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totalCustomers }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile menu launcher -->
        <x-admin.menu-grid />

        <!-- Customer stat cards (desktop only) -->
        <div class="hidden lg:grid grid-cols-4 gap-4">
            <x-stat-card
                label="Pelanggan Baru"
                value="{{ $newCustomers }}"
                color="blue"
                icon="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
            />
            <x-stat-card
                label="Pelanggan Aktif"
                value="{{ $activeCustomers }}"
                color="green"
                icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
            <x-stat-card
                label="Pelanggan Isolir"
                value="{{ $isolatedCustomers }}"
                color="yellow"
                icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
            <x-stat-card
                label="Tidak Aktif"
                value="{{ $inactiveCustomers }}"
                color="red"
                icon="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
        </div>
    </div>
</x-admin-layout>