<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Message Templates</h1>
            <a href="{{ route('whatsapp.templates.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">Add Template</a>
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
                <select name="category" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <option value="reminder" {{ request('category') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                    <option value="payment" {{ request('category') === 'payment' ? 'selected' : '' }}>Payment</option>
                    <option value="broadcast" {{ request('category') === 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                    <option value="otp" {{ request('category') === 'otp' ? 'selected' : '' }}>OTP</option>
                    <option value="custom" {{ request('category') === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
                <button type="submit" class="btn-sm btn-neutral">Filter</button>
            </form>
        </x-card>

        @if($templates->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <p class="text-gray-500">No templates yet.</p>
                </div>
            </x-card>
        @else
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                    <thead>
                        <tr>
                            <th class="text-left">Name</th>
                            <th class="text-left">Category</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($templates as $template)
                            <tr>
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
                                    <form method="POST" action="{{ route('whatsapp.templates.destroy', $template) }}" class="inline" x-data @submit.prevent="async () => { if(await customConfirm('Hapus template ini?')) $el.submit() }">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
            <div class="mt-4">{{ $templates->links() }}</div>
        @endif
    </div>
</x-admin-layout>
