<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add PPP Secret</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a new PPPoE/PPTP/L2TP user account</p>
            </div>
            <a href="{{ route('ppp-secrets.index', ['router_id' => $selectedRouter?->id]) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
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

        <form method="POST" action="{{ route('ppp-secrets.store') }}" class="space-y-6">
            @csrf

            <x-card title="Router Selection">
                <div>
                    <label for="router_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router <span class="text-red-500">*</span></label>
                    <select name="router_id" id="router_id" required onchange="window.location.href='{{ route('ppp-secrets.create') }}?router_id='+this.value" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Select Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ $selectedRouter?->id == $router->id ? 'selected' : '' }}>
                                {{ $router->name }} ({{ $router->host }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select the router where this PPP Secret will be created</p>
                </div>
            </x-card>

            @if($selectedRouter)
                <x-card title="Account Information">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PPP username for authentication</p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" id="password" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PPP password for authentication</p>
                        </div>
                    </div>
                </x-card>

                <x-card title="Connection Settings">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="service" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service</label>
                            <select name="service" id="service" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Any</option>
                                <option value="pppoe" {{ old('service') === 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="pptp" {{ old('service') === 'pptp' ? 'selected' : '' }}>PPTP</option>
                                <option value="l2tp" {{ old('service') === 'l2tp' ? 'selected' : '' }}>L2TP</option>
                                <option value="sstp" {{ old('service') === 'sstp' ? 'selected' : '' }}>SSTP</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PPP service type</p>
                        </div>

                        <div>
                            <label for="profile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile</label>
                            <select name="profile" id="profile" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Default</option>
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile }}" {{ old('profile') === $profile ? 'selected' : '' }}>{{ $profile }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PPP profile to use</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="local_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Local Address</label>
                                <input type="text" name="local_address" id="local_address" value="{{ old('local_address') }}" placeholder="10.0.0.1" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Router IP (optional)</p>
                            </div>

                            <div>
                                <label for="remote_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Remote Address</label>
                                <input type="text" name="remote_address" id="remote_address" value="{{ old('remote_address') }}" placeholder="10.0.0.2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Client IP (optional)</p>
                            </div>
                        </div>

                        <div>
                            <label for="caller_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Caller ID</label>
                            <input type="text" name="caller_id" id="caller_id" value="{{ old('caller_id') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">MAC address or phone number (optional)</p>
                        </div>

                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comment</label>
                            <textarea name="comment" id="comment" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('comment') }}</textarea>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional notes or description</p>
                        </div>
                    </div>
                </x-card>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('ppp-secrets.index', ['router_id' => $selectedRouter->id]) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Create PPP Secret
                    </button>
                </div>
            @endif
        </form>
    </div>
</x-admin-layout>
