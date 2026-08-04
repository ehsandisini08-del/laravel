<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">WhatsApp Gateway</h1>
            <x-badge variant="{{ $gatewayHealthy ? 'success' : 'danger' }}">
                {{ $gatewayHealthy ? 'Gateway Online' : 'Gateway Offline' }}
            </x-badge>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Connected</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['connected'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Disconnected</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['disconnected'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Messages Today</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_messages_today'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Failed</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_failed'] }}</p>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Message Stats">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Sent</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stats['total_sent'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Delivered</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stats['total_delivered'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Failed</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stats['total_failed'] }}</span>
                    </div>
                </div>
            </x-card>

            <x-card title="Quick Actions">
                <div class="space-y-3">
                    <a href="{{ route('whatsapp.devices.index') }}" class="block px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <span class="font-medium text-gray-900 dark:text-white">Device List</span>
                        <p class="text-sm text-gray-500">Manage WhatsApp devices & QR codes</p>
                    </a>
                    <a href="{{ route('whatsapp.devices.create') }}" class="block px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <span class="font-medium text-gray-900 dark:text-white">Add Device</span>
                        <p class="text-sm text-gray-500">Register new WhatsApp device</p>
                    </a>
                    <a href="{{ route('whatsapp.messages.index') }}" class="block px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <span class="font-medium text-gray-900 dark:text-white">Message History</span>
                        <p class="text-sm text-gray-500">View sent & received messages</p>
                    </a>
                    <a href="{{ route('whatsapp.messages.create') }}" class="block px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <span class="font-medium text-gray-900 dark:text-white">Send Message</span>
                        <p class="text-sm text-gray-500">Send manual WhatsApp message</p>
                    </a>
                    <a href="{{ route('whatsapp.broadcast.create') }}" class="block px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <span class="font-medium text-gray-900 dark:text-white">Broadcast</span>
                        <p class="text-sm text-gray-500">Send bulk messages to customers</p>
                    </a>
                    <a href="{{ route('whatsapp.settings.index') }}" class="block px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <span class="font-medium text-gray-900 dark:text-white">Settings</span>
                        <p class="text-sm text-gray-500">Configure WhatsApp Gateway</p>
                    </a>
                </div>
            </x-card>
        </div>
    </div>
</x-admin-layout>