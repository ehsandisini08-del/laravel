<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Messages</h1>
            <a href="{{ route('whatsapp.messages.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">Send Message</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <x-card>
            <form method="GET" class="flex flex-col md:flex-row gap-3">
                <select name="device_id" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Devices</option>
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ request('device_id') == $dev->id ? 'selected' : '' }}>{{ $dev->device_name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
                <input type="text" name="phone" value="{{ request('phone') }}" placeholder="Search phone..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($messages->isEmpty())
            <x-card><div class="text-center py-12"><p class="text-gray-500">No messages yet.</p></div></x-card>
        @else
            <div class="admin-panel">
                <table>
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Direction</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($messages as $msg)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $msg->phone }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $msg->device?->device_name }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <x-badge variant="{{ $msg->direction === 'outgoing' ? 'info' : 'default' }}">{{ $msg->direction }}</x-badge>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <x-badge variant="{{ $msg->status === 'failed' ? 'danger' : ($msg->status === 'sent' ? 'success' : ($msg->status === 'delivered' ? 'primary' : 'default')) }}">{{ $msg->status }}</x-badge>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $msg->created_at->format('d M H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('whatsapp.messages.show', $msg) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $messages->links() }}</div>
        @endif
    </div>
</x-admin-layout>