<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cetak Invoice</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih invoice lalu cetak satu per satu atau sekaligus.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif

        <x-card>
            <form method="GET" class="space-y-3">
                <div class="flex flex-col md:flex-row gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari invoice atau pelanggan..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Bayar</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Telat Bayar</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Sudah Bayar</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    <button type="submit" class="btn-sm btn-neutral">Filter</button>
                </div>
                <div class="flex flex-wrap gap-3">
                    <select name="router_id" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Semua Router</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ request('router_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <select name="area_id" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Semua Area</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}" {{ request('area_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                    <select name="package_id" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Semua Paket</option>
                        @foreach($packages as $p)
                            <option value="{{ $p->id }}" {{ request('package_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <select name="month" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @php
                            $months = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                                10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ];
                        @endphp
                        @foreach($months as $m => $monthName)
                            <option value="{{ $m }}" {{ ($defaultMonth == $m) ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @foreach(range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ ($defaultYear == $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </x-card>

        @if($invoices->isEmpty())
            <x-card><div class="text-center py-12"><p class="text-gray-500">Tidak ada invoice yang cocok dengan filter.</p></div></x-card>
        @else
            <div x-data="{ selected: [], allIds: @json($invoices->pluck('id')->all()) }">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan <span class="font-semibold text-gray-900 dark:text-white">{{ $invoices->count() }}</span> invoice.
                        <span x-show="selected.length > 0" x-cloak class="text-blue-600">
                            <span x-text="selected.length"></span> dipilih.
                        </span>
                    </p>
                    <form method="GET" action="{{ route('billing.cetak-invoice.print') }}" target="_blank" class="flex gap-2" x-data @submit="if (selected.length === 0) { $event.preventDefault(); alert('Pilih minimal satu invoice untuk dicetak.'); }">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <a href="{{ route('billing.invoices.index') }}" class="btn-sm btn-neutral">Kembali ke Invoice</a>
                        <button type="submit" x-show="selected.length > 0" x-cloak class="app-btn-success px-4 py-2 text-sm">
                            Cetak Terpilih (<span x-text="selected.length"></span>)
                        </button>
                    </form>
                </div>

                <div class="admin-panel">
                    <div class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th class="w-10">
                                        <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" :checked="selected.length > 0 && selected.length === allIds.length" @change="$event.target.checked ? selected = [...allIds] : selected = []">
                                    </th>
                                    <th class="text-left">No. Invoice</th>
                                    <th class="text-left">Pelanggan</th>
                                    <th class="text-left">Paket</th>
                                    <th class="text-left">Total</th>
                                    <th class="text-left">Jatuh Tempo</th>
                                    <th class="text-left">Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $inv)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <input type="checkbox" value="{{ $inv->id }}" x-model="selected" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $inv->invoice_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $inv->customer?->name }}
                                            <span class="block text-xs text-gray-400">{{ $inv->customer?->phone }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $inv->package?->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">@currency($inv->amount)</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $inv->due_date?->format('d M Y') }}</td>
                                        <td class="px-4 py-3"><x-badge variant="{{ $inv->status_color }}">{{ $inv->status_label }}</x-badge></td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <a href="{{ route('billing.invoices.print', $inv) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" /></svg>
                                                Cetak
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">{{ $invoices->links() }}</div>
            </div>
        @endif
    </div>
</x-admin-layout>