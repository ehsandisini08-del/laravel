<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $category->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Detail kategori barang</p>
            </div>
            <a href="{{ route('gudang.kategori.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nama</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $category->name }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">Jumlah Barang</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $category->items_count }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                <p class="mt-1">
                    @if($category->is_active)
                        <x-badge variant="success">Aktif</x-badge>
                    @else
                        <x-badge variant="danger">Nonaktif</x-badge>
                    @endif
                </p>
            </x-card>
        </div>

        @if($category->description)
            <x-card title="Deskripsi">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $category->description }}</p>
            </x-card>
        @endif
    </div>
</x-admin-layout>