<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Areas</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage service coverage areas</p>
            </div>
            @if(!Auth::user()->isAdminArea())
            <a href="{{ route('areas.create') }}" class="app-btn-primary px-4 py-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Area
            </a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('success')) showToast('{{ session('success') }}', 'success'); @endif
                @if(session('error')) showToast('{{ session('error') }}', 'error'); @endif
            });
        </script>

        <x-card>
            <form method="GET" action="{{ route('areas.index') }}" class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by code or name..." class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($areas->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Areas</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new service area.</p>
                    @if(!Auth::user()->isAdminArea())
                    <div class="mt-6">
                        <a href="{{ route('areas.create') }}" class="app-btn-primary px-4 py-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Area
                        </a>
                    </div>
                    @endif
                </div>
            </x-card>
        @else
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Code</th>
                                <th class="text-left">Name</th>
                                <th class="text-left">Status</th>
                                <th class="text-left">Created</th>
                                @if(!Auth::user()->isAdminArea())
                                <th class="text-right">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($areas as $area)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-mono font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">{{ $area->code }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('areas.show', $area) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $area->name }}</a>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if($area->is_active)
                                            <x-badge variant="success">🟢 Active</x-badge>
                                        @else
                                            <x-badge variant="danger">🔴 Inactive</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $area->created_at->diffForHumans() }}</td>
                                    @if(!Auth::user()->isAdminArea())
                                    <td class="whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('areas.edit', $area) }}" class="icon-btn" title="Edit">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('areas.destroy', $area) }}" x-data @submit.prevent="async () => { if(await customConfirm('Apakah Anda yakin ingin menghapus Area &quot;{{ $area->name }}&quot;?')) $el.submit() }" class="inline">
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
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $areas->links() }}</div>
        @endif
    </div>
</x-admin-layout>
