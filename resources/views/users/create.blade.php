<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tambah User</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat akun pengguna baru</p>
            </div>
            <a href="{{ route('users.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        @if($errors->any())
            <x-alert variant="danger" dismissible class="mb-6">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('users.store') }}" class="space-y-6" x-data="{ role: @js(old('role', '')) }">
            @csrf

            <x-card title="Data Akun">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password" required minlength="8" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimal 8 karakter.</p>
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
                        <select name="role" id="role" x-model="role" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(\App\Models\User::roles() as $value => $label)
                                <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-card>

            <x-card title="Area" x-show="role === @js(\App\Models\User::ROLE_ADMIN_AREA)">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Pilih satu atau lebih area yang dikelola oleh Admin Area ini.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($areas as $area)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input type="checkbox" name="areas[]" value="{{ $area->id }}" {{ in_array($area->id, old('areas', [])) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $area->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $area->code }}</span>
                        </label>
                    @endforeach
                </div>
                @if($areas->isEmpty())
                    <p class="text-sm text-gray-500">Belum ada area. Buat area terlebih dahulu sebelum membuat user Admin Area.</p>
                @endif
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('users.index') }}" class="app-btn-ghost px-4 py-2 text-sm">Batal</a>
                <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
