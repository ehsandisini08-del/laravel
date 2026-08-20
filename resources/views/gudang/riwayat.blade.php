<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Riwayat / Jejak Stok</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Seluruh pergerakan stok tercatat permanen di sini</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <form method="GET" action="{{ route('gudang.riwayat') }}" class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <select name="item_id" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Barang</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>{{ $item->code }} — {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <select name="type" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Tipe</option>
                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Barang Masuk</option>
                    <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Barang Keluar</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Stok Opname</option>
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($movements->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Riwayat</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Riwayat pergerakan stok akan muncul di sini.</p>
                </div>
            </x-card>
        @else
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Waktu</th>
                                <th class="text-left">No. Transaksi</th>
                                <th class="text-left">Barang</th>
                                <th class="text-left">Tipe</th>
                                <th class="text-right">Perubahan</th>
                                <th class="text-right">Stok Sebelum</th>
                                <th class="text-right">Stok Sesudah</th>
                                <th class="text-left">Pencatat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $movement->moved_at->format('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-mono font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded">{{ $movement->transaction?->transaction_number ?? '—' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('gudang.barang.show', $movement->item) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $movement->item?->code }} — {{ $movement->item?->name }}</a>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if($movement->type === 'in')
                                            <x-badge variant="success">Masuk</x-badge>
                                        @elseif($movement->type === 'out')
                                            <x-badge variant="danger">Keluar</x-badge>
                                        @else
                                            <x-badge variant="warning">Opname</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm font-bold {{ $movement->isIncrease() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }} {{ $movement->item?->unit }}
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">{{ $movement->stock_before }}</td>
                                    <td class="whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">{{ $movement->stock_after }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $movement->user?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $movements->links() }}</div>
        @endif
    </div>
</x-admin-layout>