<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $package->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Package details</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('packages.edit', $package) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
                <a href="{{ route('packages.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        <x-card title="Package Information">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $package->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Price</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $package->price_formatted }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                    <dd>@if($package->is_active) <x-badge variant="success">🟢 Active</x-badge> @else <x-badge variant="danger">🔴 Inactive</x-badge> @endif</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Router</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $package->router?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">PPP Profile</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $package->pppProfile?->name ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Coverage Areas">
            <div class="flex flex-wrap gap-2">
                @forelse($package->areas as $area)
                    <span class="px-3 py-1 text-sm font-medium bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded-full">{{ $area->name }} ({{ $area->code }})</span>
                @empty
                    <p class="text-sm text-gray-500">No areas assigned.</p>
                @endforelse
            </div>
        </x-card>

        @if($package->description)
            <x-card title="Description">
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $package->description }}</p>
            </x-card>
        @endif

        <x-card title="Timestamps">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $package->created_at->diffForHumans() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Updated</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $package->updated_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-admin-layout>
