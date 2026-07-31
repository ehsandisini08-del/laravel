<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add New Router</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Connect a new MikroTik router to your system</p>
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

        <form method="POST" action="{{ route('routers.store') }}" class="space-y-6">
            @csrf

            <x-card title="Router Information">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">A friendly name to identify this router</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional description or notes about this router</p>
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                        <input type="text" name="location" id="location" value="{{ old('location') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Physical location of the router</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Connection Settings">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Host / IP Address <span class="text-red-500">*</span></label>
                        <input type="text" name="host" id="host" value="{{ old('host') }}" required placeholder="192.168.88.1" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">IP address or hostname of the router</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="api_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Port <span class="text-red-500">*</span></label>
                            <input type="number" name="api_port" id="api_port" value="{{ old('api_port', 8728) }}" required min="1" max="65535" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Default: 8728</p>
                        </div>

                        <div>
                            <label for="api_ssl" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SSL Connection</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="api_ssl" id="api_ssl" value="1" {{ old('api_ssl') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Enable SSL/TLS</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use port 8729 for SSL</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="username" value="{{ old('username', 'admin') }}" required autocomplete="off" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">RouterOS API username</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password" required autocomplete="new-password" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">RouterOS API password (will be encrypted)</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Router Settings">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="enabled" value="1" {{ old('enabled', true) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable Router</span>
                        </label>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only enabled routers will be used by the system</p>
                    </div>

                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Set as Default Router</span>
                        </label>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The default router will be used when no specific router is selected</p>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                        <input type="number" name="priority" id="priority" value="{{ old('priority', 0) }}" min="0" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Higher priority routers are used first (0 = lowest)</p>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('routers.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Add Router
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
