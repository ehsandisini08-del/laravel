<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Pemasangan</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dokumentasi hasil instalasi baru, aktivasi pelanggan, dan berita acara pemasangan</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pemasangan Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Total instalasi baru</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Aktif Terverifikasi</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Selesai & online</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Rata-rata Redaman</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">- dBm</p>
                <p class="mt-1 text-xs text-gray-500">Kualitas sinyal optik</p>
            </div>
        </div>

        <x-card>
            <div class="py-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50 dark:bg-emerald-900/30 dark:text-emerald-400 dark:ring-emerald-900/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Laporan & Berita Acara Pemasangan</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Daftar histori pemasangan baru beserta dokumentasi foto instalasi, titik ODP, nomor port, redaman RX Power, dan serial modem/CPE.
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
