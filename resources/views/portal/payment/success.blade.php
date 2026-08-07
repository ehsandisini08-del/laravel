<x-portal-layout>
    <div class="max-w-lg mx-auto">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl p-8 text-center">
            @if($status === 'paid')
                <div class="mx-auto w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="h-9 w-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Pembayaran Berhasil</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Terima kasih. Pembayaran Anda telah kami terima.
                </p>
            @elseif($status === 'pending')
                <div class="mx-auto w-16 h-16 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="h-9 w-9 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Menunggu Konfirmasi</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Pembayaran telah diterima dan sedang menunggu konfirmasi. Status akan diperbarui otomatis.
                </p>
            @else
                <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="h-9 w-9 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Status Pembayaran</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Kami tidak dapat menemukan data pembayaran untuk referensi ini.
                </p>
            @endif

            @if($invoice)
                <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Nomor Invoice</dt>
                            <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Periode</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $invoice->billing_period }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Jumlah</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">@currency($invoice->amount)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                            <dd><x-badge variant="{{ $invoice->status_color }}">{{ $invoice->status_label }}</x-badge></dd>
                        </div>
                    </dl>
                </div>
            @endif

            <div class="mt-6 flex flex-col gap-3">
                @if($status === 'pending')
                    <button onclick="location.reload()" class="w-full px-4 py-2.5 bg-gray-600 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors">
                        Refresh Status
                    </button>
                @endif
                <a href="{{ route('portal.invoices.index') }}" class="w-full px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    Lihat Riwayat Tagihan
                </a>
                <a href="{{ route('portal.dashboard') }}" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-portal-layout>