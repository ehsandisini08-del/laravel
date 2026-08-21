<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Add Package</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a new internet service package</p>
            </div>
            <a href="{{ route('packages.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        @if($errors->any())
            <x-alert variant="danger" dismissible class="mb-6">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('packages.store') }}" class="space-y-6" x-data="packageForm()">
            @csrf

            <x-card title="Package Information">
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="price" id="price" value="{{ old('price') }}" required min="0" step="0.01" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" id="description" rows="2" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                    </div>
                </div>
            </x-card>

            <x-card title="Router & Profile">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="router_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router <span class="text-red-500">*</span></label>
                        <select name="router_id" id="router_id" required @change="onRouterChange" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Router --</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ old('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="ppp_profile_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">PPP Profile <span class="text-red-500">*</span></label>
                        <select name="ppp_profile_id" id="ppp_profile_id" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Router First --</option>
                        </select>
                    </div>
                </div>
            </x-card>

            <x-card title="Coverage Areas">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Areas <span class="text-red-500">*</span></label>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Select one or more areas where this package is available.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($areas as $area)
                            <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                                <input type="checkbox" name="areas[]" value="{{ $area->id }}" {{ in_array($area->id, old('areas', [])) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $area->name }} ({{ $area->code }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </x-card>

            <x-card>
                <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('packages.index') }}" class="app-btn-ghost">Cancel</a>
                <button type="submit" class="app-btn-primary px-4 py-2.5 text-sm">Create Package</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function packageForm() {
            return {
                onRouterChange() {
                    const routerId = document.getElementById('router_id').value;
                    const profileSelect = document.getElementById('ppp_profile_id');
                    profileSelect.innerHTML = '<option value="">Loading...</option>';

                    if (!routerId) {
                        profileSelect.innerHTML = '<option value="">-- Select Router First --</option>';
                        return;
                    }

                    fetch('/packages/router/' + routerId + '/profiles')
                        .then(r => r.json())
                        .then(profiles => {
                            profileSelect.innerHTML = '<option value="">-- Select Profile --</option>';
                            profiles.forEach(p => {
                                const selected = '{{ old('ppp_profile_id') }}' == p.id ? 'selected' : '';
                                profileSelect.innerHTML += `<option value="${p.id}" ${selected}>${p.name}</option>`;
                            });
                        })
                        .catch(() => {
                            profileSelect.innerHTML = '<option value="">Failed to load profiles</option>';
                        });
                },
                init() {
                    if (this.routerId) {
                        this.onRouterChange();
                    }
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>
