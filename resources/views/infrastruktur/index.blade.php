<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Infrastruktur</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola infrastruktur jaringan</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div x-data="{ activeTab: 'odc' }">
            <div class="mb-6 flex flex-wrap gap-2">
                <button type="button"
                    @click="activeTab = 'odc'"
                    :class="activeTab === 'odc' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M5 6l7-3 7 3M4 10h16v11H4V10z" />
                    </svg>
                    ODC
                </button>
                <button type="button"
                    @click="activeTab = 'odp'"
                    :class="activeTab === 'odp' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM8 9h8M8 13h8M8 17h5" />
                    </svg>
                    ODP
                </button>
                <button type="button"
                    @click="activeTab = 'map'"
                    :class="activeTab === 'map' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13a3 3 0 100-6 3 3 0 000 6z" />
                    </svg>
                    MAP
                </button>
            </div>

            <div x-show="activeTab === 'odc'" class="pt-6">
                <x-card>
                    <div class="text-center py-16">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M5 6l7-3 7 3M4 10h16v11H4V10z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">ODC</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">Modul ODC (Optical Distribution Cabinet) akan tersedia di sini.</p>
                        <div class="mt-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200">Coming Soon</span>
                        </div>
                    </div>
                </x-card>
            </div>

            <div x-show="activeTab === 'odp'" class="pt-6" x-cloak>
                <x-card>
                    <div class="text-center py-16">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM8 9h8M8 13h8M8 17h5" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">ODP</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">Modul ODP (Optical Distribution Point) akan tersedia di sini.</p>
                        <div class="mt-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200">Coming Soon</span>
                        </div>
                    </div>
                </x-card>
            </div>

            <div x-show="activeTab === 'map'" class="pt-6" x-cloak>
                <x-card>
                    <div class="text-center py-16">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 13a3 3 0 100-6 3 3 0 000 6z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">MAP</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">Modul MAP (peta infrastruktur) akan tersedia di sini.</p>
                        <div class="mt-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200">Coming Soon</span>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>