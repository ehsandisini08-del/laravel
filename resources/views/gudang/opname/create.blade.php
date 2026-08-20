<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Catat Stok Opname</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sesuaikan stok berdasarkan hasil stock opname fisik</p>
            </div>
            <a href="{{ route('gudang.opname.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        @if($errors->any())
            <x-alert variant="danger" dismissible class="mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('gudang.opname.store') }}" class="space-y-6" x-data="{ currentStock: null, unit: '' }">
            @csrf

            <x-card title="Penyesuaian Stok">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="item_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barang <span class="text-red-500">*</span></label>
                        <select name="item_id" id="item_id" required x-on:change="currentStock = $event.target.selectedOptions[0].dataset.stock; unit = $event.target.selectedOptions[0].dataset.unit" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="" data-stock="" data-unit="">Pilih barang...</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-stock="{{ $item->current_stock }}" data-unit="{{ $item->unit }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->code }} — {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="currentStock !== null">
                            Stok saat ini di sistem: <span class="font-semibold" x-text="currentStock + ' ' + unit"></span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stok Fisik Hasil Opname <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" min="0" required placeholder="0" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jumlah stok yang ditemukan saat opname. Selisih dihitung otomatis.</p>
                        </div>
                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Opname <span class="text-red-500">*</span></label>
                            <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Penyesuaian <span class="text-red-500">*</span></label>
                        <input type="text" name="reason" id="reason" value="{{ old('reason') }}" maxlength="200" required placeholder="Contoh: hasil stock opname bulanan, barang rusak ditemukan, dll." class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label>
                        <textarea name="notes" id="notes" rows="3" maxlength="1000" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('gudang.opname.index') }}" class="app-btn-ghost">Batal</a>
                <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Simpan Opname</button>
            </div>
        </form>
    </div>
</x-admin-layout>