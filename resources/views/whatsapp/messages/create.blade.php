<x-admin-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Send Message</h1>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <x-card title="New Message">
            <form method="POST" action="{{ route('whatsapp.messages.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Device</label>
                    <select name="device_id" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select device</option>
                        @foreach($devices as $dev)
                            <option value="{{ $dev->id }}" {{ old('device_id') == $dev->id ? 'selected' : '' }}>{{ $dev->device_name }} ({{ $dev->phone_number ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                    @error('device_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="62821xxxxxx" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                    <textarea name="message" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-between">
                    <a href="{{ route('whatsapp.messages.index') }}" class="btn-sm btn-neutral">Cancel</a>
                    <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Send Message</button>
                </div>
            </form>
        </x-card>
    </div>
</x-admin-layout>