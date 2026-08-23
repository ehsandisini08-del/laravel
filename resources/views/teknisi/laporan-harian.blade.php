<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Harian</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rekapitulasi aktivitas, kendala harian, dan log kegiatan teknisi lapangan</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="py-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 ring-8 ring-indigo-50/50 dark:bg-indigo-900/30 dark:text-indigo-400 dark:ring-indigo-900/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Laporan Harian Teknisi</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Fitur untuk mencatat dan mereview ringkasan aktivitas harian, absensi lapangan, kendala teknis, serta material yang digunakan setiap hari.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                        🚧 Modul Dalam Pengembangan (Placeholder)
                    </span>
                </div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
