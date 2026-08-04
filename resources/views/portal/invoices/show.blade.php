<x-portal-layout>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoice {{ $invoice->invoice_number }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->billing_period }}</p>
        </div>
        <div class="flex items-center gap-3">
            <x-badge variant="{{ $invoice->status_color }}">{{ $invoice->status_label }}</x-badge>
            <a href="{{ route('portal.invoices.index') }}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Rincian Tagihan">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nomor Invoice</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Periode</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->billing_period }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Paket</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->package?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Jatuh Tempo</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->due_date?->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1"><x-badge variant="{{ $invoice->status_color }}">{{ $invoice->status_label }}</x-badge></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Metode Pembayaran</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->payment_method?->label() ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tanggal Bayar</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->paid_at?->format('d M Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Item Tagihan">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item->description }}</td>
                                <td class="px-4 py-2 text-sm text-center text-gray-600">{{ $item->qty }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-600">@currency($item->price)</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">@currency($item->subtotal)</td>
                            </tr>
                        @endforeach
                        <tr class="font-bold">
                            <td colspan="3" class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">Total</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">@currency($invoice->amount)</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Pembayaran">
                @if(in_array($invoice->status->value, ['unpaid', 'overdue']))
                    <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-500">Total yang harus dibayar</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">@currency($invoice->amount)</p>
                    </div>

                    @php
                        $pendingPayment = $invoice->payments->first(fn ($p) => $p->status?->value === 'pending');
                    @endphp

                    @if($pendingPayment)
                        <p class="mb-3 text-xs text-yellow-700 dark:text-yellow-300">Pembayaran sedang menunggu. Klik tombol di bawah untuk melanjutkan pembayaran yang sudah dibuat.</p>
                    @endif

                    @if($paymentProvider !== 'none')
                        <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}">
                            @csrf
                            <button type="submit" class="block w-full text-center px-4 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                {{ $pendingPayment ? 'Lanjutkan Pembayaran' : 'Bayar Sekarang' }}
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500">Pembayaran online belum tersedia. Silakan hubungi admin untuk informasi pembayaran.</p>
                    @endif
                @elseif($invoice->status->value === 'paid')
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-sm font-medium text-green-700 dark:text-green-300">Invoice sudah lunas</p>
                        <p class="mt-1 text-xs text-gray-500">Terima kasih. Pembayaran Anda telah kami terima.</p>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Invoice ini tidak dapat dibayar.</p>
                @endif
            </x-card>

            <x-card title="Riwayat Pembayaran">
                @if($invoice->payments->isEmpty())
                    <p class="text-sm text-gray-500">Belum ada pembayaran.</p>
                @else
                    <div class="space-y-3">
                        @foreach($invoice->payments as $payment)
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">{{ $payment->method_label }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    @currency($payment->amount) · {{ $payment->paid_at?->format('d M Y H:i') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-portal-layout>
