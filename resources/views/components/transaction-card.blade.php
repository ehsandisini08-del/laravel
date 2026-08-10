@props(['invoice'])

@php
$statusStyles = [
    'paid' => 'app-badge-success',
    'unpaid' => 'app-badge-warning',
    'overdue' => 'app-badge-danger',
    'draft' => 'app-badge-neutral',
    'cancelled' => 'app-badge-neutral',
];
$isPaid = $invoice->status->value === 'paid';
$dateLabel = $isPaid
    ? ($invoice->paid_at?->format('d M Y') ?? $invoice->due_date?->format('d M Y'))
    : ($invoice->due_date?->format('d M Y') ?? '-');
@endphp

<a href="{{ route('portal.invoices.show', $invoice) }}" class="app-card block transition-transform duration-150 hover:shadow-md active:scale-[0.98]">
    <div class="px-5 py-4">
        {{-- Header: bulan + status --}}
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[15px] font-bold text-slate-900 dark:text-white">{{ $invoice->billing_period }}</p>
                <p class="mt-1 truncate text-xs text-slate-400 dark:text-gray-500">
                    Invoice {{ $invoice->invoice_number }}
                </p>
            </div>
            <span class="{{ $statusStyles[$invoice->status->value] ?? 'app-badge-neutral' }} shrink-0">
                {{ $invoice->status_label }}
            </span>
        </div>

        {{-- Divider tipis --}}
        <div class="mt-4 border-t border-dashed border-slate-200 dark:border-gray-700"></div>

        {{-- Detail metode + nominal --}}
        <div class="mt-4 flex items-center justify-between gap-4">
            <div class="flex min-w-0 items-start gap-2.5">
                <span class="{{ $isPaid ? 'bg-green-50 dark:bg-green-900/30 text-[#22c55e]' : 'bg-slate-100 dark:bg-gray-700 text-slate-400' }} mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl">
                    @if($isPaid)
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[13px] font-semibold text-slate-700 dark:text-gray-200">
                        {{ $invoice->payment_method?->label() ?? 'Belum dibayar' }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                        {{ $dateLabel }}
                    </p>
                </div>
            </div>

            <div class="shrink-0 text-right">
                <p class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    @currency($invoice->amount)
                </p>
                @if($isPaid)
                    <p class="mt-0.5 flex items-center justify-end gap-1 text-xs font-semibold text-[#22c55e] dark:text-green-400">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Lunas
                    </p>
                @else
                    <p class="mt-0.5 text-xs font-medium text-slate-400 dark:text-gray-500">Belum lunas</p>
                @endif
            </div>
        </div>
    </div>
</a>