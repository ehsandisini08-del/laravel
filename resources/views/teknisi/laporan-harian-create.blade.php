<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tambah Laporan Harian</h1>
                    <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/40 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                        Teknisi
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat laporan harian perbaikan secara manual</p>
            </div>
            <a href="{{ route('teknisi.laporan-harian') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        @if(session('success'))
            <x-alert variant="success" dismissible class="mb-6">{{ session('success') }}</x-alert>
        @endif

        @if(session('error'))
            <x-alert variant="danger" dismissible class="mb-6">{{ session('error') }}</x-alert>
        @endif

        @if($errors->any())
            <x-alert variant="danger" dismissible class="mb-6">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('teknisi.laporan-harian.store') }}" class="space-y-6" enctype="multipart/form-data" x-data="laporanCustomerPicker()">
            @csrf

            <x-card title="Data Pelanggan">
                <div class="space-y-4">
                    <div>
                        <label for="nama_customer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" id="nama_customer" name="nama_customer" x-model="search" x-ref="searchInput"
                                   @input="onSearchInput" @focus="open = true" @click.outside="open = false"
                                   placeholder="Ketik nama pelanggan untuk mencari…" required autocomplete="off"
                                   class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                                </svg>
                            </div>

                            <div x-show="open && filteredCustomers.length" x-cloak x-transition
                                 class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg max-h-64 overflow-y-auto">
                                <template x-for="customer in filteredCustomers" :key="customer.id">
                                    <button type="button" @click="selectCustomer(customer)"
                                            class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors">
                                        <span class="flex min-w-0 flex-col">
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="customer.name"></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="customer.customer_code"></span>
                                        </span>
                                        <span class="max-w-[200px] truncate text-xs text-gray-500 dark:text-gray-400" x-text="customer.address"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="no_telp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomor Telepon <span class="text-red-500">*</span></label>
                            <input type="text" id="no_telp" name="no_telp" x-model="phone" required
                                   class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="completed_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                            <input type="date" id="completed_at" name="completed_at" value="{{ old('completed_at', now()->toDateString()) }}" required
                                   class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label for="alamat" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alamat <span class="text-red-500">*</span></label>
                        <textarea id="alamat" name="alamat" rows="3" x-model="address" required
                                  class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </x-card>

            <x-card title="Data Kendala & Penyelesaian">
                <div class="space-y-4">
                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kendala <span class="text-red-500">*</span></label>
                        <textarea id="keterangan" name="keterangan" rows="3" required
                                  class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('keterangan') }}</textarea>
                    </div>
                    <div>
                        <label for="keterangan_teknisi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan Penyelesaian <span class="text-red-500">*</span></label>
                        <textarea id="keterangan_teknisi" name="keterangan_teknisi" rows="3" required
                                  class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('keterangan_teknisi') }}</textarea>
                    </div>
                    <div>
                        <label for="parts_used" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Penggunaan Part <span class="text-gray-400">(opsional)</span></label>
                        <textarea id="parts_used" name="parts_used" rows="3"
                                  placeholder="Contoh: Kabel FO 20m, Konektor SC 2 pcs, Patchcord…"
                                  class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('parts_used') }}</textarea>
                    </div>
                </div>
            </x-card>

            <x-card title="Teknisi & Bukti">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teknisi <span class="text-red-500">*</span></label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pilih satu atau lebih teknisi yang menangani</p>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach($teknisiList as $teknisi)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <input type="checkbox" name="technician_ids[]" value="{{ $teknisi->id }}"
                                           @checked(in_array($teknisi->id, old('technician_ids', [])))
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $teknisi->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label for="foto_bukti" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto Bukti <span class="text-gray-400">(opsional)</span></label>
                        <input type="file" id="foto_bukti" name="foto_bukti" accept="image/*"
                               class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-400">Format: JPG, PNG, max 2MB</p>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center gap-3">
                <button type="submit" class="app-btn-primary flex items-center gap-2 px-6 py-2.5 text-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Simpan Laporan
                </button>
                <a href="{{ route('teknisi.laporan-harian') }}" class="app-btn-ghost px-6 py-2.5 text-sm">
                    Batal
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function laporanCustomerPicker() {
            return {
                customers: @json($customers),
                search: @json(old('nama_customer', '')),
                phone: @json(old('no_telp', '')),
                address: @json(old('alamat', '')),
                open: false,

                get filteredCustomers() {
                    const q = this.search.trim().toLowerCase();
                    if (!q) {
                        return this.customers.slice(0, 20);
                    }
                    return this.customers.filter((c) => {
                        const name = (c.name || '').toLowerCase();
                        const code = (c.customer_code || '').toLowerCase();
                        const phone = (c.phone || '').toLowerCase();
                        const address = (c.address || '').toLowerCase();
                        return name.includes(q) || code.includes(q) || phone.includes(q) || address.includes(q);
                    }).slice(0, 20);
                },

                onSearchInput() {
                    this.open = true;
                },

                selectCustomer(customer) {
                    this.search = customer.name;
                    this.phone = customer.phone;
                    this.address = customer.address;
                    this.open = false;
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>
