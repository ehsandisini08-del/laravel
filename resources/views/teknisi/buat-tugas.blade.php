<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Buat Tugas</h1>
                    <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/40 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                        Developer & Superadmin
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pembuatan dan pembagian tugas kerja teknisi lapangan</p>
            </div>
            <a href="{{ route('teknisi.repair-tasks.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        @if(session('error'))
            <x-alert variant="danger" dismissible class="mb-6">{{ session('error') }}</x-alert>
        @endif

        @if($errors->any())
            <x-alert variant="danger" dismissible class="mb-6">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('teknisi.repair-tasks.store') }}" class="space-y-6" x-data="repairTaskForm()">
            @csrf

            <x-card title="Pilih Customer">
                <div class="space-y-4">
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer <span class="text-red-500">*</span></label>
                        <select name="customer_id" id="customer_id" required x-model="customerId" @change="onCustomerChange" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" 
                                    data-name="{{ $customer->name }}"
                                    data-address="{{ $customer->address }}"
                                    data-phone="{{ $customer->phone }}"
                                    data-latitude="{{ $customer->latitude }}"
                                    data-longitude="{{ $customer->longitude }}"
                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} - {{ $customer->phone }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="customerId" x-cloak>
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 space-y-2">
                            <div class="flex items-start gap-2">
                                <svg class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Nama</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="customerName"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Alamat</p>
                                    <p class="text-sm text-gray-900 dark:text-white" x-text="customerAddress"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No Telepon</p>
                                    <p class="text-sm text-gray-900 dark:text-white" x-text="customerPhone"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2" x-show="customerLatitude && customerLongitude">
                                <svg class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Koordinat</p>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        <span x-text="customerLatitude"></span>, <span x-text="customerLongitude"></span>
                                        <a :href="'https://maps.google.com/?q=' + customerLatitude + ',' + customerLongitude" target="_blank" class="ml-2 text-blue-600 dark:text-blue-400 hover:underline text-xs">Buka Maps</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Keterangan Tugas">
                <div>
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi Tugas <span class="text-red-500">*</span></label>
                    <textarea name="keterangan" id="keterangan" rows="6" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: Internet pelanggan mati sejak kemarin sore. Sudah dicoba restart modem tapi tetap tidak connect. Mohon teknisi untuk cek di lokasi...">{{ old('keterangan') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jelaskan detail masalah atau pekerjaan yang harus dilakukan teknisi.</p>
                </div>
            </x-card>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('teknisi.repair-tasks.index') }}" class="app-btn-ghost px-4 py-2 text-sm">
                    Batal
                </a>
                <button type="submit" class="app-btn-primary px-6 py-2.5 text-sm">
                    Buat Tugas & Kirim Notifikasi
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function repairTaskForm() {
            return {
                customerId: @json(old('customer_id')),
                customerName: '',
                customerAddress: '',
                customerPhone: '',
                customerLatitude: '',
                customerLongitude: '',

                onCustomerChange(event) {
                    const selectedOption = event.target.options[event.target.selectedIndex];
                    
                    if (selectedOption.value) {
                        this.customerName = selectedOption.dataset.name;
                        this.customerAddress = selectedOption.dataset.address;
                        this.customerPhone = selectedOption.dataset.phone;
                        this.customerLatitude = selectedOption.dataset.latitude;
                        this.customerLongitude = selectedOption.dataset.longitude;
                    } else {
                        this.customerName = '';
                        this.customerAddress = '';
                        this.customerPhone = '';
                        this.customerLatitude = '';
                        this.customerLongitude = '';
                    }
                },

                init() {
                    if (this.customerId) {
                        const option = document.querySelector(`option[value="${this.customerId}"]`);
                        if (option) {
                            this.customerName = option.dataset.name;
                            this.customerAddress = option.dataset.address;
                            this.customerPhone = option.dataset.phone;
                            this.customerLatitude = option.dataset.latitude;
                            this.customerLongitude = option.dataset.longitude;
                        }
                    }
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>
