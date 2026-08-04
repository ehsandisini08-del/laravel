<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="connection.name">{{ $connection['name'] ?? '' }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Active connection details</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ppp-active.index', ['router_id' => $router->id]) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Connection Details</h3>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                    <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                    Online
                </span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Username</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['name'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Router</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $router->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Service</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['service'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Caller ID</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['caller_id'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">IP Address</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['address'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Interface</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['interface'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Uptime</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['uptime'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Session Time</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['session_time'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Connected Since</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['connected_since'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Session ID</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['session_id'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Encoding</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['encoding'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Radius</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $connection['radius'] ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Traffic Statistics">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Bytes In</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format($connection['bytes_in'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Bytes Out</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format($connection['bytes_out'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Packets In</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format($connection['packets_in'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Packets Out</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format($connection['packets_out'] ?? 0) }}</dd>
                </div>
            </dl>
        </x-card>

        @if($connection['comment'])
            <x-card title="Comment">
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $connection['comment'] }}</p>
            </x-card>
        @endif

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('ppp-active.index', ['router_id' => $router->id]) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Back to List
            </a>
            <form method="POST" action="{{ route('ppp-active.disconnect') }}" x-data @submit.prevent="async () => { if(await customConfirm('Apakah Anda yakin ingin disconnect user ini?')) $el.submit() }">
                @csrf
                <input type="hidden" name="router_id" value="{{ $router->id }}">
                <input type="hidden" name="user_id" value="{{ $connection['id'] }}">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Disconnect User
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>
