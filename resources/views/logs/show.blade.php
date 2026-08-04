<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Log Detail</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Detailed view of activity log entry</p>
            </div>
            <a href="{{ route('logs.index') }}{{ request()->server('HTTP_REFERER') ? '' : '' }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Logs
            </a>
        </div>
    </x-slot>

    @php $props = $log->properties ?? []; @endphp

    <div class="max-w-4xl mx-auto space-y-6">
        <x-card title="Activity Information">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $log->created_at->format('d M Y H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $log->causer?->name ?? 'System' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $log->causer?->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Module</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            {{ $props['module'] ?? '-' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Activity</dt>
                    <dd class="mt-1">
                        @php
                            $event = $log->event ?? '';
                            $badgeClass = match(true) {
                                in_array($event, ['Created', 'Login Success', 'Connected', 'Enabled']) => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200',
                                in_array($event, ['Updated', 'Synced']) => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200',
                                in_array($event, ['Deleted', 'Connection Failed', 'Login Failed', 'Disconnected', 'Disabled']) => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200',
                                in_array($event, ['View', 'Refresh']) => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
                                default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                            {{ $event ?: '-' }}
                        </span>
                    </dd>
                </div>
                @if(isset($props['router_name']))
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Router</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $props['router_name'] }}</dd>
                </div>
                @endif
            </dl>
        </x-card>

        <x-card title="Description">
            <p class="text-sm text-gray-900 dark:text-white">{{ $log->description }}</p>
        </x-card>

        <x-card title="Request Information">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $props['ip_address'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">HTTP Method</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200">
                            {{ $props['method'] ?? '-' }}
                        </span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">URL</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">{{ $props['url'] ?? '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white break-all font-mono">{{ $props['user_agent'] ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>

        @if($log->properties && count($log->properties) > 0)
        <x-card title="Properties (JSON)">
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-sm text-gray-800 dark:text-gray-200 font-mono">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </x-card>
        @endif
    </div>
</x-admin-layout>
