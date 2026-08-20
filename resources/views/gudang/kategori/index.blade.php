<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kategori Barang</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelompok barang di gudang</p>
            </div>
            <a href="{{ route('gudang.kategori.create') }}" class="app-btn-primary px-4 py-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Kategori
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <x-card>
            <form method="GET" action="{{ route('gudang.kategori.index') }}" class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kategori..." class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($categories->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Kategori</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tambahkan kategori untuk mengelompokkan barang.</p>
                    <div class="mt-6">
                        <a href="{{ route('gudang.kategori.create') }}" class="app-btn-primary px-4 py-2">Tambah Kategori</a>
                    </div>
                </div>
            </x-card>
        @else
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Nama</th>
                                <th class="text-left">Deskripsi</th>
                                <th class="text-right">Jumlah Barang</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('gudang.kategori.show', $category) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $category->name }}</a>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $category->description ?? '—' }}</td>
                                    <td class="whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">{{ $category->items_count }}</td>
                                    <td class="whitespace-nowrap">
                                        @if($category->is_active)
                                            <x-badge variant="success">Aktif</x-badge>
                                        @else
                                            <x-badge variant="danger">Nonaktif</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('gudang.kategori.edit', $category) }}" class="icon-btn" title="Edit">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('gudang.kategori.destroy', $category) }}" x-data @submit.prevent="async () => { if(await customConfirm('Apakah Anda yakin ingin menghapus kategori &quot;{{ $category->name }}&quot;?')) $el.submit() }" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn-danger" title="Delete">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $categories->links() }}</div>
        @endif
    </div>
</x-admin-layout>