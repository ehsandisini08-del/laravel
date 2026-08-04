<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Import Customer</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Import data customer dari file Excel (.xlsx, .xls, .csv)</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('customers.import.template') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Template
                </a>
                <a href="{{ route('customers.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('warning')) <x-alert variant="warning" dismissible>{{ session('warning') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif

        @if(session('import_result'))
            <x-card title="Hasil Import">
                <div class="mb-4 flex items-center gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Berhasil</p>
                        <p class="text-2xl font-bold text-green-600">{{ session('import_result')['success'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Gagal</p>
                        <p class="text-2xl font-bold text-red-600">{{ count(session('import_result')['errors']) }}</p>
                    </div>
                </div>

                @if(session('import_result')['errors'])
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Baris</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pesan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach(session('import_result')['errors'] as $error)
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-mono text-gray-600">{{ $error['row'] }}</td>
                                        <td class="px-4 py-2 text-sm text-red-600">{{ $error['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-card title="Upload File Excel">
                    <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Excel</label>
                            <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <input type="checkbox" name="link_ppp_secret" value="1" checked class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Buat / Hubungkan PPP Secret ke MikroTik
                                <span class="block text-xs text-gray-500">Jika username PPP sudah ada di MikroTik, akan otomatis dihubungkan (tidak membuat duplikat).</span>
                            </span>
                        </label>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                Import Customer
                            </button>
                        </div>
                    </form>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Kolom Template">
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="font-semibold text-gray-900 dark:text-white">Kolom wajib:</li>
                        <li>- Nama</li>
                        <li>- Alamat</li>
                        <li>- No HP</li>
                        <li>- Area</li>
                        <li>- Router</li>
                        <li>- Paket</li>
                        <li>- PPP Username</li>
                        <li>- PPP Password</li>
                        <li>- Jatuh Tempo (1-31)</li>
                        <li class="mt-2 font-semibold text-gray-900 dark:text-white">Opsional:</li>
                        <li>- Tanggal Pasang (YYYY-MM-DD)</li>
                        <li>- Hari Isolir (1-31)</li>
                        <li>- Status (Active/Isolated/Suspended/Terminated)</li>
                        <li>- Catatan</li>
                    </ul>
                </x-card>

                <x-card title="Catatan">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Nilai Area, Router, dan Paket harus sesuai dengan data yang sudah ada di sistem. Gunakan kolom dropdown pada template untuk menghindari kesalahan. Kode customer dan password portal dibuat otomatis.
                    </p>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>
