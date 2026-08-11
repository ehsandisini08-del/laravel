<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Customers</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage ISP customer data</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-3 md:flex md:items-center md:gap-3">
                <button type="button" onclick="reconcileCustomers()" class="btn-sm justify-center whitespace-nowrap bg-green-600 text-white hover:bg-green-700 md:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync ke MikroTik
                </button>
                <a href="{{ route('customers.import.form') }}" class="btn-sm btn-neutral justify-center whitespace-nowrap md:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import
                </a>
                <a href="{{ route('customers.create') }}" class="btn-sm justify-center whitespace-nowrap bg-blue-600 text-white hover:bg-blue-700 md:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Customer
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
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

        <x-card>
            <form method="GET" x-data class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, code, phone, or PPP username..." autocomplete="off" @input.debounce.500ms="$el.form.requestSubmit()" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <select name="area_id" @change="$el.form.requestSubmit()" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Areas</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                    @endforeach
                </select>
                <select name="router_id" @change="$el.form.requestSubmit()" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Routers</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                    @endforeach
                </select>
                <select name="status" @change="$el.form.requestSubmit()" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Isolated" {{ request('status') === 'Isolated' ? 'selected' : '' }}>Isolated</option>
                    <option value="Suspended" {{ request('status') === 'Suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="Terminated" {{ request('status') === 'Terminated' ? 'selected' : '' }}>Terminated</option>
                </select>
            </form>
        </x-card>

        @if($customers->isEmpty())
            <x-card>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Customers</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if(request()->anyFilled(['search', 'area_id', 'router_id', 'status']))
                            Tidak ada customer yang cocok dengan pencarian atau filter.
                        @else
                            Get started by adding a new customer.
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('customers.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">Add Customer</a>
                    </div>
                </div>
            </x-card>
        @else
            <div class="admin-panel hidden md:block">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Code</th>
                                <th class="text-left">Name</th>
                                <th class="text-left">Phone</th>
                                <th class="text-left">Area</th>
                                <th class="text-left">Router</th>
                                <th class="text-left">Package</th>
                                <th class="text-left">PPP Username</th>
                                <th class="text-left">Due Day</th>
                                <th class="text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                                <tr class="cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800" onclick="window.location='{{ route('customers.show', $customer) }}'">
                                    <td class="whitespace-nowrap">
                                        <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $customer->customer_code }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('customers.show', $customer) }}" onclick="event.stopPropagation()" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $customer->name }}</a>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->phone }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->area?->name }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->router?->name }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->package?->name }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->ppp_username }}</td>
                                    <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">Tgl {{ $customer->due_day }}</td>
                                    <td class="whitespace-nowrap">
                                        <x-badge variant="{{ $customer->status_color }}">{{ $customer->status_badge }}</x-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-3 md:hidden">
                @foreach($customers as $customer)
                    <a href="{{ route('customers.show', $customer) }}" class="block rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-medium text-gray-500 dark:text-gray-400">{{ $customer->customer_code }}</span>
                            <x-badge variant="{{ $customer->status_color }}">{{ $customer->status_badge }}</x-badge>
                        </div>
                        <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $customer->address ?? '-' }}</p>
                        <p class="mt-1.5 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $customer->ppp_username }}</p>
                    </a>
                @endforeach
            </div>
            <div class="mt-4">{{ $customers->links() }}</div>
        @endif
    </div>

    @push('scripts')
    <script>
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
