<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Barang Keluar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catat barang keluar dari gudang</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <x-card x-data="{ open: false, rows: [{ item_id: '', quantity: 1 }] }">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Catat Barang Keluar</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pengeluaran barang dari gudang (instalasi, penjualan, rusak, dll)</p>
                </div>
                <button type="button" @click="open = !open" class="app-btn-primary px-4 py-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span x-text="open ? 'Tutup' : 'Catat Barang Keluar'"></span>
                </button>
            </div>

            <template x-if="open">
                <form method="POST" action="{{ route('gudang.barang-keluar.store') }}" class="mt-6 space-y-6 border-t border-slate-100 dark:border-gray-700 pt-6">
                    @csrf

                    @if($errors->any())
                        <x-alert variant="danger" dismissible>
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="recipient" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Penerima / Tujuan</label>
                            <input type="text" name="recipient" id="recipient" value="{{ old('recipient') }}" maxlength="150" placeholder="Nama petugas / lokasi" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan / Keperluan</label>
                            <input type="text" name="reason" id="reason" value="{{ old('reason') }}" maxlength="200" placeholder="Instalasi baru, penjualan, rusak..." class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Daftar Barang <span class="text-red-500">*</span></label>
                            <button type="button" @click="rows.push({ item_id: '', quantity: 1 })" class="btn-sm btn-soft">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Baris
                            </button>
                        </div>

                        <div class="mt-3 space-y-3">
                            <template x-for="(row, index) in rows" :key="index">
                                <div class="flex flex-col gap-3 rounded-xl border border-gray-200 dark:border-gray-600 p-3 sm:flex-row sm:items-center">
                                    <div class="flex-1">
                                        <select :name="`items[${index}][item_id]`" x-model="row.item_id" required class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Pilih barang...</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->code }} — {{ $item->name }} (stok: {{ $item->current_stock }} {{ $item->unit }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-full sm:w-36">
                                        <input type="number" :name="`items[${index}][quantity]`" x-model.number="row.quantity" min="1" required placeholder="Jumlah" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <button type="button" @click="rows.splice(index, 1)" class="icon-btn-danger shrink-0" title="Hapus baris">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <p x-show="rows.length === 0" class="text-sm text-gray-500 dark:text-gray-400">Belum ada baris barang. Klik "Tambah Baris" untuk menambahkan.</p>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label>
                        <textarea name="notes" id="notes" rows="2" maxlength="1000" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Simpan Barang Keluar</button>
                    </div>
                </form>
            </template>
        </x-card>

        <x-card>
            <form method="GET" action="{{ route('gudang.barang-keluar') }}" class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor transaksi / penerima / alasan..." class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($transactions->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21v-12m0 0l-4 4m4-4l4 4M4 7V5a2 2 0 012-2h12a2 2 0 012 2v2" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Barang Keluar</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catat barang keluar pertama menggunakan form di atas.</p>
                </div>
            </x-card>
        @else
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">No. Transaksi</th>
                                <th class="text-left">Tanggal</th>
                                <th class="text-left">Penerima</th>
                                <th class="text-left">Alasan</th>
                                <th class="text-right">Jumlah Item</th>
                                <th class="text-left">Pencatat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr x-data="{ open: false }" @click="open = !open" class="cursor-pointer">
                                    <td class="whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-mono font-medium bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded">{{ $transaction->transaction_number }}</span>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $transaction->transaction_date->format('d M Y') }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $transaction->recipient ?? '—' }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $transaction->reason ?? '—' }}</td>
                                    <td class="whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">{{ $transaction->items->sum('quantity') }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $transaction->user?->name ?? '—' }}</td>
                                </tr>
                                <tr x-show="open" x-cloak class="!bg-gray-50 dark:!bg-gray-700/40">
                                    <td colspan="6" class="!whitespace-normal px-5 py-4">
                                        <div class="space-y-2">
                                            @foreach($transaction->items as $line)
                                                <div class="flex items-center justify-between gap-3 text-sm">
                                                    <span class="text-gray-700 dark:text-gray-200">{{ $line->item?->code }} — {{ $line->item?->name }}</span>
                                                    <span class="font-semibold text-rose-600 dark:text-rose-400">-{{ $line->quantity }} {{ $line->item?->unit }}</span>
                                                </div>
                                            @endforeach
                                            @if($transaction->notes)
                                                <p class="pt-2 text-xs text-gray-400">Catatan: {{ $transaction->notes }}</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $transactions->links() }}</div>
        @endif
    </div>
</x-admin-layout>