<x-portal-layout>
    @php
        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
        $serviceLabel = match ($customer->service_status?->value) {
            'active' => 'Aktif',
            'overdue' => 'Tunggakan',
            'isolated' => 'Isolir',
            default => 'Aktif',
        };
        $latestBill = $activeBills->first();
    @endphp

    {{-- Greeting header --}}
    <header class="mb-6 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">{{ $greeting }},</p>
            <h1 class="truncate text-2xl font-extrabold tracking-tight text-slate-900">{{ $customer->name }}</h1>
        </div>
        <a href="{{ route('portal.account') }}" class="shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-lg font-bold text-white shadow-[0_8px_16px_rgba(59,130,246,0.35)]">
                {{ strtoupper(substr($customer->name, 0, 1)) }}
            </div>
        </a>
    </header>

    <!-- Status Layanan hero card -->
    <section class="app-card overflow-hidden p-0">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-6 text-white">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-blue-100">Status Layanan</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            @if($customer->service_status?->value === 'active')
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-300 opacity-75"></span>
                            @endif
                            <span class="relative inline-flex h-3 w-3 rounded-full {{ $customer->service_status?->value === 'active' ? 'bg-green-400' : ($customer->service_status?->value === 'isolated' ? 'bg-red-400' : 'bg-amber-400') }}"></span>
                        </span>
                        <span class="text-xl font-bold">Layanan {{ $serviceLabel }}</span>
                    </div>
                    <p class="mt-3 text-sm text-blue-100">{{ $customer->area?->name ?? 'Area tidak diketahui' }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 p-3 backdrop-blur-sm">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-5 text-xs text-blue-200">Kode: {{ $customer->customer_code }}</p>
        </div>
        <div class="flex items-center justify-between px-6 py-4">
            <div>
                <p class="text-xs font-medium text-slate-400">Jatuh tempo tagihan</p>
                <p class="mt-0.5 text-base font-bold text-slate-900">Tgl {{ $customer->due_day }}</p>
            </div>
            <a href="{{ route('portal.bills') }}" class="app-btn bg-blue-50 text-[#2563eb] px-4 py-2 text-xs">
                Lihat Tagihan
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
            </a>
        </div>
    </section>

    <!-- Package card -->
    <section class="mt-5">
        <div class="app-section-title">
            <h2>Paket Internet</h2>
        </div>
        <x-app-card>
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[#2563eb]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-slate-900">{{ $customer->package?->name ?? 'Belum ada paket' }}</p>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $customer->ppp_username ? 'PPPoE: '.$customer->ppp_username : 'Belum dikonfigurasi' }}</p>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-lg font-extrabold text-[#2563eb]">@currency($customer->package?->price ?? 0)</p>
                    <p class="text-xs text-slate-400">/bulan</p>
                </div>
            </div>
        </x-app-card>
    </section>

    <!-- Active bill / tagihan card -->
    <section class="mt-5">
        <div class="app-section-title">
            <h2>Tagihan</h2>
            @if($latestBill)
                <a href="{{ route('portal.bills') }}" class="text-xs font-semibold text-[#2563eb]">Lihat semua</a>
            @endif
        </div>

        @if($latestBill)
            <x-app-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="app-label">{{ $latestBill->billing_period }}</p>
                        <p class="mt-1.5 text-2xl font-extrabold tracking-tight text-slate-900">@currency($latestBill->amount)</p>
                        <p class="mt-1 text-xs text-slate-400">Jatuh tempo {{ $latestBill->due_date?->format('d M Y') }}</p>
                    </div>
                    <span class="{{ $latestBill->status->value === 'overdue' ? 'app-badge-danger' : 'app-badge-warning' }}">
                        {{ $latestBill->status_label }}
                    </span>
                </div>
                @if(in_array($latestBill->status->value, ['unpaid', 'overdue']))
                    <div class="mt-5">
                        @if($paymentProvider !== 'none')
                            <form method="POST" action="{{ route('portal.invoices.pay', $latestBill) }}">
                                @csrf
                                <button type="submit" class="app-btn-success w-full">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                                    Bayar Sekarang
                                </button>
                            </form>
                        @else
                            <button type="button" onclick="showToast('Pembayaran online belum tersedia. Silakan hubungi admin.', 'info')" class="app-btn-primary w-full">
                                Hubungi Admin untuk Bayar
                            </button>
                        @endif
                    </div>
                @endif
            </x-app-card>
        @else
            <x-app-card>
                <div class="flex flex-col items-center justify-center px-6 py-10 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-green-50 text-[#22c55e]">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900">Semua Tagihan Lunas</h3>
                    <p class="mt-1 max-w-xs text-sm text-slate-500">Tidak ada tagihan aktif. Tagihan baru akan muncul di sini setiap periode.</p>
                    <a href="{{ route('portal.invoices.index') }}" class="app-btn-soft mt-5 px-5 py-2.5 text-sm">Lihat Riwayat</a>
                </div>
            </x-app-card>
        @endif
    </section>

    <!-- Quick actions -->
    <section class="mt-7 grid grid-cols-2 gap-3">
        <a href="{{ $latestBill ? route('portal.invoices.show', $latestBill) : route('portal.bills') }}" class="app-card flex flex-col items-center justify-center gap-2 px-4 py-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $latestBill ? ($latestBill->status->value === 'overdue' ? 'bg-red-50 text-red-500' : 'bg-green-50 text-[#22c55e]') : 'bg-slate-100 text-slate-400' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="text-sm font-semibold text-slate-700">Bayar Tagihan</span>
        </a>
        <a href="{{ route('portal.invoices.index') }}" class="app-card flex flex-col items-center justify-center gap-2 px-4 py-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-[#2563eb]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                </svg>
            </div>
            <span class="text-sm font-semibold text-slate-700">Lihat Riwayat</span>
        </a>
    </section>
</x-portal-layout>