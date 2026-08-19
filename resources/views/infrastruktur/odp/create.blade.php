<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add ODP</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tambah Optical Distribution Point baru</p>
            </div>
            <a href="{{ route('infrastruktur.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        @if($errors->any())
            <x-alert variant="danger" dismissible class="mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('odps.store') }}" class="space-y-6">
            @csrf

            <x-card title="Informasi ODP">
                <div class="grid grid-cols-1 gap-6">
                    @include('infrastruktur.odp._form')
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('infrastruktur.index') }}" class="app-btn-ghost">Cancel</a>
                <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Simpan ODP</button>
            </div>
        </form>
    </div>
</x-admin-layout>