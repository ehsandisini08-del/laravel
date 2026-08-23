<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tugas Perbaikan</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar tiket gangguan, kendala jaringan, dan tugas perbaikan pelanggan</p>
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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Perbaikan Baru</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Menunggu penanganan</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Dalam Proses</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Sedang dikerjakan teknisi</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Selesai Hari Ini</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0</p>
                <p class="mt-1 text-xs text-gray-500">Tiket terselesaikan</p>
            </div>
        </div>

        <x-card>
            <div class="py-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-8 ring-amber-50/50 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-900/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.017l4.085 1.203a1.5 1.5 0 011.026 1.838l-.66 2.246a1.5 1.5 0 01-1.838 1.026l-4.085-1.203a3.57 3.57 0 01-1.378-.855" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Belum Ada Tugas Perbaikan</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Semua tiket gangguan dan tugas perbaikan jaringan/pelanggan akan ditampilkan di sini.
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
