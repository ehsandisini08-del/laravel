<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Message Detail</h1>
            <a href="{{ route('whatsapp.messages.index') }}" class="text-blue-600 hover:text-blue-800">Back to Messages</a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-card>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Phone</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $message->phone }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Device</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $message->device?->device_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Direction</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $message->direction }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <x-badge variant="{{ $message->status === 'failed' ? 'danger' : ($message->status === 'sent' ? 'success' : 'default') }}">{{ $message->status }}</x-badge>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Type</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $message->type }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Message ID</p>
                    <p class="font-medium text-gray-900 dark:text-white text-xs">{{ $message->baileys_message_id ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Sent At</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $message->sent_at?->format('d M Y H:i:s') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Delivered At</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $message->delivered_at?->format('d M Y H:i:s') ?? '-' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Message</p>
                    <p class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white whitespace-pre-wrap">{{ $message->message }}</p>
                </div>
            </div>
        </x-card>
    </div>
</x-admin-layout>