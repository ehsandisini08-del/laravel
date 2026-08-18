<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Packages</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage internet service packages</p>
            </div>
            @if(!Auth::user()->isAdminArea())
            <a href="{{ route('packages.create') }}" class="app-btn-primary px-4 py-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Package
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
            <form method="GET" class="flex flex-col gap-3 md:flex-row" x-data x-ref="filterForm">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search package name..." class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <select name="router_id" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Routers</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                    @endforeach
                </select>
                <select name="area_id" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Areas</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($packages->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Packages</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new internet package.</p>
                    @if(!Auth::user()->isAdminArea())
                    <div class="mt-6">
                        <a href="{{ route('packages.create') }}" class="app-btn-primary px-4 py-2">Add Package</a>
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
                                <th class="text-left">Name</th>
                                <th class="text-left">Price</th>
                                <th class="text-left">Router</th>
                                <th class="text-left">Profile</th>
                                <th class="text-left">Areas</th>
                                <th class="text-left">Status</th>
                                @if(!Auth::user()->isAdminArea())
                                <th class="text-right">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($packages as $package)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('packages.show', $package) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $package->name }}</a>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $package->price_formatted }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $package->router?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $package->pppProfile?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            @foreach($package->areas->take(3) as $area)
                                                <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded">{{ $area->code }}</span>
                                            @endforeach
                                            @if($package->areas->count() > 3)
                                                <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded cursor-default" title="{{ $package->areas->slice(3)->pluck('name')->implode(', ') }}">+{{ $package->areas->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if($package->is_active)
                                            <x-badge variant="success">🟢 Active</x-badge>
                                        @else
                                            <x-badge variant="danger">🔴 Inactive</x-badge>
                                        @endif
                                    </td>
                                    @if(!Auth::user()->isAdminArea())
                                    <td class="whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('packages.edit', $package) }}" class="icon-btn" title="Edit">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('packages.destroy', $package) }}" x-data @submit.prevent="async () => { if(await customConfirm('Apakah Anda yakin ingin menghapus package &quot;{{ $package->name }}&quot;?')) $el.submit() }" class="inline">
                                                @csrf @method('DELETE')
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
            <div class="mt-4">{{ $packages->links() }}</div>
        @endif
    </div>
</x-admin-layout>
