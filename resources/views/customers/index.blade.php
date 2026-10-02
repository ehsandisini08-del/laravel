<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="hidden md:block">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Customers</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage ISP customer data</p>
            </div>
            <div class="grid grid-cols-3 gap-2 md:hidden">
                @if(!Auth::user()->isAdminArea() && !Auth::user()->isTeknisi())
                <button type="button" onclick="reconcileCustomers()" class="btn-sm justify-center bg-green-600 text-white hover:bg-green-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Sync</span>
                </button>
                <a href="{{ route('customers.import.form') }}" class="btn-sm btn-neutral justify-center">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import
                </a>
                @endif
                @if(!Auth::user()->isAdminArea())
                <a href="{{ route('customers.create') }}" class="btn-sm justify-center bg-blue-600 text-white hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add</span>
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="hidden lg:grid grid-cols-4 gap-4">
            <div class="rounded-xl border border-slate-100 bg-white text-slate-900 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 transition-all hover:ring-2 hover:ring-blue-400">
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/30">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pelanggan Total</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $totalCustomersCount }} <span class="text-xs font-normal text-gray-400">pelanggan</span></p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white text-slate-900 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 transition-all hover:ring-2 hover:ring-yellow-400">
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yellow-50 dark:bg-yellow-900/30">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pelanggan Isolir</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $isolatedCustomersCount }} <span class="text-xs font-normal text-gray-400">pelanggan</span></p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white text-slate-900 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 transition-all hover:ring-2 hover:ring-gray-400">
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700/50">
                        <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0M12 9v4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pelanggan Offline</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $offlineCustomersCount }} <span class="text-xs font-normal text-gray-400">pelanggan</span></p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white text-slate-900 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 transition-all hover:ring-2 hover:ring-red-400">
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/30">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pelanggan Tidak Aktif</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $inactiveCustomersCount }} <span class="text-xs font-normal text-gray-400">pelanggan</span></p>
                    </div>
                </div>
            </div>
        </div>

        @php $canSyncImport = !Auth::user()->isAdminArea() && !Auth::user()->isTeknisi(); @endphp
        @php $canAddCustomer = !Auth::user()->isAdminArea(); @endphp

        @if($canSyncImport || $canAddCustomer)
        <div class="hidden md:flex items-center gap-3 rounded-lg border border-slate-100 bg-white px-5 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @if($canSyncImport)
            <button type="button" onclick="reconcileCustomers()" class="btn-sm bg-green-600 text-white hover:bg-green-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync ke MikroTik
            </button>
            <a href="{{ route('customers.import.form') }}" class="btn-sm btn-neutral">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import
            </a>
            @endif
            @if($canAddCustomer)
            <a href="{{ route('customers.create') }}" class="btn-sm bg-blue-600 text-white hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Customer
            </a>
            @endif
        </div>
        @endif

        <div class="flex flex-col gap-6">
        <div class="order-2 lg:order-1">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif
        @if(session('portal_password'))
            <x-alert variant="warning" dismissible>
                <p class="font-semibold">Password Portal: <span class="font-mono text-lg tracking-widest">{{ session('portal_password') }}</span></p>
                <p class="mt-1 text-xs">Tampil hanya sekali. Berikan kepada pelanggan untuk login di portal.</p>
            </x-alert>
        @endif
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('success')) showToast('{{ session('success') }}', 'success'); @endif
                @if(session('error')) showToast('{{ session('error') }}', 'error'); @endif
            });
        </script>
        </div>

        <div class="order-1 lg:order-2">
        <x-card>
            <form method="GET" x-data="{ filterOpen: false }" @submit.prevent="loadResults()">
                <div class="flex flex-col gap-3 md:flex-row">
                    <div class="flex flex-1 gap-2">
                        <input type="text" name="search" id="search-customer" value="{{ request('search') }}" placeholder="Search name, code, phone, or PPP username..." autocomplete="off" enterkeyhint="search" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" @click="filterOpen = !filterOpen" class="btn-sm btn-neutral flex shrink-0 items-center gap-2 whitespace-nowrap {{ request()->anyFilled(['router_id', 'area_id', 'status']) ? 'text-blue-600' : '' }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                            </svg>
                            Filter
                            <svg class="h-4 w-4 transition-transform" :class="filterOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div x-show="filterOpen" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <select name="router_id" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Routers</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                        @endforeach
                    </select>
                    <select name="area_id" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Areas</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Isolated" {{ request('status') === 'Isolated' ? 'selected' : '' }}>Isolir</option>
                        <option value="Suspended" {{ request('status') === 'Suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="Terminated" {{ request('status') === 'Terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                </div>

                <div x-show="filterOpen" class="mt-4 flex items-center justify-end gap-3">
                    <button type="button" @click="$el.closest('form').reset(); loadResults()" class="btn-sm btn-neutral">Reset</button>
                    <button type="submit" class="btn-sm bg-blue-600 text-white">Terapkan</button>
                </div>
            </form>
        </x-card>
        </div>
        </div>

        <div id="customer-results">
            @include('customers.partials.list', ['customers' => $customers, 'pppActiveConnections' => $pppActiveConnections])
        </div>
    </div>

    @push('scripts')
    <script>
        const searchInput = document.getElementById('search-customer');
        const resultsEl = document.getElementById('customer-results');
        const filterForm = searchInput.closest('form');
        let searchTimer = null;

        async function loadResults() {
            const params = new URLSearchParams(new FormData(filterForm));
            const url = location.pathname + '?' + params.toString();

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                resultsEl.innerHTML = await response.text();
                history.replaceState(null, '', url);
            } catch (error) {
                console.error('Customer search error:', error);
            }
        }

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadResults, 500);
        });

        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchInput.blur();
                loadResults();
            }
        });

        async function reconcileCustomers() {
            const routerId = '{{ request('router_id') }}';

            // Direct confirmation without customConfirm
            if (!await customConfirm('Sinkronkan data customer ke MikroTik?\n\nSecret di router akan disesuaikan dengan data customer (comment, profile, password, status disabled).\nSecret yang belum ada akan dibuatkan.', { confirmLabel: 'Ya, Sinkronkan', confirmColor: 'blue' })) {
                return;
            }

            showToast('Menyinkronkan customer ke MikroTik...', 'info');

            try {
                const response = await fetch('{{ route('customers.reconcile') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ router_id: routerId || null })
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Reconcile error:', error);
                showToast('Gagal sinkronisasi: ' + error.message, 'error');
            }
        }
    </script>
    @endpush
</x-admin-layout>
