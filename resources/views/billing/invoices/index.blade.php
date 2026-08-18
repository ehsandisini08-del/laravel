<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoice</h1>
            @if(!Auth::user()->isAdminArea())
            <button type="button" x-data @click="$dispatch('open-modal', 'generate-invoice')" class="app-btn-success px-4 py-2.5 text-sm">
                Buat Invoice
            </button>
            @endif
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
                    @php
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                            4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ];
                    @endphp
                    <select name="month" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
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
            <x-card><div class="text-center py-12"><p class="text-gray-500">Tidak ada invoice.</p></div></x-card>
        @else
            <div x-data="{ selected: [], allIds: @json($invoices->pluck('id')->all()) }">
                @if(auth()->user()->canDeleteInvoices())
                <div class="mb-3 flex justify-end">
                    <form method="POST" action="{{ route('billing.invoices.destroy-many') }}" x-data @submit.prevent="async () => { if(await customConfirm('Hapus '+selected.length+' invoice terpilih? Tindakan ini tidak dapat dibatalkan.', { confirmLabel: 'Ya, Hapus', confirmColor: 'red' })) $el.submit() }">
                        @csrf @method('DELETE')
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <button type="submit" x-show="selected.length > 0" class="app-btn-danger-ghost px-4 py-2 text-sm">
                            Hapus Terpilih (<span x-text="selected.length"></span>)
                        </button>
                    </form>
                </div>
                @endif
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                    <thead>
                        <tr>
                            <th class="w-10">
                                @if(auth()->user()->canDeleteInvoices())
                                <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" :checked="selected.length > 0 && selected.length === allIds.length" @change="$event.target.checked ? selected = [...allIds] : selected = []">
                                @endif
                            </th>
                            <th class="text-left">No. Invoice</th>
                            <th class="text-left">Pelanggan</th>
                            <th class="text-left">Paket</th>
                            <th class="text-left">Total</th>
                            <th class="text-left">Jatuh Tempo</th>
                            <th class="text-left">Status</th>
                            <th class="text-left">Metode</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                            <tr>
                                @if(auth()->user()->canDeleteInvoices())
                                <td class="px-4 py-3">
                                    <input type="checkbox" value="{{ $inv->id }}" x-model="selected" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                </td>
                                @endif
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $inv->invoice_number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $inv->customer?->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $inv->package?->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">@currency($inv->amount)</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $inv->due_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3"><x-badge variant="{{ $inv->status_color }}">{{ $inv->status_label }}</x-badge></td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $inv->payment_method?->label() ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('billing.invoices.show', $inv) }}" class="text-blue-600 hover:text-blue-800">Detail</a>
                                        @if(auth()->user()->canDeleteInvoices())
                                        <form method="POST" action="{{ route('billing.invoices.destroy', $inv) }}" x-data @submit.prevent="async () => { if(await customConfirm('Hapus invoice {{ $inv->invoice_number }} ({{ $inv->billing_period }})? Tindakan ini tidak dapat dibatalkan.', { confirmLabel: 'Ya, Hapus', confirmColor: 'red' })) $el.submit() }">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">Hapus</button>
                                        </form>
                                        @endif
                                        @if(in_array($inv->status->value, ['unpaid', 'overdue']))
                                        <form method="POST" action="{{ route('billing.invoices.pay', $inv) }}" x-data @submit.prevent="async () => { if(await customConfirm('Tandai invoice {{ $inv->invoice_number }} sebagai dibayar (Cash)?', { confirmLabel: 'Ya, Bayar', confirmColor: 'green' })) $el.submit() }">
                                            @csrf
                                            <button type="submit" class="btn-sm bg-green-600 text-white">Bayar</button>
                                        </form>
                                        @endif
                                    </div>
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

    @if(!Auth::user()->isAdminArea())
    <x-modal name="generate-invoice" maxWidth="md">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Buat Invoice</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih bulan yang ingin dibuatkan invoice.</p>

            <form method="POST" action="{{ route('billing.generate') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="generate-month" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                        <select id="generate-month" name="month" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($months as $m => $monthName)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="generate-year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                        <select id="generate-year" name="year" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(range(now()->year - 1, now()->year + 2) as $y)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="btn-sm btn-neutral" @click="$dispatch('close-modal', 'generate-invoice')">Batal</button>
                    <button type="submit" class="app-btn-success px-4 py-2 text-sm">Generate</button>
                </div>
            </form>
        </div>
    </x-modal>
    @endif
</x-admin-layout>
