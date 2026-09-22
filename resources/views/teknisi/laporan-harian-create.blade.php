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

        <form method="POST" action="{{ route('teknisi.laporan-harian.store') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf

            <x-card title="Data Pelanggan">
                <div class="space-y-4">
                    <div>
                        <label for="nama_customer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_customer" name="nama_customer" value="{{ old('nama_customer') }}" required
                               class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="no_telp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomor Telepon <span class="text-red-500">*</span></label>
                            <input type="text" id="no_telp" name="no_telp" value="{{ old('no_telp') }}" required
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
                        <textarea id="alamat" name="alamat" rows="3" required
                                  class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('alamat') }}</textarea>
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
                </div>
            </x-card>

            <x-card title="Teknisi & Bukti">
                <div class="space-y-4">
                    <div>
                        <label for="taken_by_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teknisi <span class="text-red-500">*</span></label>
                        <select id="taken_by_user_id" name="taken_by_user_id" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Teknisi --</option>
                            @foreach($teknisiList as $teknisi)
                                <option value="{{ $teknisi->id }}" {{ old('taken_by_user_id') == $teknisi->id ? 'selected' : '' }}>
                                    {{ $teknisi->name }}
                                </option>
                            @endforeach
                        </select>
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
</x-admin-layout>
