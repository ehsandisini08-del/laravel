<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $pppProfile->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PPP Profile details and configuration</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ppp-profiles.edit', $pppProfile) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
                <a href="{{ route('ppp-profiles.index', ['router_id' => $pppProfile->router_id]) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <x-card title="Profile Information">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Profile Name</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Router</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->router->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Local Address</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->local_address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Remote Address</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->remote_address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">DNS Server</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->dns_server ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Rate Limit</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->rate_limit ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Parent Queue</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->parent_queue ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">PPPs Using This Profile</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $secretsCount }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Feature Toggles">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Only One</dt>
                    <dd>
                        @if($pppProfile->only_one)
                            <x-badge variant="success">Enabled</x-badge>
                        @else
                            <x-badge variant="danger">Disabled</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Change TCP MSS</dt>
                    <dd>
                        @if($pppProfile->change_tcp_mss)
                            <x-badge variant="success">Enabled</x-badge>
                        @else
                            <x-badge variant="danger">Disabled</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Use Compression</dt>
                    <dd>
                        @if($pppProfile->use_compression)
                            <x-badge variant="success">Enabled</x-badge>
                        @else
                            <x-badge variant="danger">Disabled</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Use Encryption</dt>
                    <dd>
                        @if($pppProfile->use_encryption)
                            <x-badge variant="success">Enabled</x-badge>
                        @else
                            <x-badge variant="danger">Disabled</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Use IPv6</dt>
                    <dd>
                        @if($pppProfile->use_ipv6)
                            <x-badge variant="success">Enabled</x-badge>
                        @else
                            <x-badge variant="danger">Disabled</x-badge>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Bridge Settings">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Bridge</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->bridge ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Bridge Path Cost</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->bridge_path_cost ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Bridge Horizon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->bridge_horizon ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>

        @if($pppProfile->comment)
            <x-card title="Comment">
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $pppProfile->comment }}</p>
            </x-card>
        @endif

        <x-card title="Timestamps">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Last Synced</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->synced_at ? $pppProfile->synced_at->diffForHumans() : 'Never' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->created_at->diffForHumans() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Updated</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pppProfile->updated_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-admin-layout>
