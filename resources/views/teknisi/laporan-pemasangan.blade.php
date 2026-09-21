<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Pemasangan</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dokumentasi hasil instalasi baru dan aktivasi pelanggan</p>
            </div>
            <a href="{{ route('teknisi.laporan-pemasangan.export', array_filter(['bulan' => $bulan])) }}"
               class="flex items-center gap-2 rounded-lg border border-green-300 dark:border-green-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
        </div>
    </x-slot>

    <div class="space-y-5">

        <div class="flex flex-wrap gap-3">
            <div class="app-card px-4 py-3 flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bulan Ini</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ $stats['bulan_ini'] }}</p>
                </div>
            </div>
            <div class="app-card px-4 py-3 flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <x-card>
            <form method="GET" action="{{ route('teknisi.laporan-pemasangan') }}"
                  class="flex flex-wrap items-end gap-3 p-1">

                <div class="flex-1 min-w-[180px]">
                    <label for="f_search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cari</label>
                    <input type="text" id="f_search" name="search" value="{{ $search }}"
                           placeholder="Nama, kode, telepon, alamat..."
                           class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="min-w-[160px]">
                    <label for="f_bulan" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Bulan</label>
                    <select id="f_bulan" name="bulan"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Bulan</option>
                        @for($i = 0; $i < 12; $i++)
                            @php $d = now()->startOfMonth()->subMonths($i); $val = $d->format('Y-m'); @endphp
                            <option value="{{ $val }}" @selected($bulan === $val)>{{ $d->translatedFormat('F Y') }}</option>
                        @endfor
                    </select>
                </div>

                <div class="min-w-[140px]">
                    <label for="f_date_from" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                    <input type="date" id="f_date_from" name="date_from" value="{{ $dateFrom }}"
                           class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="min-w-[140px]">
                    <label for="f_date_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                    <input type="date" id="f_date_to" name="date_to" value="{{ $dateTo }}"
                           class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="app-btn-primary flex items-center gap-2 px-4 py-2 text-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                    </button>
                    @if($search || $dateFrom || $dateTo || $bulan)
                        <a href="{{ route('teknisi.laporan-pemasangan') }}"
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

        <x-card>
            @if($reports->isEmpty())
                <div class="py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Laporan Pemasangan</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Laporan pemasangan akan otomatis dibuat saat menambahkan customer baru.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50/60 dark:bg-gray-800/40">
                            <tr>
                                <th class="px-4 py-3 w-10 text-center">No</th>
                                <th class="px-4 py-3">Pelanggan</th>
                                <th class="px-4 py-3">Paket</th>
                                <th class="px-4 py-3">Area</th>
                                <th class="px-4 py-3">ODP / Port</th>
                                <th class="px-4 py-3">Tgl Pemasangan</th>
                                <th class="px-4 py-3">RX Power</th>
                                <th class="px-4 py-3">Perangkat / Merk</th>
                                <th class="px-4 py-3">Part Yang Digunakan</th>
                                <th class="px-4 py-3">Dibuat Oleh</th>
                                <th class="px-4 py-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach($reports as $report)
                                @php
                                    $no = ($reports->currentPage() - 1) * $reports->perPage() + $loop->iteration;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">{{ $no }}</span>
                                    </td>

                                    <td class="px-4 py-3 min-w-[180px]">
                                        @if($report->customer)
                                            <a href="{{ route('customers.show', $report->customer) }}" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">{{ $report->customer->name }}</a>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $report->customer->customer_code }}</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-1">{{ Str::limit($report->customer->address, 40) }}</p>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Customer dihapus</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($report->customer?->package)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">
                                                {{ $report->customer->package->name }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $report->customer?->area?->name ?? '—' }}</span>
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($report->customer?->odp)
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->customer->odp->kode }}</p>
                                            @if($report->port_odp ?? $report->customer->port_odp)
                                                <p class="text-xs text-gray-500">Port {{ $report->port_odp ?? $report->customer->port_odp }}</p>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($report->installation_date)
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($report->installation_date)->format('d/m/Y') }}</p>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($report->rx_power)
                                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $report->rx_power }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($report->device_name)
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $report->device_name }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 max-w-[200px]">
                                        @if($report->parts_used)
                                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug line-clamp-2">{{ $report->parts_used }}</p>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($report->user)
                                            <div class="flex items-center gap-1.5">
                                                <div class="h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                                    {{ substr($report->user->name, 0, 2) }}
                                                </div>
                                                <p class="text-xs font-semibold text-gray-900 dark:text-white leading-tight">{{ $report->user->name }}</p>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 max-w-[200px]">
                                        @if($report->notes)
                                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug line-clamp-2">{{ $report->notes }}</p>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($reports->hasPages())
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                        {{ $reports->links() }}
                    </div>
                @endif

                <div class="border-t border-gray-100 dark:border-gray-700 px-4 py-2.5 text-right">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Menampilkan {{ $reports->firstItem() }}–{{ $reports->lastItem() }} dari {{ $reports->total() }} laporan
                    </p>
                </div>
            @endif
        </x-card>

    </div>
</x-admin-layout>
