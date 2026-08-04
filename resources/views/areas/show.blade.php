<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $area->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Area details and usage</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('areas.edit', $area) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
                <a href="{{ route('areas.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        <x-card title="Area Information">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Code</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        <span class="px-2 py-1 text-xs font-mono font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">{{ $area->code }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $area->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                    <dd>
                        @if($area->is_active)
                            <x-badge variant="success">🟢 Active</x-badge>
                        @else
                            <x-badge variant="danger">🔴 Inactive</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $area->created_at->diffForHumans() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Updated</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $area->updated_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </x-card>

        @if($area->description)
            <x-card title="Description">
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $area->description }}</p>
            </x-card>
        @endif

        <x-card title="Usage">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Packages</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">0</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Customers</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">0</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-admin-layout>
