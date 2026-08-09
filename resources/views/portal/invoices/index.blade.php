<x-portal-layout>
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Riwayat</h1>
        <p class="mt-1 text-sm text-slate-500">Seluruh riwayat tagihan Anda.</p>
    </header>

    @if($invoices->isEmpty())
        <x-app-card>
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-900">Belum Ada Riwayat</h3>
                <p class="mt-1 max-w-xs text-sm text-slate-500">Riwayat tagihan akan muncul di sini setelah admin membuat tagihan untuk akun Anda.</p>
            </div>
        </x-app-card>
    @else
        <div class="space-y-3">
            @foreach($invoices as $invoice)
                <a href="{{ route('portal.invoices.show', $invoice) }}" class="app-card block transition-transform active:scale-[0.98]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $invoice->billing_period }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Invoice {{ $invoice->invoice_number }}</p>
                        </div>
                        <span class="{{ match ($invoice->status->value) {
                            'paid' => 'app-badge-success',
                            'overdue' => 'app-badge-danger',
                            'unpaid' => 'app-badge-warning',
                            default => 'app-badge-neutral',
                        } }}">
                            {{ $invoice->status_label }}
                        </span>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                        <div>
                            <p class="text-xs text-slate-400">{{ $invoice->payment_method?->label() ?? 'Belum dibayar' }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-extrabold text-slate-900">@currency($invoice->amount)</p>
                            @if($invoice->status->value === 'paid')
                                <p class="mt-0.5 text-xs font-medium text-[#22c55e]">Lunas</p>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if($invoices->hasPages())
            <div class="mt-6">
                {{ $invoices->links() }}
            </div>
        @endif
    @endif
</x-portal-layout>