<x-portal-layout>
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Tagihan</h1>
        <p class="mt-1 text-sm text-slate-500">Tagihan yang belum dibayar untuk akun Anda.</p>
    </header>

    @if($bills->isEmpty())
        <x-app-card>
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-green-50 text-[#22c55e]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-900">Tidak Ada Tagihan</h3>
                <p class="mt-1 max-w-xs text-sm text-slate-500">Semua tagihan Anda telah dibayar. Anda tidak memiliki tagihan aktif saat ini.</p>
                <a href="{{ route('portal.invoices.index') }}" class="app-btn-soft mt-5 px-5 py-2.5 text-sm">Lihat Riwayat Tagihan</a>
            </div>
        </x-app-card>
    @else
        <div class="space-y-4">
            @foreach($bills as $bill)
                <x-app-card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="app-label">{{ $bill->billing_period }}</p>
                            <p class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">@currency($bill->amount)</p>
                            <p class="mt-1 text-xs text-slate-400">Invoice {{ $bill->invoice_number }}</p>
                        </div>
                        <span class="{{ $bill->status->value === 'overdue' ? 'app-badge-danger' : 'app-badge-warning' }}">
                            {{ $bill->status_label }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <div>
                            <p class="text-xs text-slate-400">Jatuh tempo</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $bill->due_date?->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Paket</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $bill->package?->name ?? '-' }}</p>
                        </div>
                    </div>

                    @if($paymentProvider !== 'none')
                        <form method="POST" action="{{ route('portal.invoices.pay', $bill) }}" class="mt-4 flex gap-3">
                            @csrf
                            <a href="{{ route('portal.invoices.show', $bill) }}" class="app-btn-ghost flex-1 px-4 py-3 text-sm">Detail</a>
                            <button type="submit" class="app-btn-success flex-[2] px-4 py-3 text-sm">Bayar Sekarang</button>
                        </form>
                    @else
                        <div class="mt-4">
                            <a href="{{ route('portal.invoices.show', $bill) }}" class="app-btn-primary w-full px-4 py-3 text-sm">Detail Tagihan</a>
                        </div>
                    @endif
                </x-app-card>
            @endforeach
        </div>
    @endif
</x-portal-layout>