<x-admin-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">WhatsApp Gateway Settings</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <x-card title="Gateway Configuration">
            <form method="POST" action="{{ route('whatsapp.settings.update') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gateway URL</label>
                    <input type="url" name="gateway_url" value="{{ old('gateway_url', $settings['gateway_url'] ?? config('services.baileys_gateway.base_url')) }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('gateway_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Token</label>
                    <input type="text" name="api_token" value="{{ old('api_token', $settings['api_token'] ?? config('services.baileys_gateway.api_token')) }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('api_token') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Request Timeout (seconds)</label>
                    <input type="number" name="request_timeout" value="{{ old('request_timeout', $settings['request_timeout'] ?? config('services.baileys_gateway.timeout')) }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required min="1" max="120">
                    @error('request_timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Retry</label>
                    <input type="number" name="max_retry" value="{{ old('max_retry', $settings['max_retry'] ?? '3') }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required min="1" max="10">
                    @error('max_retry') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Session Storage Path</label>
                    <input type="text" name="session_storage_path" value="{{ old('session_storage_path', $settings['session_storage_path'] ?? './sessions') }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('session_storage_path') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook URL</label>
                    <input type="url" name="webhook_url" value="{{ old('webhook_url', $settings['webhook_url'] ?? '') }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('webhook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook Secret</label>
                    <input type="text" name="webhook_secret" value="{{ old('webhook_secret', $settings['webhook_secret'] ?? config('services.baileys_gateway.webhook_secret')) }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('webhook_secret') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="auto_reconnect" value="1" id="auto_reconnect" {{ old('auto_reconnect', $settings['auto_reconnect'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500">
                    <label for="auto_reconnect" class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Reconnect</label>
                </div>
                <div class="flex justify-between">
                    <a href="{{ route('whatsapp.dashboard') }}" class="btn-sm btn-neutral">Back</a>
                    <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Save Settings</button>
                </div>
            </form>
        </x-card>
    </div>
</x-admin-layout>