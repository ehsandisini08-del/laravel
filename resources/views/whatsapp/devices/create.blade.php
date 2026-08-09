<x-admin-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Device</h1>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <x-card title="New WhatsApp Device">
            <form method="POST" action="{{ route('whatsapp.devices.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Device Name</label>
                    <input type="text" name="device_name" value="{{ old('device_name') }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('device_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Session Name</label>
                    <input type="text" name="session_name" value="{{ old('session_name') }}" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. admin, support, billing" required>
                    @error('session_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-between">
                    <a href="{{ route('whatsapp.devices.index') }}" class="btn-sm btn-neutral">Cancel</a>
                    <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Create Device</button>
                </div>
            </form>
        </x-card>
    </div>
</x-admin-layout>