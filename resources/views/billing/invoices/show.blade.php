<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoice {{ $invoice->invoice_number }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $invoice->billing_period }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-badge variant="{{ $invoice->status_color }}">{{ $invoice->status_label }}</x-badge>
                <a href="{{ route('billing.invoices.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Invoice Details">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Invoice Number</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Billing Period</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->billing_period }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Customer</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <a href="{{ route('customers.show', $invoice->customer) }}" class="text-blue-600 hover:text-blue-800">{{ $invoice->customer?->name }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Package</dt>
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
                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->due_date?->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Isolation Date</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->isolation_day ? 'Tanggal '.$invoice->isolation_day : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Paid At</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->paid_at?->format('d M Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Invoice Items">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item->description }}</td>
                            <td class="px-4 py-2 text-sm text-center text-gray-600">{{ $item->qty }}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="font-bold">
                            <td colspan="3" class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">Total</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Isolation History">
                @if($invoice->isolationLogs->isEmpty())
                    <p class="text-sm text-gray-500">No isolation records.</p>
                @else
                    <div class="space-y-3">
                        @foreach($invoice->isolationLogs as $log)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->action === 'disabled' ? 'Disabled' : 'Enabled' }}</p>
                                <p class="text-xs text-gray-500">{{ $log->executed_at?->format('d M Y H:i') }}</p>
                            </div>
                            <x-badge variant="{{ $log->status === 'success' ? 'success' : 'danger' }}">{{ $log->status }}</x-badge>
                        </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card title="Payment History">
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500">Payment history will be available when Payment Gateway is integrated.</p>
                </div>
            </x-card>
        </div>
    </div>
</x-admin-layout>