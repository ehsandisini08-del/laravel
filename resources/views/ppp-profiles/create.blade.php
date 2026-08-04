<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add PPP Profile</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a new PPPoE/PPTP/L2TP connection profile</p>
            </div>
            <a href="{{ route('ppp-profiles.index', ['router_id' => $selectedRouter?->id]) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
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

        <form method="POST" action="{{ route('ppp-profiles.store') }}" class="space-y-6">
            @csrf

            <x-card title="Router Selection">
                <div>
                    <label for="router_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router <span class="text-red-500">*</span></label>
                    <select name="router_id" id="router_id" required onchange="window.location.href='{{ route('ppp-profiles.create') }}?router_id='+this.value" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Select Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ $selectedRouter?->id == $router->id ? 'selected' : '' }}>
                                {{ $router->name }} ({{ $router->host }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </x-card>

            @if($selectedRouter)
                <x-card title="Profile Information">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="local_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Local Address</label>
                                <input type="text" name="local_address" id="local_address" value="{{ old('local_address') }}" placeholder="10.0.0.1" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="remote_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Remote Address</label>
                                <input type="text" name="remote_address" id="remote_address" value="{{ old('remote_address') }}" placeholder="10.0.0.2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label for="dns_server" class="block text-sm font-medium text-gray-700 dark:text-gray-300">DNS Server</label>
                            <input type="text" name="dns_server" id="dns_server" value="{{ old('dns_server') }}" placeholder="8.8.8.8,8.8.4.4" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="rate_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rate Limit</label>
                                <input type="text" name="rate_limit" id="rate_limit" value="{{ old('rate_limit') }}" placeholder="10M/10M" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="parent_queue" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parent Queue</label>
                                <input type="text" name="parent_queue" id="parent_queue" value="{{ old('parent_queue') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </x-card>

                <x-card title="Advanced Settings">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                            <input type="hidden" name="only_one" value="0">
                            <input type="checkbox" name="only_one" value="1" {{ old('only_one') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Only One</span>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                            <input type="hidden" name="change_tcp_mss" value="0">
                            <input type="checkbox" name="change_tcp_mss" value="1" {{ old('change_tcp_mss') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Change TCP MSS</span>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                            <input type="hidden" name="use_compression" value="0">
                            <input type="checkbox" name="use_compression" value="1" {{ old('use_compression') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Use Compression</span>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                            <input type="hidden" name="use_encryption" value="0">
                            <input type="checkbox" name="use_encryption" value="1" {{ old('use_encryption') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Use Encryption</span>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                            <input type="hidden" name="use_ipv6" value="0">
                            <input type="checkbox" name="use_ipv6" value="1" {{ old('use_ipv6') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Use IPv6</span>
                        </label>
                    </div>
                </x-card>

                <x-card title="Bridge Settings">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="bridge" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bridge</label>
                            <input type="text" name="bridge" id="bridge" value="{{ old('bridge') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="bridge_path_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bridge Path Cost</label>
                                <input type="number" name="bridge_path_cost" id="bridge_path_cost" value="{{ old('bridge_path_cost') }}" min="1" max="999" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="bridge_horizon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bridge Horizon</label>
                                <input type="text" name="bridge_horizon" id="bridge_horizon" value="{{ old('bridge_horizon') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </x-card>

                <x-card title="Additional">
                    <div>
                        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comment</label>
                        <textarea name="comment" id="comment" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('comment') }}</textarea>
                    </div>
                </x-card>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('ppp-profiles.index', ['router_id' => $selectedRouter->id]) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Create PPP Profile
                    </button>
                </div>
            @endif
        </form>
    </div>
</x-admin-layout>
