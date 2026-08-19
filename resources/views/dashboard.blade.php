<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Halo, {{ Auth::user()->name }}!</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Mobile menu launcher -->
        <x-admin.menu-grid />

        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-{{ Auth::user()->isAdminArea() ? 3 : 4 }}">
            <x-stat-card
                label="Total Routers"
                value="{{ $totalRouters }}"
                color="blue"
                icon="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"
            />

            <x-stat-card
                label="Online Routers"
                value="{{ $onlineRouters }}"
                color="green"
                icon="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
            />

            <x-stat-card
                label="Offline Routers"
                value="{{ $offlineRouters }}"
                color="red"
                icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />

            @if(!Auth::user()->isAdminArea())
            <x-stat-card
                label="System Status"
                value="Aktif"
                color="purple"
                icon="M13 10V3L4 14h7v7l9-11h-7z"
            />
            @endif
        </div>

        @if(!Auth::user()->isAdminArea())
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Activity -->
            <div class="app-card">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-700 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Aktivitas Terbaru</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Log aktivitas sistem</p>
                    </div>
                    <a href="{{ route('logs.index') }}" class="app-btn-soft px-4 py-2 text-xs">Lihat Log</a>
                </div>
                <div class="flow-root px-5 py-4">
                    <ul class="-my-5 divide-y divide-slate-100 dark:divide-gray-700">
                        @forelse($recentLogs as $log)
                            @php $props = $log->properties ?? []; @endphp
                            <li class="py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-xs">
                                        {{ strtoupper(substr($log->causer?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $log->description }}</p>
                                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $log->causer?->name ?? 'System' }} &middot; {{ $props['module'] ?? '-' }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs text-slate-400 dark:text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="py-10 text-center text-sm text-slate-500 dark:text-gray-400">Belum ada aktivitas.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="app-card">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-700 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Aksi Cepat</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Menu umum manajemen</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5">
                    <a href="{{ route('customers.create') }}" class="group flex items-center gap-4 rounded-2xl bg-blue-50 dark:bg-blue-900/30 p-4 transition-colors hover:bg-blue-100 dark:hover:bg-blue-900/40">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/30">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Add Customer</p>
                            <p class="text-xs text-slate-500 dark:text-gray-400">Daftarkan pelanggan baru</p>
                        </div>
                    </a>

                    <a href="{{ route('billing.dashboard') }}" class="group flex flex-col gap-4 rounded-2xl bg-green-50 dark:bg-green-900/30 p-4 transition-colors hover:bg-green-100 dark:hover:bg-green-900/40">
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-600 text-white shadow-md shadow-green-600/30">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Billing</p>
                                <p class="text-xs text-slate-500 dark:text-gray-400">Buat & kelola invoice</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('routers.index') }}" class="group flex flex-col gap-4 rounded-2xl bg-purple-50 dark:bg-purple-900/30 p-4 transition-colors hover:bg-purple-100 dark:hover:bg-purple-900/40">
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-600 text-white shadow-md shadow-purple-600/30">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Routers</p>
                                <p class="text-xs text-slate-500 dark:text-gray-400">Konfigurasi jaringan</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('monitoring.jobs') }}" class="group flex flex-col gap-4 rounded-2xl bg-yellow-50 dark:bg-yellow-900/30 p-4 transition-colors hover:bg-yellow-100 dark:hover:bg-yellow-900/40">
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yellow-500 text-white shadow-md shadow-yellow-500/30">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Job Monitor</p>
                                <p class="text-xs text-slate-500 dark:text-gray-400">Pantau job berjalan</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        @endif

        @if(!Auth::user()->isAdminArea())
        <!-- System Status -->
        <div class="app-card">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-700 px-5 py-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Status Sistem</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Kondisi layanan saat ini</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-3">
                <div class="flex items-center gap-3 rounded-2xl bg-green-50 dark:bg-green-900/20 p-4">
                    <div class="relative flex h-3 w-3 shrink-0">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-60"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">Database</p>
                        <p class="text-xs text-green-600 dark:text-green-400">Operasional</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl bg-green-50 dark:bg-green-900/20 p-4">
                    <div class="relative flex h-3 w-3 shrink-0">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-60"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">API Services</p>
                        <p class="text-xs text-green-600 dark:text-green-400">Semua sistem berjalan</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl bg-yellow-50 dark:bg-yellow-900/20 p-4">
                    <div class="relative flex h-3 w-3 shrink-0">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-yellow-500 opacity-60"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-yellow-500"></span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">Network Load</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">Penggunaan sedang</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-admin-layout>