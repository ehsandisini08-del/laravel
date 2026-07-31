<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Devices</h1>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('whatsapp.devices.sync') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors">Sync Devices</button>
                </form>
                <a href="{{ route('whatsapp.devices.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">Add Device</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif

        @if($devices->isEmpty())
            <x-card><div class="text-center py-12"><p class="text-gray-500">No devices yet.</p><a href="{{ route('whatsapp.devices.create') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Add your first device</a></div></x-card>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($devices as $device)
                    <x-card>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $device->device_name }}</h3>
                                <x-badge variant="{{ $device->status_color }}">{{ $device->status_label }}</x-badge>
                            </div>
                            <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                @if($device->phone_number)
                                <p>Number: {{ $device->phone_number }}</p>
                                @endif
                                @if($device->profile_name)
                                <p>Name: {{ $device->profile_name }}</p>
                                @endif
                                <p>Session: {{ $device->session_name }}</p>
                                <p>Last Seen: {{ $device->last_seen?->diffForHumans() ?? 'Never' }}</p>
                            </div>
                            <div class="flex gap-2 pt-2">
                                <a href="{{ route('whatsapp.devices.show', $device) }}" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">View</a>
                                <form method="POST" action="{{ route('whatsapp.devices.destroy', $device) }}" onsubmit="return confirm('Hapus device ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
            <div class="mt-4">{{ $devices->links() }}</div>
        @endif
    </div>
</x-admin-layout>