<x-portal-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Tagihan</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Seluruh riwayat tagihan {{ $customer->name }} ({{ $customer->customer_code }})</p>
    </div>

    @if($invoices->isEmpty())
        <x-card>
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">Belum ada tagihan.</p>
            </div>
        </x-card>
    @else
        <x-card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor Invoice</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Metode</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invoices as $invoice)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $invoice->billing_period }}</td>
                                <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-400">{{ $invoice->invoice_number }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">@currency($invoice->amount)</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $invoice->due_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3"><x-badge variant="{{ $invoice->status_color }}">{{ $invoice->status_label }}</x-badge></td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $invoice->payment_method?->label() ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('portal.invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-800">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $invoices->links() }}</div>
        </x-card>
    @endif
</x-portal-layout>
