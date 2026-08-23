<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pekerjaan</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitoring seluruh antrean pekerjaan lapangan (instalasi baru, mutasi, penarikan, & maintenance)</p>
            </div>
            @if(auth()->user()->canManageTeknisiTasks())
            <div>
                <a href="{{ route('teknisi.buat-tugas') }}" class="app-btn-primary px-4 py-2.5 text-sm">
                    + Buat Tugas Baru
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pekerjaan</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Semua kategori</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Menunggu</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Antrean pekerjaan</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Sedang Dikerjakan</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Dalam progres</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Selesai</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Telah ditutup</p>
            </div>
        </div>

        <x-card>
            <div class="py-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-600 ring-8 ring-cyan-50/50 dark:bg-cyan-900/30 dark:text-cyan-400 dark:ring-cyan-900/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Daftar Pekerjaan Lapangan</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Dashboard pekerjaan teknisi untuk melacak status pemasangan, perbaikan kabel fiber, penggantian perangkat, dan survei lokasi.
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
