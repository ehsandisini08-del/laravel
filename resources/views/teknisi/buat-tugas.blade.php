<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Buat Tugas</h1>
                    <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/40 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                        Developer & Superadmin
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pembuatan dan pembagian tugas kerja teknisi lapangan</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="py-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-8 ring-blue-50/50 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-900/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Form Pembuatan Tugas Teknisi</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Fitur ini digunakan oleh admin untuk membuat tiket pekerjaan, menugaskan teknisi, menentukan prioritas, dan menjadwalkan pemasangan atau perbaikan.
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
