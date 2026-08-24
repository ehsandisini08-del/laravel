<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Harian</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rekapitulasi tugas perbaikan yang diselesaikan teknisi</p>
            </div>
            @if(auth()->user()->canManageTeknisiTasks())
                <a href="{{ route('teknisi.laporan-harian.export', array_filter([
                        'date_from'   => $dateFrom,
                        'date_to'     => $dateTo,
                        'teknisi'     => $teknisiFilter,
                        'search'      => $search,
                    ])) }}"
                   class="flex items-center gap-2 rounded-lg border border-green-300 dark:border-green-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-5">

        {{-- Stats --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Selesai Hari Ini</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_hari_ini'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ now()->translatedFormat('d F Y') }}</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Selesai Bulan Ini</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_bulan_ini'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ now()->translatedFormat('F Y') }}</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Semua Waktu</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_selesai'] }}</p>
                <p class="mt-1 text-xs text-gray-500">Semua tugas yang diselesaikan</p>
            </div>
        </div>

        {{-- Filter --}}
        <x-card>
            <form method="GET" action="{{ route('teknisi.laporan-harian') }}"
                  class="flex flex-wrap items-end gap-3 p-1">

                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="f_search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cari</label>
                    <input type="text" id="f_search" name="search" value="{{ $search }}"
                           placeholder="Nama, telepon, alamat, kendala…"
                           class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                {{-- Date From --}}
                <div class="min-w-[150px]">
                    <label for="f_date_from" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                    <input type="date" id="f_date_from" name="date_from" value="{{ $dateFrom }}"
                           class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                {{-- Date To --}}
                <div class="min-w-[150px]">
                    <label for="f_date_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                    <input type="date" id="f_date_to" name="date_to" value="{{ $dateTo }}"
                           class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                {{-- Teknisi filter (admin only) --}}
                @if(auth()->user()->canManageTeknisiTasks() && $teknisiList->isNotEmpty())
                    <div class="min-w-[170px]">
                        <label for="f_teknisi" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Teknisi</label>
                        <select id="f_teknisi" name="teknisi"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Teknisi</option>
                            @foreach($teknisiList as $tek)
                                <option value="{{ $tek->id }}" @selected($teknisiFilter == $tek->id)>{{ $tek->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="app-btn-primary flex items-center gap-2 px-4 py-2 text-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                    </button>
                    @if($dateFrom || $dateTo || $teknisiFilter || $search)
                        <a href="{{ route('teknisi.laporan-harian') }}"
                           class="app-btn-ghost flex items-center gap-1 px-4 py-2 text-sm text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Table --}}
        <x-card>
            @if($laporans->isEmpty())
                <div class="py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Laporan</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada tugas perbaikan yang selesai sesuai filter yang dipilih.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50/60 dark:bg-gray-800/40">
                            <tr>
                                <th class="px-4 py-3 w-10 text-center">No</th>
                                <th class="px-4 py-3">Pelanggan</th>
                                <th class="px-4 py-3">Kendala</th>
                                <th class="px-4 py-3">Keterangan Penyelesaian</th>
                                <th class="px-4 py-3">Teknisi</th>
                                <th class="px-4 py-3">Tgl Selesai</th>
                                <th class="px-4 py-3 text-center">Durasi</th>
                                <th class="px-4 py-3 text-center">Foto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach($laporans as $task)
                                @php
                                    $no = ($laporans->currentPage() - 1) * $laporans->perPage() + $loop->iteration;
                                    $takenAt = $task->taken_at;
                                    $completedAt = $task->completed_at;
                                    $duration = ($takenAt && $completedAt) ? $takenAt->diffInMinutes($completedAt) : null;
                                    $partners = $task->technicians->where('id', '!=', $task->taken_by_user_id);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">

                                    {{-- No --}}
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">{{ $no }}</span>
                                    </td>

                                    {{-- Pelanggan --}}
                                    <td class="px-4 py-3 min-w-[180px]">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $task->nama_customer }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $task->no_telp }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-1">{{ Str::limit($task->alamat, 40) }}</p>
                                        @if($task->latitude && $task->longitude)
                                            <a href="{{ $task->maps_link }}" target="_blank"
                                               class="text-[11px] text-blue-500 hover:underline inline-flex items-center gap-0.5 mt-0.5">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                Maps
                                            </a>
                                        @endif
                                    </td>

                                    {{-- Kendala --}}
                                    <td class="px-4 py-3 min-w-[160px] max-w-[220px]">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug line-clamp-3">{{ $task->keterangan }}</p>
                                    </td>

                                    {{-- Keterangan Penyelesaian --}}
                                    <td class="px-4 py-3 min-w-[180px] max-w-[260px]">
                                        @if($task->keterangan_teknisi)
                                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug line-clamp-3">{{ $task->keterangan_teknisi }}</p>
                                        @else
                                            <span class="text-xs text-gray-400 italic">—</span>
                                        @endif
                                    </td>

                                    {{-- Teknisi --}}
                                    <td class="px-4 py-3 min-w-[140px]">
                                        <div class="space-y-1">
                                            {{-- Lead --}}
                                            <div class="flex items-center gap-1.5">
                                                <div class="h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                                    {{ substr($task->takenBy?->name ?? '?', 0, 2) }}
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold text-gray-900 dark:text-white leading-tight">{{ $task->takenBy?->name ?? '-' }}</p>
                                                    <p class="text-[10px] text-blue-500 dark:text-blue-400">Lead</p>
                                                </div>
                                            </div>
                                            {{-- Partners --}}
                                            @foreach($partners as $partner)
                                                <div class="flex items-center gap-1.5">
                                                    <div class="h-6 w-6 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 flex items-center justify-center text-[10px] font-bold shrink-0">
                                                        {{ substr($partner->name, 0, 2) }}
                                                    </div>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-tight">{{ $partner->name }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>

                                    {{-- Tgl Selesai --}}
                                    <td class="px-4 py-3 min-w-[130px]">
                                        @if($completedAt)
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $completedAt->format('d/m/Y') }}</p>
                                            <p class="text-xs text-green-600 dark:text-green-400">{{ $completedAt->format('H:i') }}</p>
                                            @if($takenAt)
                                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Ambil: {{ $takenAt->format('H:i') }}</p>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Durasi --}}
                                    <td class="px-4 py-3 text-center">
                                        @if($duration !== null)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full
                                                {{ $duration <= 30 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                                   ($duration <= 90 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' :
                                                   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                                                {{ $duration }}&nbsp;mnt
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Foto --}}
                                    <td class="px-4 py-3 text-center">
                                        @if($task->foto_bukti)
                                            <a href="{{ Storage::url($task->foto_bukti) }}" target="_blank"
                                               title="Lihat foto bukti">
                                                <img src="{{ Storage::url($task->foto_bukti) }}"
                                                     alt="Foto #{{ $task->id }}"
                                                     class="h-12 w-12 object-cover rounded-lg border border-gray-200 dark:border-gray-600 mx-auto hover:opacity-80 transition-opacity cursor-zoom-in">
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($laporans->hasPages())
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                        {{ $laporans->links() }}
                    </div>
                @endif

                <div class="border-t border-gray-100 dark:border-gray-700 px-4 py-2.5 text-right">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Menampilkan {{ $laporans->firstItem() }}–{{ $laporans->lastItem() }} dari {{ $laporans->total() }} laporan
                    </p>
                </div>
            @endif
        </x-card>

    </div>
</x-admin-layout>
