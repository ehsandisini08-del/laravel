<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoice {{ $invoice->invoice_number }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $invoice->billing_period }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-badge variant="{{ $invoice->status_color }}">{{ $invoice->status_label }}</x-badge>
                <a href="{{ route('billing.invoices.print', $invoice) }}" target="_blank" class="btn-sm btn-neutral">
                    Cetak
                </a>
                @if(auth()->user()->canDeleteInvoices())
                <form method="POST" action="{{ route('billing.invoices.destroy', $invoice) }}" x-data @submit.prevent="async () => { if(await customConfirm('Hapus invoice {{ $invoice->invoice_number }} ({{ $invoice->billing_period }})? Tindakan ini tidak dapat dibatalkan.', { confirmLabel: 'Ya, Hapus', confirmColor: 'red' })) $el.submit() }">
                    @csrf @method('DELETE')
                    <button type="submit" class="app-btn-danger-ghost px-4 py-2.5 text-sm">
                        Hapus
                    </button>
                </form>
                @endif
                @if(in_array($invoice->status->value, ['unpaid', 'overdue']))
                <form method="POST" action="{{ route('billing.invoices.pay', $invoice) }}" x-data @submit.prevent="async () => { if(await customConfirm('Tandai invoice {{ $invoice->invoice_number }} sebagai dibayar (Cash)?', { confirmLabel: 'Ya, Bayar', confirmColor: 'green' })) $el.submit() }">
                    @csrf
                    <button type="submit" class="app-btn-success px-4 py-2.5 text-sm">
                        Bayar
                    </button>
                </form>
                @endif
                <a href="{{ route('billing.invoices.index') }}" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" aria-label="Tutup">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Detail Invoice">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nomor Invoice</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Periode Tagihan</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->billing_period }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Pelanggan</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <a href="{{ route('customers.show', $invoice->customer) }}" class="text-blue-600 hover:text-blue-800">{{ $invoice->customer?->name }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Paket</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->package?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Router</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->router?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Area</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->customer?->area?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Jatuh Tempo</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->due_date?->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tanggal Isolir</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->isolation_day ? 'Tanggal '.$invoice->isolation_day : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Jumlah</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900 dark:text-white">@currency($invoice->amount)</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Metode Pembayaran</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->payment_method?->label() ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Dibayar Pada</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->paid_at?->format('d M Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Item Invoice">
                <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th class="text-left">Deskripsi</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
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
            <x-card title="Riwayat Isolir">
                @if($invoice->isolationLogs->isEmpty())
                    <p class="text-sm text-gray-500">Tidak ada catatan isolir.</p>
                @else
                    <div class="space-y-3">
                        @foreach($invoice->isolationLogs as $log)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->action === 'disabled' ? 'Dimitikan' : 'Diaktifkan' }}</p>
                                <p class="text-xs text-gray-500">{{ $log->executed_at?->format('d M Y H:i') }}</p>
                                @if($log->reason)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $log->reason }}</p>
                                @endif
                            </div>
                            <x-badge variant="{{ $log->status === 'success' ? 'success' : 'danger' }}">{{ $log->status === 'success' ? 'Berhasil' : 'Gagal' }}</x-badge>
                        </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card title="Riwayat Pembayaran">
                @if($invoice->payments->isEmpty())
                    <div class="text-center py-6">
                        <p class="text-sm text-gray-500">Belum ada pembayaran.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($invoice->payments as $payment)
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">{{ $payment->method_label }}</p>
                                <x-badge variant="success">{{ $payment->status?->label() }}</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                @currency($payment->amount) · {{ $payment->paid_at?->format('d M Y H:i') }}
                            </p>
                            @if($payment->gateway_provider !== 'manual')
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ ucfirst($payment->gateway_provider) }} @if($payment->reference) · Ref: {{ $payment->reference }} @endif
                                </p>
                            @elseif($payment->paidByUser)
                                <p class="text-xs text-gray-500 mt-0.5">Diterima oleh {{ $payment->paidByUser->name }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-admin-layout>
