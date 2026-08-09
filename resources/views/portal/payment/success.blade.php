<x-portal-layout>
    <div class="flex min-h-[70vh] flex-col justify-center">
        <x-app-card>
            <div class="flex flex-col items-center text-center">
                @if($status === 'paid')
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-[#22c55e]/10 text-[#22c55e]">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-slate-900">Pembayaran Berhasil</h1>
                    <p class="mt-2 max-w-xs text-center text-sm text-slate-500">
                        Terima kasih. Pembayaran Anda telah kami terima.
                    </p>
                @elseif($status === 'pending')
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-500">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-slate-900">Menunggu Konfirmasi</h1>
                    <p class="mt-2 max-w-xs text-center text-sm text-slate-500">
                        Pembayaran telah diterima dan sedang menunggu konfirmasi. Status akan diperbarui otomatis.
                    </p>
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-slate-900">Status Pembayaran</h1>
                    <p class="mt-2 max-w-xs text-center text-sm text-slate-500">
                        Kami tidak dapat menemukan data pembayaran untuk referensi ini.
                    </p>
                @endif

                @if($invoice)
                    <div class="mt-6 w-full rounded-2xl bg-slate-50 px-5 py-4">
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Nomor Invoice</dt>
                                <dd class="font-mono font-medium text-slate-900">{{ $invoice->invoice_number }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Periode</dt>
                                <dd class="font-medium text-slate-900">{{ $invoice->billing_period }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Jumlah</dt>
                                <dd class="font-bold text-slate-900">@currency($invoice->amount)</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Status</dt>
                                <dd><x-badge variant="{{ $invoice->status_color }}">{{ $invoice->status_label }}</x-badge></dd>
                            </div>
                        </dl>
                    </div>
                @endif

                <div class="mt-6 w-full space-y-3">
                    @if($status === 'pending')
                        <button onclick="location.reload()" class="app-btn-soft w-full py-3 text-sm">
                            Refresh Status
                        </button>
                    @endif
                    <a href="{{ route('portal.dashboard') }}" class="app-btn-primary w-full py-3.5 text-sm">Kembali ke Beranda</a>
                    <a href="{{ route('portal.invoices.index') }}" class="app-btn-ghost w-full py-3 text-sm">Lihat Riwayat Tagihan</a>
                </div>
            </div>
        </x-app-card>
    </div>
</x-portal-layout>