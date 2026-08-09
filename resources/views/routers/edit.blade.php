<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Edit Router</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update router configuration</p>
            </div>
            <a href="{{ route('routers.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
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

        <form method="POST" action="{{ route('routers.update', $router) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card title="Router Information">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $router->name) }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">A friendly name to identify this router</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $router->description) }}</textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional description or notes about this router</p>
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                        <input type="text" name="location" id="location" value="{{ old('location', $router->location) }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Physical location of the router</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Connection Settings">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Host / IP Address <span class="text-red-500">*</span></label>
                        <input type="text" name="host" id="host" value="{{ old('host', $router->host) }}" required placeholder="192.168.88.1" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">IP address or hostname of the router</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="api_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Port <span class="text-red-500">*</span></label>
                            <input type="number" name="api_port" id="api_port" value="{{ old('api_port', $router->api_port) }}" required min="1" max="65535" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Default: 8728</p>
                        </div>

                        <div>
                            <label for="api_ssl" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SSL Connection</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="api_ssl" id="api_ssl" value="1" {{ old('api_ssl', $router->api_ssl) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Enable SSL/TLS</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use port 8729 for SSL</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="username" value="{{ old('username', $router->username) }}" required autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">RouterOS API username</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <input type="password" name="password" id="password" autocomplete="new-password" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Leave blank to keep current password</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Router Settings">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="enabled" value="1" {{ old('enabled', $router->enabled) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable Router</span>
                        </label>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only enabled routers will be used by the system</p>
                    </div>

                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_default" value="1" {{ old('is_default', $router->is_default) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Set as Default Router</span>
                        </label>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The default router will be used when no specific router is selected</p>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                        <input type="number" name="priority" id="priority" value="{{ old('priority', $router->priority) }}" min="0" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Higher priority routers are used first (0 = lowest)</p>
                    </div>
                </div>
            </x-card>

            @if($router->identity)
                <x-card title="Router Information">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Identity:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $router->identity }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Version:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $router->routeros_version }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Board:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $router->board_name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Architecture:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $router->architecture }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Status:</span>
                            <span class="ml-2">
                                @if($router->status === 'online')
                                    <x-badge variant="success">🟢 Online</x-badge>
                                @elseif($router->status === 'checking')
                                    <x-badge variant="warning">🟡 Checking...</x-badge>
                                @else
                                    <x-badge variant="danger">🔴 Offline</x-badge>
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Last Seen:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $router->last_seen_at ? $router->last_seen_at->diffForHumans() : 'Never' }}</span>
                        </div>
                    </div>
                </x-card>
            @endif

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('routers.index') }}" class="app-btn-ghost px-4 py-2 text-sm">
                    Cancel
                </a>
                <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">
                    Update Router
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
