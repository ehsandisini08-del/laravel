<x-portal-layout>
    <header class="mb-6 flex items-center gap-3">
        <a href="{{ $invoice->isUnpaid() || $invoice->isOverdue() ? route('portal.bills') : route('portal.invoices.index') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div class="min-w-0">
            <h1 class="truncate text-xl font-extrabold tracking-tight text-slate-900">Invoice {{ $invoice->invoice_number }}</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $invoice->billing_period }}</p>
        </div>
        <span class="ml-auto {{ $invoice->status->value === 'paid' ? 'app-badge-success' : ($invoice->status->value === 'overdue' ? 'app-badge-danger' : ($invoice->status->value === 'unpaid' ? 'app-badge-warning' : 'app-badge-neutral')) }}">
            {{ $invoice->status_label }}
        </span>
    </header>

    <!-- Amount summary -->
    <section class="app-card overflow-hidden p-0">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-6 text-white">
            <p class="text-sm font-medium text-blue-100">Total Tagihan</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight">@currency($invoice->amount)</p>
            @if($invoice->due_date)
                <p class="mt-2 text-sm text-blue-100">Jatuh tempo {{ $invoice->due_date->format('d M Y') }}</p>
            @endif
        </div>
        <div class="grid grid-cols-3 gap-2 px-5 py-4">
            <div>
                <p class="text-xs text-slate-400">Periode</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $invoice->billing_period }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400">Paket</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $invoice->package?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400">Metode</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $invoice->payment_method?->label() ?? '-' }}</p>
            </div>
        </div>
    </section>

    <!-- Items -->
    <section class="mt-6">
        <div class="app-section-title">
            <h2>Rincian Tagihan</h2>
        </div>
        <x-app-card>
            <div class="divide-y divide-slate-100">
                @foreach($invoice->items as $item)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $item->description }}</p>
                            @if($item->qty > 1)
                                <p class="mt-0.5 text-xs text-slate-400">{{ $item->qty }} × @currency($item->price)</p>
                            @endif
                        </div>
                        <p class="text-sm font-semibold text-slate-900">@currency($item->subtotal)</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-4">
                <p class="text-sm font-semibold text-slate-500">Total</p>
                <p class="text-xl font-extrabold text-slate-900">@currency($invoice->amount)</p>
            </div>
        </x-app-card>
    </section>

    <!-- Payment history -->
    <section class="mt-6">
        <div class="app-section-title">
            <h2>Riwayat Pembayaran</h2>
        </div>
        <x-app-card>
            @if($invoice->payments->isEmpty())
                <p class="py-2 text-sm text-slate-500">Belum ada pembayaran untuk tagihan ini.</p>
            @else
                <div class="space-y-3">
                    @foreach($invoice->payments as $payment)
                        <div class="flex items-center justify-between rounded-2xl bg-green-50 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#22c55e]/15 text-[#16a34a]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $payment->method_label }}</p>
                                    <p class="text-xs text-slate-500">{{ $payment->paid_at?->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                            <p class="text-sm font-bold text-slate-900">@currency($payment->amount)</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-app-card>
    </section>

    <!-- Pay / status box -->
    <section class="mt-6">
        @if(in_array($invoice->status->value, ['unpaid', 'overdue']))
            @php
                $pendingPayment = $invoice->payments->first(fn ($p) => $p->status?->value === 'pending');
            @endphp

            @if($paymentProvider === 'none')
                <x-app-card>
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Pembayaran online belum tersedia</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">Silakan hubungi admin untuk melakukan pembayaran tagihan.</p>
                        </div>
                    </div>
                </x-app-card>
            @else
                <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}">
                    @csrf
                    <button type="submit" class="app-btn-success w-full px-5 py-4 text-base">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                        {{ $pendingPayment ? 'Lanjutkan Pembayaran' : 'Bayar Sekarang' }}
                    </button>
                </form>
                @if($pendingPayment)
                    <p class="mt-3 text-center text-xs text-amber-600">Pembayaran sedang diproses. Klik tombol di atas untuk melanjutkan pembayaran.</p>
                @endif
            @endif
        @elseif($invoice->status->value === 'paid')
            <x-app-card>
                <div class="flex flex-col items-center py-2 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-[#22c55e]/10 text-[#22c55e]">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="mt-3 text-base font-bold text-slate-900">Tagihan Lunas</p>
                    <p class="mt-1 text-sm text-slate-500">Terima kasih. Pembayaran Anda telah kami terima.</p>
                </div>
            </x-app-card>
        @endif
    </section>
</x-portal-layout>