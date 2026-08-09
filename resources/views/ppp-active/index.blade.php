<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Active Connections</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor PPPoE/PPTP/L2TP users currently online</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="pppActiveManager()">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('success'))
                    showToast('{{ session('success') }}', 'success');
                @endif
                @if(session('error'))
                    showToast('{{ session('error') }}', 'error');
                @endif
            });
        </script>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-card class="border-l-4 border-green-500">
                <p class="text-sm text-gray-500 dark:text-gray-400">Active Now</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="statistics.total_active + ' users'">
                    {{ $statistics['total_active'] ?? 0 }} users
                </p>
            </x-card>
            <x-card class="border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 dark:text-gray-400">Online Routers</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $onlineRouters }} / {{ $totalRouters }}</p>
            </x-card>
            <x-card class="border-l-4 border-red-500">
                <p class="text-sm text-gray-500 dark:text-gray-400">Offline Routers</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $offlineRouters }}</p>
            </x-card>
            <x-card class="border-l-4 border-purple-500">
                <p class="text-sm text-gray-500 dark:text-gray-400">Online Rate</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $totalRouters > 0 ? round(($onlineRouters / $totalRouters) * 100) : 0 }}%
                </p>
            </x-card>
            <x-card class="border-l-4 border-yellow-500">
                <p class="text-sm text-gray-500 dark:text-gray-400">Refresh</p>
                <select x-model="autoRefreshInterval" @change="toggleAutoRefresh" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="0">Off</option>
                    <option value="10000">10s</option>
                    <option value="30000">30s</option>
                    <option value="60000">60s</option>
                </select>
            </x-card>
        </div>

        <x-card>
            <div class="space-y-4">
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Router</label>
                        <select x-model="routerId" @change="selectRouter" class="w-full md:w-64 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Router --</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ $selectedRouter?->id == $router->id ? 'selected' : '' }}>
                                    {{ $router->name }} ({{ $router->host }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <template x-if="routerId">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="flex-1">
                                <input type="text" x-model="search" @input.debounce.300ms="fetchConnections" placeholder="Search username..." class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <select x-model="filterService" @change="fetchConnections" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Services</option>
                                <template x-for="s in availableServices" :key="s">
                                    <option x-text="s" :value="s"></option>
                                </template>
                            </select>
                            <button @click="fetchConnections" class="btn-sm btn-neutral" :disabled="loading">
                                <span x-show="!loading">Refresh</span>
                                <span x-show="loading">Loading...</span>
                            </button>
                        </div>
                    </template>
                </div>

                <template x-if="routerId && selectedSecrets.length > 0">
                    <div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <span class="text-sm text-red-700 dark:text-red-300" x-text="selectedSecrets.length + ' selected'"></span>
                        <button @click="bulkDisconnect" class="text-sm font-medium text-red-600 dark:text-red-400 hover:underline">Disconnect Selected</button>
                        <button @click="selectedSecrets = []" class="text-sm text-gray-500 hover:underline">Clear</button>
                    </div>
                </template>
            </div>
        </x-card>

        <template x-if="!routerId">
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Router Selected</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Please select a router to view active connections.</p>
                </div>
            </x-card>
        </template>

        <template x-if="routerId && connections.length === 0 && !loading">
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Active Connections</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">There are no users currently connected to this router.</p>
                </div>
            </x-card>
        </template>

        <template x-if="loading">
            <x-card>
                <div class="space-y-4 p-4">
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                </div>
            </x-card>
        </template>

        <template x-if="routerId && connections.length > 0 && !loading">
            <div class="admin-panel">
                <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Showing <span x-text="((currentPage - 1) * perPage) + 1"></span> to <span x-text="Math.min(currentPage * perPage, connections.length)"></span> of <span x-text="connections.length"></span>
                    </span>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500 dark:text-gray-400">Per page:</label>
                        <select x-model="perPage" @change="currentPage = 1" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table>
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left"><input type="checkbox" @change="toggleAll" :checked="selectedSecrets.length === connections.length" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"></th>
                                <th class="text-left">Username</th>
                                <th class="text-left">Service</th>
                                <th class="text-left">Caller ID</th>
                                <th class="text-left">Address</th>
                                <th class="text-left">Uptime</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="conn in paginatedConnections" :key="conn.id">
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <input type="checkbox" :value="conn.id" x-model="selectedSecrets" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <a :href="`/ppp-active/${conn.id}?router_id=${routerId}`" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline" x-text="conn.name"></a>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="conn.service || '-'"></td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="conn.caller_id || '-'"></td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="conn.address || '-'"></td>
                                    <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="conn.uptime || '-'"></td>
                                    <td class="whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            Online
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <button @click="disconnectUser(conn.id, conn.name)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" title="Disconnect">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1" class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded-xl hover:bg-slate-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Previous
                        </button>
                        <template x-for="page in pages" :key="page">
                            <button @click="currentPage = page" :class="page === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'" class="px-3 py-1 text-sm border rounded-xl transition-colors" x-text="page">
                            </button>
                        </template>
                        <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages" class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded-xl hover:bg-slate-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
        function pppActiveManager() {
            return {
                routerId: {{ $selectedRouter?->id ?? 'null' }},
                connections: @json($connections ?? []),
                statistics: { total_active: {{ $statistics['total_active'] ?? 0 }} },
                selectedSecrets: [],
                search: '',
                filterService: '',
                availableServices: [],
                loading: false,
                autoRefreshInterval: 0,
                autoRefreshTimer: null,
                currentPage: 1,
                perPage: 15,

                get paginatedConnections() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.connections.slice(start, start + this.perPage);
                },

                get totalPages() {
                    return Math.max(1, Math.ceil(this.connections.length / this.perPage));
                },

                get pages() {
                    const total = this.totalPages;
                    const current = this.currentPage;
                    const maxVisible = 5;
                    let start = Math.max(1, current - Math.floor(maxVisible / 2));
                    let end = Math.min(total, start + maxVisible - 1);
                    if (end - start + 1 < maxVisible) {
                        start = Math.max(1, end - maxVisible + 1);
                    }
                    const pages = [];
                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    return pages;
                },

                init() {
                    if (this.routerId) {
                        this.extractFilters();
                    }
                },

                extractFilters() {
                    const services = [...new Set(this.connections.map(c => c.service).filter(Boolean))];
                    this.availableServices = services;
                },

                selectRouter() {
                    if (!this.routerId) {
                        window.location.href = '{{ route("ppp-active.index") }}';
                        return;
                    }
                    window.location.href = '{{ route("ppp-active.index") }}?router_id=' + this.routerId;
                },

                async fetchConnections() {
                    if (!this.routerId) return;
                    this.loading = true;
                    this.currentPage = 1;
                    try {
                        const params = new URLSearchParams({
                            router_id: this.routerId,
                            search: this.search,
                            service: this.filterService,
                        });
                        const response = await fetch('{{ route("ppp-active.fetch") }}?' + params, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.connections = data.connections;
                            this.statistics = data.statistics;
                            this.availableServices = data.filters.services;
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                toggleAutoRefresh() {
                    if (this.autoRefreshTimer) {
                        clearInterval(this.autoRefreshTimer);
                        this.autoRefreshTimer = null;
                    }
                    if (this.autoRefreshInterval > 0) {
                        this.autoRefreshTimer = setInterval(() => {
                            this.fetchConnections();
                        }, this.autoRefreshInterval);
                    }
                },

                toggleAll(event) {
                    if (event.target.checked) {
                        this.selectedSecrets = this.paginatedConnections.map(c => c.id);
                    } else {
                        this.selectedSecrets = [];
                    }
                },

                async disconnectUser(userId, userName) {
                    const confirmed = await customConfirm(`Apakah Anda yakin ingin disconnect "${userName}"?`);
                    if (!confirmed) return;
                    try {
                        showToast('Disconnecting user...', 'info');
                        const response = await fetch('{{ route("ppp-active.disconnect") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ router_id: this.routerId, user_id: userId })
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            this.fetchConnections();
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                },

                async bulkDisconnect() {
                    const confirmed = await customConfirm(`Apakah Anda yakin ingin disconnect ${this.selectedSecrets.length} user?`);
                    if (!confirmed) return;
                    try {
                        showToast('Disconnecting users...', 'info');
                        const response = await fetch('{{ route("ppp-active.bulk-disconnect") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ router_id: this.routerId, user_ids: this.selectedSecrets })
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            this.selectedSecrets = [];
                            this.fetchConnections();
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
