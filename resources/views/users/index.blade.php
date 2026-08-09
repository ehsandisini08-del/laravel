<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen User</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola pengguna sistem dan role</p>
            </div>
            <a href="{{ route('users.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah User
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif

        <x-card>
            <form method="GET" class="flex flex-col md:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <select name="role" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Role</option>
                    @foreach(\App\Models\User::roles() as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($users->isEmpty())
            <x-card><div class="text-center py-12"><p class="text-gray-500">Tidak ada user.</p></div></x-card>
        @else
            <x-card>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Nama</th>
                                <th class="text-left">Email</th>
                                <th class="text-left">Role</th>
                                <th class="text-left">Terdaftar</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($users as $user)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
                                            @if($user->id === Auth::id())
                                                <span class="text-xs text-gray-400">(Anda)</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                                    <td class="px-4 py-3"><x-badge variant="{{ $user->roleColor() }}">{{ $user->roleLabel() }}</x-badge></td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $user->created_at?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                                            @if($user->id !== Auth::id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" x-data="{ confirmed: false }" @submit.prevent="if(!confirmed) { $dispatch('open-confirm', { message: 'Hapus user {{ $user->name }}?', callback: () => { confirmed = true; $el.submit(); } }); }">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800">Hapus</button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $users->links() }}</div>
            </x-card>
        @endif
    </div>
</x-admin-layout>
