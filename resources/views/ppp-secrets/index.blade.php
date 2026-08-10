<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">PPP Secrets</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage PPPoE/PPTP/L2TP user accounts</p>
            </div>
            <div class="flex items-center gap-3">
                @if($selectedRouter)
                    <button onclick="syncSecrets()" class="app-btn-success px-4 py-2.5 text-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sync
                    </button>
                @endif
                <a href="{{ route('ppp-secrets.create', ['router_id' => $selectedRouter?->id]) }}" class="app-btn-primary px-4 py-2.5 text-sm">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add PPP Secret
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="pppSecretManager()">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif

        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <x-card>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Router</label>
                    <select onchange="window.location.href='{{ route('ppp-secrets.index') }}?router_id='+this.value" class="w-full md:w-64 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Select Router --</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ $selectedRouter?->id == $router->id ? 'selected' : '' }}>
                                {{ $router->name }} ({{ $router->host }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($selectedRouter)
                    <form method="GET" action="{{ route('ppp-secrets.index') }}" class="flex flex-col gap-3 md:flex-row">
                        <input type="hidden" name="router_id" value="{{ $selectedRouter->id }}">
                        <div class="flex-1">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by username, profile, IP..." class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <button type="submit" class="btn-sm btn-neutral">Filter</button>
                    </form>

                    <div class="flex items-center gap-3" x-show="selectedSecrets.length > 0" x-cloak>
                        <span class="text-sm text-gray-600 dark:text-gray-400"><span x-text="selectedSecrets.length"></span> selected</span>
                        <button @click="bulkEnable" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Enable</button>
                        <button @click="bulkDisable" class="text-sm text-yellow-600 dark:text-yellow-400 hover:underline">Disable</button>
                        <button @click="bulkDelete" class="text-sm text-red-600 dark:text-red-400 hover:underline">Delete</button>
                    </div>
                @endif
            </div>
        </x-card>

        @if(!$selectedRouter)
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Router Selected</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Please select a router to view PPP Secrets.</p>
                </div>
            </x-card>
        @elseif($pppSecrets->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No PPP Secrets</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new PPP Secret or sync from router.</p>
                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button onclick="syncSecrets()" class="app-btn-success px-4 py-2.5 text-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Sync from Router
                        </button>
                        <a href="{{ route('ppp-secrets.create', ['router_id' => $selectedRouter->id]) }}" class="app-btn-primary px-4 py-2.5 text-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add PPP Secret
                        </a>
                    </div>
                </div>
            </x-card>
        @else
            <div class="admin-panel">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left"><input type="checkbox" @change="toggleAll" :checked="selectedSecrets.length === {{ $pppSecrets->count() }}" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"></th>
                                <th class="text-left">Username</th>
                                <th class="text-left">Profile</th>
                                <th class="text-left">Service</th>
                                <th class="text-left">Remote Address</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pppSecrets as $secret)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <input type="checkbox" value="{{ $secret->id }}" x-model="selectedSecrets" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $secret->name }}</div>
                                        @if($secret->comment)
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($secret->comment, 30) }}</div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $secret->profile ?? '-' }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $secret->service ?? 'any' }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $secret->remote_address ?? '-' }}</td>
                                    <td class="whitespace-nowrap">
                                        @if($secret->disabled)
                                            <x-badge variant="danger">🔴 Disabled</x-badge>
                                        @else
                                            <x-badge variant="success">🟢 Active</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            @if($secret->disabled)
                                                <button @click="enableSecret({{ $secret->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300" title="Enable">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            @else
                                                <button @click="disableSecret({{ $secret->id }})" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300" title="Disable">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            <a href="{{ route('ppp-secrets.edit', $secret) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300" title="Edit">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button @click="deleteSecret({{ $secret->id }}, '{{ addslashes($secret->name) }}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" title="Delete">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $pppSecrets->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif
            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif
        });

        function syncSecrets() {
            const routerId = {{ $selectedRouter?->id ?? 'null' }};
            if (!routerId) {
                showToast('Please select a router first', 'error');
                return;
            }

            showToast('Syncing PPP Secrets...', 'info');

            fetch('/ppp-secrets/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ router_id: routerId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Sync error:', error);
                showToast('Failed to sync: ' + error.message, 'error');
            });
        }

        function pppSecretManager() {
            return {
                selectedSecrets: [],
                toggleAll(event) {
                    if (event.target.checked) {
                        this.selectedSecrets = [{{ $pppSecrets->pluck('id')->implode(',') }}];
                    } else {
                        this.selectedSecrets = [];
                    }
                },
                async enableSecret(secretId) {
                    try {
                        showToast('Enabling PPP Secret...', 'info');
                        const response = await fetch(`/ppp-secrets/${secretId}/enable`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async disableSecret(secretId) {
                    try {
                        showToast('Disabling PPP Secret...', 'info');
                        const response = await fetch(`/ppp-secrets/${secretId}/disable`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async deleteSecret(secretId, secretName) {
                    const confirmed = await customConfirm(`Apakah Anda yakin ingin menghapus PPP Secret "${secretName}"?`);
                    if (!confirmed) {
                        return;
                    }
                    try {
                        showToast('Deleting PPP Secret...', 'info');
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/ppp-secrets/${secretId}`;
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                            <input type="hidden" name="_method" value="DELETE">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async bulkDelete() {
                    const confirmed = await customConfirm(`Apakah Anda yakin ingin menghapus ${this.selectedSecrets.length} PPP Secret?`);
                    if (!confirmed) return;
                    try {
                        showToast('Deleting PPP Secrets...', 'info');
                        const response = await fetch('/ppp-secrets/bulk-delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: this.selectedSecrets })
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async bulkEnable() {
                    try {
                        showToast('Enabling PPP Secrets...', 'info');
                        const response = await fetch('/ppp-secrets/bulk-enable', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: this.selectedSecrets })
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async bulkDisable() {
                    try {
                        showToast('Disabling PPP Secrets...', 'info');
                        const response = await fetch('/ppp-secrets/bulk-disable', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: this.selectedSecrets })
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>
