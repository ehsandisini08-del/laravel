<x-portal-layout>
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Riwayat</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">Riwayat tagihan & pembayaran Anda.</p>
    </header>

    @if($invoices->isEmpty())
        <div class="app-card py-10">
            <div class="flex flex-col items-center justify-center px-6 text-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-blue-50 text-[#2563eb] dark:bg-blue-900/40 dark:text-blue-300">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <h3 class="mt-5 text-lg font-bold text-slate-900 dark:text-white">Belum Ada Riwayat</h3>
                <p class="mt-1.5 max-w-xs text-sm leading-relaxed text-slate-500 dark:text-gray-400">
                    Riwayat tagihan akan muncul di sini setelah admin membuat tagihan untuk akun Anda.
                </p>
                <a href="{{ route('portal.dashboard') }}" class="app-btn-soft mt-6 px-5 py-2.5 text-sm">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach($invoices as $invoice)
                <x-transaction-card :invoice="$invoice" />
            @endforeach
        </div>

        @if($invoices->hasPages())
            <div class="mt-6">
                {{ $invoices->links() }}
            </div>
        @endif
    @endif
</x-portal-layout>