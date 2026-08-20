<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Stok Opname</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Penyesuaian stok hasil stock opname / audit</p>
            </div>
            <a href="{{ route('gudang.opname.create') }}" class="app-btn-primary px-4 py-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Catat Opname
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <x-card>
            <form method="GET" action="{{ route('gudang.opname.index') }}" class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor transaksi / alasan..." class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Stok Opname</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catat penyesuaian stok pertama menggunakan tombol di atas.</p>
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
                                <th class="text-left">Alasan</th>
                                <th class="text-left">Barang</th>
                                <th class="text-right">Selisih</th>
                                <th class="text-left">Pencatat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                @foreach($transaction->items as $line)
                                    <tr>
                                        <td class="whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-mono font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded">{{ $transaction->transaction_number }}</span>
                                        </td>
                                        <td class="whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $transaction->transaction_date->format('d M Y') }}</td>
                                        <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $transaction->reason ?? '—' }}</td>
                                        <td class="whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $line->item?->code }} — {{ $line->item?->name }}</td>
                                        <td class="whitespace-nowrap text-right text-sm font-bold {{ $line->quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                            {{ $line->quantity > 0 ? '+' : '' }}{{ $line->quantity }} {{ $line->item?->unit }}
                                        </td>
                                        <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $transaction->user?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $transactions->links() }}</div>
        @endif
    </div>
</x-admin-layout>