<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tugas Perbaikan</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar tiket gangguan, kendala jaringan, dan tugas perbaikan pelanggan</p>
            </div>
            @if(auth()->user()->canManageTeknisiTasks())
            <div>
                <a href="{{ route('teknisi.repair-tasks.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">
                    + Buat Tugas Baru
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    @if(session('success'))
        <x-alert variant="success" dismissible class="mb-6">{{ session('success') }}</x-alert>
    @endif

    @if(session('error'))
        <x-alert variant="danger" dismissible class="mb-6">{{ session('error') }}</x-alert>
    @endif

    <div class="space-y-6">
        @if(auth()->user()->canManageTeknisiTasks())
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Perbaikan Baru</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['baru'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Menunggu penanganan</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Dalam Proses</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['proses'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Sedang dikerjakan teknisi</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Selesai Hari Ini</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['selesai_hari_ini'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Tiket terselesaikan</p>
                </div>
            </div>

            <x-card>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Alamat</th>
                                <th class="px-4 py-3">Keterangan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Ditugaskan</th>
                                <th class="px-4 py-3">Teknisi</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($tasks as $task)
                                @include('teknisi.repair-tasks.partials.task-table-row', ['task' => $task])
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Belum ada tugas perbaikan</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($tasks->hasPages())
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </x-card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Tersedia</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['tersedia'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Tugas yang bisa diambil</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Tugas Saya</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['tugas_saya'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Sedang dikerjakan</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Selesai Bulan Ini</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['selesai_bulan_ini'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Tugas terselesaikan</p>
                </div>
            </div>

            <div x-data="{ activeTab: 'tersedia' }">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex gap-6 px-4" aria-label="Tabs">
                        <button @click="activeTab = 'tersedia'" :class="activeTab === 'tersedia' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                            Tersedia
                        </button>
                        <button @click="activeTab = 'tugas-saya'" :class="activeTab === 'tugas-saya' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                            Tugas Saya
                        </button>
                        <button @click="activeTab = 'selesai'" :class="activeTab === 'selesai' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                            Selesai
                        </button>
                    </nav>
                </div>

                <div class="mt-6">
                    <div x-show="activeTab === 'tersedia'" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse($tasks->where('status', 'baru') as $task)
                            @include('teknisi.repair-tasks.partials.task-card', ['task' => $task, 'showTakeButton' => true])
                        @empty
                            <div class="col-span-full">
                                <x-card>
                                    <div class="py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada tugas tersedia saat ini</p>
                                    </div>
                                </x-card>
                            </div>
                        @endforelse
                    </div>

                    <div x-show="activeTab === 'tugas-saya'" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse($tasks->where('status', 'proses')->where('taken_by_user_id', auth()->id()) as $task)
                            @include('teknisi.repair-tasks.partials.task-card', ['task' => $task, 'showCompleteButton' => true])
                        @empty
                            <div class="col-span-full">
                                <x-card>
                                    <div class="py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Anda belum mengambil tugas apapun</p>
                                    </div>
                                </x-card>
                            </div>
                        @endforelse
                    </div>

                    <div x-show="activeTab === 'selesai'" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse($tasks->where('status', 'selesai')->where('taken_by_user_id', auth()->id()) as $task)
                            @include('teknisi.repair-tasks.partials.task-card', ['task' => $task])
                        @empty
                            <div class="col-span-full">
                                <x-card>
                                    <div class="py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Belum ada tugas yang diselesaikan</p>
                                    </div>
                                </x-card>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
