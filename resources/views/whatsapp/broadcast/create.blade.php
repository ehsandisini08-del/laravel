<x-admin-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Broadcast Message</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <x-card title="Send Broadcast">
            <form method="POST" action="{{ route('whatsapp.broadcast.store') }}" class="space-y-4">
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template (Optional)</label>
                    <select name="template_id" id="template-select" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Manual message</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }} ({{ $tpl->category }})</option>
                        @endforeach
                    </select>
                    @error('template_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div id="message-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                    <textarea name="message" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" required>{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Area</label>
                        <select name="area_id" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Areas</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Package</label>
                        <select name="package_id" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Packages</option>
                            @foreach($packages as $pkg)
                                <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-between">
                    <a href="{{ route('whatsapp.dashboard') }}" class="btn-sm btn-neutral">Cancel</a>
                    <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Send Broadcast</button>
                </div>
            </form>
        </x-card>
    </div>

    <script>
    document.getElementById('template-select').addEventListener('change', function() {
        const msgField = document.getElementById('message-field');
        const textarea = msgField.querySelector('textarea');
        if (this.value) {
            textarea.removeAttribute('required');
            msgField.style.opacity = '0.5';
        } else {
            textarea.setAttribute('required', 'required');
            msgField.style.opacity = '1';
        }
    });
    </script>
</x-admin-layout>