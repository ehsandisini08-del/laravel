<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Welcome back, {{ Auth::user()->name }}!</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ now()->format('l, F j, Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card 
                label="Total Routers" 
                value="{{ $totalRouters }}" 
                color="blue"
                :icon="'<svg class=\"h-6 w-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01\" /></svg>'" 
            />

            <x-stat-card 
                label="Online Routers" 
                value="{{ $onlineRouters }}" 
                color="green"
                :icon="'<svg class=\"h-6 w-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\" /></svg>'" 
            />

            <x-stat-card 
                label="Offline Routers" 
                value="{{ $offlineRouters }}" 
                color="red"
                :icon="'<svg class=\"h-6 w-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z\" /></svg>'" 
            />

            <x-stat-card 
                label="System Status" 
                value="Active" 
                color="purple"
                :icon="'<svg class=\"h-6 w-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\" /></svg>'" 
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-card title="Recent Activity">
                <div class="flow-root">
                    <ul class="-my-5 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentLogs as $log)
                            @php $props = $log->properties ?? []; @endphp
                            <li class="py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-xs">
                                        {{ strtoupper(substr($log->causer?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $log->description }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $log->causer?->name ?? 'System' }} &middot; {{ $props['module'] ?? '-' }}</p>
                                    </div>
                                    <div class="flex-shrink-0 text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No activity yet.</li>
                        @endforelse
                    </ul>
                </div>
            </x-card>

            <x-card title="Quick Actions">
                <div class="grid grid-cols-2 gap-4">
                    <a href="#" class="group relative rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-md transition-all">
                        <div class="flex flex-col items-center text-center gap-3">
                            <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Add Customer</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Register new customer</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('billing.invoices.index') }}" class="group relative rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 hover:border-green-500 dark:hover:border-green-500 hover:shadow-md transition-all">
                        <div class="flex flex-col items-center text-center gap-3">
                            <div class="h-12 w-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Billing</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Manage invoices</p>
                            </div>
                        </div>
                    </a>

                    <a href="#" class="group relative rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-md transition-all">
                        <div class="flex flex-col items-center text-center gap-3">
                            <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Manage Routers</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Configure network</p>
                            </div>
                        </div>
                    </a>

                    <a href="#" class="group relative rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 hover:border-yellow-500 dark:hover:border-yellow-500 hover:shadow-md transition-all">
                        <div class="flex flex-col items-center text-center gap-3">
                            <div class="h-12 w-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">View Reports</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Analytics & insights</p>
                            </div>
                        </div>
                    </a>
                </div>
            </x-card>
        </div>

        <x-card title="System Status">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 p-4">
                    <div class="flex-shrink-0">
                        <div class="h-3 w-3 rounded-full bg-green-500 animate-pulse"></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Database</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Operational</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 p-4">
                    <div class="flex-shrink-0">
                        <div class="h-3 w-3 rounded-full bg-green-500 animate-pulse"></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">API Services</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">All systems go</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4">
                    <div class="flex-shrink-0">
                        <div class="h-3 w-3 rounded-full bg-yellow-500 animate-pulse"></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Network Load</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Moderate usage</p>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
