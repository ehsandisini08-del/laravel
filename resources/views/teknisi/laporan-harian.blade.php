<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Harian</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rekapitulasi tugas perbaikan yang diselesaikan teknisi</p>
            </div>
            @if(auth()->user()->canManageTeknisiTasks())
                <a href="{{ route('teknisi.laporan-harian.export', array_filter(['date' => $date, 'teknisi' => $teknisiFilter])) }}"
                   class="app-btn-ghost flex items-center gap-2 px-4 py-2.5 text-sm text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 border border-green-300 dark:border-green-700 hover:border-green-400"
                   title="Download CSV laporan harian tanggal {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Selesai Tanggal Ini</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_hari_ini'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</p>
            </div>
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Selesai Bulan Ini</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_bulan_ini'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ now()->translatedFormat('F Y') }}</p>
            </div>
            <div class="app-card p-5 flex items-center gap-4">
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Melihat Laporan</p>
                    <p class="mt-2 text-sm font-bold text-gray-900 dark:text-white truncate">
                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
                    </p>
                    @if($date !== today()->toDateString())
                        <a href="{{ route('teknisi.laporan-harian') }}" class="mt-1 text-xs text-blue-600 dark:text-blue-400 hover:underline">Kembali ke hari ini</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <x-card>
            <form method="GET" action="{{ route('teknisi.laporan-harian') }}" class="flex flex-wrap items-end gap-3 p-1">
                <div class="flex-1 min-w-[160px]">
                    <label for="filter_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal</label>
                    <input type="date" id="filter_date" name="date" value="{{ $date }}"
                           class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                @if(auth()->user()->canManageTeknisiTasks())
                    <div class="flex-1 min-w-[180px]">
                        <label for="filter_teknisi" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Teknisi</label>
                        <select id="filter_teknisi" name="teknisi"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Teknisi</option>
                            @foreach($teknisiList as $tek)
                                <option value="{{ $tek->id }}" @selected($teknisiFilter == $tek->id)>{{ $tek->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <button type="submit" class="app-btn-primary px-4 py-2 text-sm flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
            </form>
        </x-card>

        {{-- Laporan List --}}
        @if($laporans->isEmpty())
            <x-card>
                <div class="py-16 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Belum Ada Laporan</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Tidak ada tugas perbaikan yang diselesaikan pada tanggal <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</strong>.
                    </p>
                </div>
            </x-card>
        @else
            <div class="space-y-4">
                @foreach($laporans as $index => $task)
                    @php
                        $no = ($laporans->currentPage() - 1) * $laporans->perPage() + $loop->iteration;
                        $takenAt = $task->taken_at;
                        $completedAt = $task->completed_at;
                        $duration = ($takenAt && $completedAt) ? $takenAt->diffInMinutes($completedAt) : null;
                        $partners = $task->technicians->where('id', '!=', $task->taken_by_user_id);
                    @endphp
                    <div class="app-card overflow-hidden">
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 dark:border-gray-700 px-5 py-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-bold">
                                    {{ $no }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $task->nama_customer }}</h3>
                                        <span class="text-xs font-mono text-gray-400 dark:text-gray-500">#{{ $task->id }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $task->no_telp }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                {!! $task->status_badge !!}
                                @if($duration !== null)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $duration }} menit
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="grid grid-cols-1 gap-0 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700">
                            {{-- Lokasi & Masalah --}}
                            <div class="px-5 py-4 space-y-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Alamat</p>
                                    <div class="flex items-start gap-1.5">
                                        <svg class="h-4 w-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug">{{ $task->alamat }}</p>
                                            @if($task->latitude && $task->longitude)
                                                <a href="{{ $task->maps_link }}" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-0.5 inline-flex items-center gap-1">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    Buka di Maps
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Kendala / Masalah</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug">{{ $task->keterangan }}</p>
                                </div>
                            </div>

                            {{-- Keterangan Penyelesaian --}}
                            <div class="px-5 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Keterangan Penyelesaian</p>
                                @if($task->keterangan_teknisi)
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $task->keterangan_teknisi }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">Belum ada keterangan</p>
                                @endif

                                @if($task->foto_bukti)
                                    <div class="mt-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5">Foto Bukti</p>
                                        <a href="{{ Storage::url($task->foto_bukti) }}" target="_blank" class="block">
                                            <img src="{{ Storage::url($task->foto_bukti) }}"
                                                 alt="Foto bukti perbaikan #{{ $task->id }}"
                                                 class="h-28 w-full object-cover rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity cursor-zoom-in">
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- Info Teknisi & Waktu --}}
                            <div class="px-5 py-4 space-y-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5">Tim Teknisi</p>
                                    <div class="space-y-1.5">
                                        {{-- Lead --}}
                                        <div class="flex items-center gap-2">
                                            <div class="h-7 w-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                                {{ substr($task->takenBy?->name ?? '?', 0, 2) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $task->takenBy?->name ?? '-' }}</p>
                                                <p class="text-[10px] text-blue-600 dark:text-blue-400 font-medium">Lead</p>
                                            </div>
                                        </div>
                                        {{-- Partners --}}
                                        @foreach($partners as $partner)
                                            <div class="flex items-center gap-2">
                                                <div class="h-7 w-7 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 flex items-center justify-center text-[10px] font-bold shrink-0">
                                                    {{ substr($partner->name, 0, 2) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $partner->name }}</p>
                                                    <p class="text-[10px] text-gray-400 dark:text-gray-500">Rekan</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-gray-100 dark:border-gray-700 space-y-1">
                                    @if($takenAt)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500 dark:text-gray-400">Diambil</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $takenAt->format('H:i') }}</span>
                                        </div>
                                    @endif
                                    @if($completedAt)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500 dark:text-gray-400">Selesai</span>
                                            <span class="font-medium text-green-700 dark:text-green-400">{{ $completedAt->format('H:i') }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-500 dark:text-gray-400">Dibuat oleh</span>
                                        <span class="font-medium text-gray-700 dark:text-gray-300 truncate max-w-[110px] text-right">{{ $task->assignedBy?->name ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($laporans->hasPages())
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    {{ $laporans->links() }}
                </div>
            @endif
        @endif

    </div>
</x-admin-layout>
