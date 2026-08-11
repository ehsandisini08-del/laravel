<x-portal-layout>
    <header class="mb-6 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-blue-500 to-blue-600 text-3xl font-bold text-white shadow-[0_12px_24px_rgba(59,130,246,0.35)]">
            {{ strtoupper(substr($customer->name, 0, 1)) }}
        </div>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">{{ $customer->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Kode: {{ $customer->customer_code }}</p>
    </header>

    <!-- Informasi -->
    <section class="mt-2">
        <div class="app-section-title">
            <h2>Informasi</h2>
        </div>
        <x-app-card>
            <div class="divide-y divide-slate-100">
                <div class="flex items-center gap-4 py-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#2563eb]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-400">Nama</p>
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $customer->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 py-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#2563eb]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-400">Telepon</p>
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $customer->phone }}</p>
                    </div>
                </div>
            </div>
        </x-app-card>
    </section>

    <!-- Alamat -->
    <section class="mt-6">
        <div class="app-section-title">
            <h2>Alamat</h2>
        </div>
        <x-app-card>
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#2563eb]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </div>
                <div class="min-w-0">
                    @if($customer->address)
                        <p class="text-sm leading-relaxed text-slate-800">{{ $customer->address }}</p>
                    @else
                        <p class="text-sm text-slate-400">Belum ada alamat tercatat.</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-400">{{ $customer->area?->name ?? 'Area belum ditentukan' }}</p>
                </div>
            </div>
        </x-app-card>
    </section>

    <!-- Internet -->
    <section class="mt-6">
        <div class="app-section-title">
            <h2>Internet</h2>
        </div>
        <x-app-card>
            <div class="divide-y divide-slate-100">
                <div class="flex items-center justify-between py-3.5">
                    <div>
                        <p class="text-xs text-slate-400">Paket</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $customer->package?->name ?? '-' }}</p>
                    </div>
                    @if($customer->package)
                        <p class="text-sm font-extrabold text-[#2563eb]">@currency($customer->package->price)</p>
                    @endif
                </div>
                <div class="flex items-center justify-between py-3.5">
                    <div>
                        <p class="text-xs text-slate-400">Username PPPoE</p>
                        <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ $customer->ppp_username ?? '-' }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between py-3.5">
                    <div>
                        <p class="text-xs text-slate-400">Router</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $customer->router?->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between py-3.5">
                    <div>
                        <p class="text-xs text-slate-400">Jatuh tempo</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">Setiap tanggal {{ $customer->due_day }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between py-3.5">
                    <div>
                        <p class="text-xs text-slate-400">Jadwal isolir</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $customer->isolation_day ? 'Setiap tanggal '.$customer->isolation_day : '-' }}</p>
                    </div>
                </div>
            </div>
        </x-app-card>
    </section>

    <!-- Logout -->
    <section class="mt-8">
        <form method="POST" action="{{ route('portal.logout') }}" x-data @submit.prevent="async () => { if(await customConfirm('Apakah Anda yakin ingin keluar?', { confirmLabel: 'Ya, Keluar' })) $el.submit() }">
            @csrf
            <button type="submit" class="app-btn-danger-ghost w-full px-4 py-3 text-sm">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                Keluar dari Akun
            </button>
        </form>
    </section>
</x-portal-layout>