<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Routers</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your MikroTik routers</p>
            </div>
            <div>
                <a href="{{ route('routers.create') }}" class="app-btn-primary px-4 py-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Router
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="routerManager()">
        @if(session('success'))
            <x-alert variant="success" dismissible>
                {{ session('success') }}
            </x-alert>
        @endif

        @if(session('error'))
            <x-alert variant="danger" dismissible>
                {{ session('error') }}
            </x-alert>
        @endif

        <x-card>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <form method="GET" action="{{ route('routers.index') }}" class="flex-1 flex gap-3">
                    <div class="flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search routers..." class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                        <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                        <option value="checking" {{ request('status') === 'checking' ? 'selected' : '' }}>Checking</option>
                    </select>
                    <button type="submit" class="btn-sm btn-neutral">
                        Filter
                    </button>
                </form>
            </div>

            <div class="mt-4 flex items-center gap-3" x-show="selectedRouters.length > 0" x-cloak>
                <span class="text-sm text-gray-600 dark:text-gray-400"><span x-text="selectedRouters.length"></span> selected</span>
                <button @click="bulkEnable" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Enable</button>
                <button @click="bulkDisable" class="text-sm text-yellow-600 dark:text-yellow-400 hover:underline">Disable</button>
                <button @click="bulkDelete" class="text-sm text-red-600 dark:text-red-400 hover:underline">Delete</button>
            </div>
        </x-card>

        @if($routers->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No routers</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new router.</p>
                    <div class="mt-6">
                        <a href="{{ route('routers.create') }}" class="app-btn-primary px-4 py-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Router
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
                                <th class="text-left">
                                    <input type="checkbox" @change="toggleAll" :checked="selectedRouters.length === {{ $routers->count() }}" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="text-left">Name</th>
                                <th class="text-left">Host</th>
                                <th class="text-left">Identity</th>
                                <th class="text-left">Version</th>
                                <th class="text-left">Status</th>
                                <th class="text-left">Last Seen</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($routers as $router)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <input type="checkbox" value="{{ $router->id }}" x-model="selectedRouters" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $router->name }}</div>
                                                @if($router->is_default)
                                                    <x-badge variant="primary" size="sm">Default</x-badge>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $router->host }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Port: {{ $router->api_port }}{{ $router->api_ssl ? ' (SSL)' : '' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $router->identity ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $router->routeros_version ?? '-' }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $router->board_name ?? '-' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if($router->status === 'online')
                                            <x-badge variant="success">🟢 Online</x-badge>
                                        @elseif($router->status === 'checking')
                                            <x-badge variant="warning">🟡 Checking...</x-badge>
                                        @else
                                            <x-badge variant="danger">🔴 Offline</x-badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $router->last_seen_at ? $router->last_seen_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="testConnection({{ $router->id }})" class="icon-btn text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" title="Test Connection">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </button>
                                            <a href="{{ route('routers.edit', $router) }}" class="icon-btn" title="Edit">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button @click="deleteRouter({{ $router->id }}, '{{ addslashes($router->name) }}')" class="icon-btn-danger" title="Delete">
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
                {{ $routers->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        // Toast notification helper
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const colors = {
                success: 'bg-green-50 dark:bg-green-900/20 border-green-500 text-green-800 dark:text-green-200',
                error: 'bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800 dark:text-red-200',
                info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-500 text-blue-800 dark:text-blue-200'
            };
            
            const toast = document.createElement('div');
            toast.className = `${colors[type]} border-l-4 p-4 rounded-lg shadow-lg max-w-sm animate-slide-in`;
            toast.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="flex-1 text-sm">${message}</div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            `;
            
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        function routerManager() {
            return {
                selectedRouters: [],
                toggleAll(event) {
                    if (event.target.checked) {
                        this.selectedRouters = [{{ $routers->pluck('id')->implode(',') }}];
                    } else {
                        this.selectedRouters = [];
                    }
                },
                async testConnection(routerId) {
                    console.log('Testing connection for router ID:', routerId);
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('CSRF token not found!');
                        showToast('CSRF token not found. Please refresh the page.', 'error');
                        return;
                    }
                    
                    try {
                        showToast('Testing connection...', 'info');
                        
                        const url = `/routers/${routerId}/test-connection`;
                        console.log('Request URL:', url);
                        
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken.content,
                                'Accept': 'application/json'
                            }
                        });
                        
                        console.log('Response status:', response.status);
                        
                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('Response error:', errorText);
                            showToast(`HTTP Error ${response.status}: ${errorText}`, 'error');
                            return;
                        }
                        
                        const data = await response.json();
                        console.log('Response data:', data);
                        
                        if (data.success) {
                            const info = [
                                '✓ Connected Successfully\n',
                                'Identity: ' + (data.data.identity || '-'),
                                'Version: ' + (data.data.version || '-'),
                                'Board: ' + (data.data.board_name || '-'),
                                'Architecture: ' + (data.data.architecture || '-'),
                                'CPU: ' + (data.data.cpu || '-'),
                                'Uptime: ' + (data.data.uptime || '-')
                            ].join('\n');
                            
                            alert(info);
                            showToast('Router connected successfully!', 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            console.error('Connection failed:', data.message);
                            alert('Connection Failed\n\n' + data.message);
                            showToast('Connection failed: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Exception during test connection:', error);
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async deleteRouter(routerId, routerName) {
                    console.log('Deleting router:', routerId, routerName);
                    
                    const confirmed = await customConfirm(`Apakah Anda yakin ingin menghapus router "${routerName}"?`);
                    if (!confirmed) {
                        return;
                    }
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('CSRF token not found!');
                        showToast('CSRF token not found. Please refresh the page.', 'error');
                        return;
                    }
                    
                    try {
                        showToast('Deleting router...', 'info');
                        
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/routers/${routerId}`;
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="${csrfToken.content}">
                            <input type="hidden" name="_method" value="DELETE">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    } catch (error) {
                        console.error('Exception during delete:', error);
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async bulkDelete() {
                    const confirmed = await customConfirm(`Apakah Anda yakin ingin menghapus ${this.selectedRouters.length} router?`);
                    if (!confirmed) {
                        return;
                    }
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        showToast('CSRF token not found. Please refresh the page.', 'error');
                        return;
                    }
                    
                    try {
                        showToast('Deleting routers...', 'info');
                        
                        const response = await fetch('/routers/bulk-delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken.content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: this.selectedRouters })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Exception during bulk delete:', error);
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async bulkEnable() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        showToast('CSRF token not found. Please refresh the page.', 'error');
                        return;
                    }
                    
                    try {
                        showToast('Enabling routers...', 'info');
                        
                        const response = await fetch('/routers/bulk-enable', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken.content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: this.selectedRouters })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Exception during bulk enable:', error);
                        showToast('Error: ' + error.message, 'error');
                    }
                },
                async bulkDisable() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        showToast('CSRF token not found. Please refresh the page.', 'error');
                        return;
                    }
                    
                    try {
                        showToast('Disabling routers...', 'info');
                        
                        const response = await fetch('/routers/bulk-disable', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken.content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: this.selectedRouters })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Exception during bulk disable:', error);
                        showToast('Error: ' + error.message, 'error');
                    }
                }
            }
        }
        
        // Debug: Log when script loads
        console.log('Router manager script loaded');
        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.content);
    </script>
    @endpush
</x-admin-layout>
