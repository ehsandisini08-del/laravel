<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $item->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Detail barang dan riwayat pergerakan stok</p>
            </div>
            <a href="{{ route('gudang.barang.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kode</p>
                <p class="mt-1 font-mono text-lg font-bold text-gray-900 dark:text-white">{{ $item->code }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kategori</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $item->category?->name ?? '—' }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">Stok Saat Ini</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $item->current_stock }} {{ $item->unit }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">Stok Minimum</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $item->min_stock }} {{ $item->unit }}</p>
            </x-card>
        </div>

        @if($item->description)
            <x-card title="Deskripsi">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $item->description }}</p>
            </x-card>
        @endif

        <x-card title="Riwayat Pergerakan Stok">
            @if($movements->isEmpty())
                <div class="text-center py-10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada pergerakan stok untuk barang ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Waktu</th>
                                <th class="text-left">No. Transaksi</th>
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
                                        @if($movement->type === 'in')
                                            <x-badge variant="success">Masuk</x-badge>
                                        @elseif($movement->type === 'out')
                                            <x-badge variant="danger">Keluar</x-badge>
                                        @else
                                            <x-badge variant="warning">Opname</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm font-bold {{ $movement->isIncrease() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }} {{ $item->unit }}
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">{{ $movement->stock_before }}</td>
                                    <td class="whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">{{ $movement->stock_after }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $movement->user?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-admin-layout>