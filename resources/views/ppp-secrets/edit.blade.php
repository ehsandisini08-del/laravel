<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit PPP Secret</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update PPP Secret configuration</p>
            </div>
            <a href="{{ route('ppp-secrets.index', ['router_id' => $pppSecret->router_id]) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
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

        <form method="POST" action="{{ route('ppp-secrets.update', $pppSecret) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card title="Account Information">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router</label>
                        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $pppSecret->router->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pppSecret->router->host }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $pppSecret->name }}</p>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Username cannot be changed</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <input type="password" name="password" id="password" placeholder="Leave blank to keep current password" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Leave blank to keep current password</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Connection Settings">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service</label>
                        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $pppSecret->service ?? 'Any' }}</p>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Service type cannot be changed</p>
                    </div>

                    <div>
                        <label for="profile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile</label>
                        <select name="profile" id="profile" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Default</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile }}" {{ old('profile', $pppSecret->profile) === $profile ? 'selected' : '' }}>{{ $profile }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PPP profile to use</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="local_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Local Address</label>
                            <input type="text" name="local_address" id="local_address" value="{{ old('local_address', $pppSecret->local_address) }}" placeholder="10.0.0.1" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Router IP</p>
                        </div>

                        <div>
                            <label for="remote_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Remote Address</label>
                            <input type="text" name="remote_address" id="remote_address" value="{{ old('remote_address', $pppSecret->remote_address) }}" placeholder="10.0.0.2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Client IP</p>
                        </div>
                    </div>

                    <div>
                        <label for="caller_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Caller ID</label>
                        <input type="text" name="caller_id" id="caller_id" value="{{ old('caller_id', $pppSecret->caller_id) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">MAC address or phone number</p>
                    </div>

                    <div>
                        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comment</label>
                        <textarea name="comment" id="comment" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('comment', $pppSecret->comment) }}</textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional notes or description</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Status Information">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Status:</span>
                        <span class="ml-2">
                            @if($pppSecret->disabled)
                                <x-badge variant="danger">🔴 Disabled</x-badge>
                            @else
                                <x-badge variant="success">🟢 Active</x-badge>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Last Logout:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $pppSecret->last_logged_out ? $pppSecret->last_logged_out->diffForHumans() : 'Never' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Created:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $pppSecret->created_at->diffForHumans() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Updated:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $pppSecret->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('ppp-secrets.index', ['router_id' => $pppSecret->router_id]) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Update PPP Secret
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
