<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Message Templates</h1>
            <a href="{{ route('whatsapp.templates.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">Add Template</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif

        <x-card>
            <form method="GET" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search templates..." class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <select name="category" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <option value="reminder" {{ request('category') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                    <option value="payment" {{ request('category') === 'payment' ? 'selected' : '' }}>Payment</option>
                    <option value="broadcast" {{ request('category') === 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                    <option value="otp" {{ request('category') === 'otp' ? 'selected' : '' }}>OTP</option>
                    <option value="custom" {{ request('category') === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">Filter</button>
            </form>
        </x-card>

        @if($templates->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <p class="text-gray-500">No templates yet.</p>
                </div>
            </x-card>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($templates as $template)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $template->name }}</div>
                                    @if($template->title)
                                        <div class="text-xs text-gray-500">{{ $template->title }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ ucfirst($template->category) }}</td>
                                <td class="px-6 py-4">
                                    <x-badge variant="{{ $template->is_active ? 'success' : 'default' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</x-badge>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('whatsapp.templates.edit', $template) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">Edit</a>
                                    <form method="POST" action="{{ route('whatsapp.templates.destroy', $template) }}" class="inline" x-data @submit.prevent="if(confirm('Delete this template?')) $el.submit()">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $templates->links() }}</div>
        @endif
    </div>
</x-admin-layout>
