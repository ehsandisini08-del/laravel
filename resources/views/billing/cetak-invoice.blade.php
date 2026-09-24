<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cetak Invoice</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat PDF invoice per pelanggan dalam rentang bulan tertentu.</p>
        </div>
    </x-slot>

    <div x-data="cetakInvoiceApp()" class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif

        <x-card>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Buat PDF Invoice</h2>
                <p class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Pilih pelanggan, tentukan rentang bulan, lalu hasilkan semua invoice dalam satu file PDF.
                </p>
                <button type="button" @click="open = true"
                        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                    </svg>
                    Cetak Invoice
                </button>
            </div>
        </x-card>

        {{-- Modal --}}
        <div x-show="open" x-cloak x-transition.opacity
             class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm"
             @click.self="open = false">
            <div class="relative my-8 w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-800" x-transition>
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Cetak Invoice</h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    {{-- Pelanggan --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Pelanggan <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" x-model="search" x-ref="searchInput"
                                   @input="onSearchInput" @focus="customerOpen = true" @click.outside="customerOpen = false"
                                   placeholder="Ketik nama pelanggan untuk mencari…" autocomplete="off"
                                   class="block w-full rounded-lg border-gray-300 pr-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                            </div>

                            <div x-show="customerOpen && filteredCustomers.length" x-cloak x-transition
                                 class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                <template x-for="c in filteredCustomers" :key="c.id">
                                    <button type="button" @click="selectCustomer(c)"
                                            class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm hover:bg-blue-50 dark:hover:bg-gray-700">
                                        <span class="flex min-w-0 flex-col">
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="c.name"></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="c.customer_code"></span>
                                        </span>
                                        <span class="max-w-[200px] truncate text-xs text-gray-500 dark:text-gray-400" x-text="c.address"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="selectedCustomer" x-cloak class="mt-3 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-700/50">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <p><span class="text-gray-500">Nama:</span> <span class="font-medium text-gray-900 dark:text-white" x-text="selectedCustomer.name"></span></p>
                                <p><span class="text-gray-500">Kode:</span> <span class="font-medium text-gray-900 dark:text-white" x-text="selectedCustomer.customer_code"></span></p>
                                <p><span class="text-gray-500">Telepon:</span> <span class="font-medium text-gray-900 dark:text-white" x-text="selectedCustomer.phone"></span></p>
                                <p><span class="text-gray-500">Alamat:</span> <span class="font-medium text-gray-900 dark:text-white" x-text="selectedCustomer.address"></span></p>
                            </div>
                        </div>
                    </div>

                    {{-- Rentang bulan --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Rentang Bulan <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <p class="mb-1 text-xs text-gray-500">Dari</p>
                                <div class="flex gap-2">
                                    <select x-model.number="fromMonth" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <template x-for="(m, i) in months" :key="'fm'+i">
                                            <option :value="i + 1" x-text="m"></option>
                                        </template>
                                    </select>
                                    <select x-model.number="fromYear" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <template x-for="y in years" :key="'fy'+y">
                                            <option :value="y" x-text="y"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <p class="mb-1 text-xs text-gray-500">Sampai</p>
                                <div class="flex gap-2">
                                    <select x-model.number="toMonth" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <template x-for="(m, i) in months" :key="'tm'+i">
                                            <option :value="i + 1" x-text="m"></option>
                                        </template>
                                    </select>
                                    <select x-model.number="toYear" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <template x-for="y in years" :key="'ty'+y">
                                            <option :value="y" x-text="y"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select x-model="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Semua Status</option>
                            <option value="unpaid">Belum Bayar</option>
                            <option value="overdue">Telat Bayar</option>
                            <option value="paid">Sudah Bayar</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>

                    {{-- Preview --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pratinjau</label>
                            <button type="button" @click="loadPreview"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Muat Ulang
                            </button>
                        </div>

                        <div x-show="loading" class="mt-2 py-4 text-center text-sm text-gray-400">Memuat…</div>
                        <div x-show="!loading && preview.count !== null" x-cloak class="mt-2">
                            <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                                Ditemukan <span class="font-semibold text-blue-600" x-text="preview.count"></span> invoice.
                            </p>
                            <div x-show="preview.invoices.length" class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                <template x-for="inv in preview.invoices" :key="inv.id">
                                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5 text-sm last:border-b-0 dark:border-gray-700">
                                        <div>
                                            <p class="font-mono text-xs font-medium text-gray-900 dark:text-white" x-text="inv.invoice_number"></p>
                                            <p class="text-xs text-gray-500" x-text="inv.billing_period"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="'Rp ' + inv.amount"></p>
                                            <p class="text-xs" :class="inv.status === 'Lunas' ? 'text-green-600' : 'text-amber-600'" x-text="inv.status"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="!preview.invoices.length" class="rounded-lg bg-gray-50 px-4 py-6 text-center text-sm text-gray-500 dark:bg-gray-700/50">
                                Tidak ada invoice pada rentang bulan ini.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button" @click="open = false" class="app-btn-ghost px-4 py-2 text-sm">Batal</button>
                    <button type="button" @click="printPdf" :disabled="!selectedCustomer"
                            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-green-600/30 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z"/></svg>
                        Cetak PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function cetakInvoiceApp() {
            return {
                open: false,
                customers: @json($customers),
                search: '',
                customerOpen: false,
                selectedCustomer: null,
                fromMonth: @json((int) now()->month),
                fromYear: @json((int) now()->year),
                toMonth: @json((int) now()->month),
                toYear: @json((int) now()->year),
                status: '',
                loading: false,
                preview: { count: null, invoices: [] },
                months: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                years: [@json((int) now()->year) - 1, @json((int) now()->year), @json((int) now()->year) + 1],

                get filteredCustomers() {
                    const q = this.search.trim().toLowerCase();
                    if (!q) return this.customers.slice(0, 20);
                    return this.customers.filter((c) => {
                        const name = (c.name || '').toLowerCase();
                        const code = (c.customer_code || '').toLowerCase();
                        const phone = (c.phone || '').toLowerCase();
                        return name.includes(q) || code.includes(q) || phone.includes(q);
                    }).slice(0, 20);
                },

                onSearchInput() {
                    this.customerOpen = true;
                },

                selectCustomer(c) {
                    this.selectedCustomer = c;
                    this.search = c.name;
                    this.customerOpen = false;
                    this.loadPreview();
                },

                buildQuery() {
                    return new URLSearchParams({
                        customer_id: this.selectedCustomer?.id ?? '',
                        from_month: this.fromMonth,
                        from_year: this.fromYear,
                        to_month: this.toMonth,
                        to_year: this.toYear,
                        status: this.status,
                    }).toString();
                },

                async loadPreview() {
                    if (!this.selectedCustomer) return;
                    this.loading = true;
                    try {
                        const res = await fetch("{{ route('billing.cetak-invoice.preview') }}?" + this.buildQuery(), {
                            headers: { 'Accept': 'application/json' }
                        });
                        this.preview = await res.json();
                    } catch (e) {
                        this.preview = { count: 0, invoices: [] };
                    } finally {
                        this.loading = false;
                    }
                },

                printPdf() {
                    if (!this.selectedCustomer) return;
                    window.open("{{ route('billing.cetak-invoice.pdf') }}?" + this.buildQuery(), '_blank');
                },
            }
        }
    </script>
    @endpush
</x-admin-layout>
