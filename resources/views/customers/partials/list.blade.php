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
                @if(!Auth::user()->isAdminArea())
                <a href="{{ route('customers.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">Add Customer</a>
                @endif
            </div>
        </div>
    </x-card>
@else
    <div x-data="{ selected: [], allIds: @json($customers->pluck('id')->all()) }">
        @if(auth()->user()->canDeleteCustomers())
        <div class="mb-3 flex justify-end">
            <form method="POST" action="{{ route('customers.destroy-many') }}" x-data @submit.prevent="async () => { if(await customConfirm('Hapus '+selected.length+' customer terpilih beserta PPP secret-nya? Tindakan ini tidak dapat dibatalkan.', { confirmLabel: 'Ya, Hapus', confirmColor: 'red' })) $el.submit() }">
                @csrf @method('DELETE')
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit" x-show="selected.length > 0" class="app-btn-danger-ghost px-4 py-2 text-sm">
                    Hapus Terpilih (<span x-text="selected.length"></span>)
                </button>
            </form>
        </div>
        @endif

    <div class="admin-panel hidden md:block">
        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        @if(auth()->user()->canDeleteCustomers())
                        <th class="w-10">
                            <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" :checked="selected.length > 0 && selected.length === allIds.length" @change="$event.target.checked ? selected = [...allIds] : selected = []">
                        </th>
                        @endif
                        <th class="text-left">Code</th>
                        <th class="text-left">Name</th>
                        <th class="text-left">Phone</th>
                        <th class="text-left">Area</th>
                        <th class="text-left">ODP</th>
                        <th class="text-left">Package</th>
                        <th class="text-left">PPP Username</th>
                        <th class="text-left">Due Day</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Koneksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        @php
                            $pppConnection = $pppActiveConnections[$customer->router_id.':'.$customer->ppp_username] ?? null;
                        @endphp
                        <tr class="cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800" onclick="window.location='{{ route('customers.show', $customer) }}'">
                            @if(auth()->user()->canDeleteCustomers())
                            <td class="whitespace-nowrap" @click.stop>
                                <input type="checkbox" value="{{ $customer->id }}" x-model="selected" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            </td>
                            @endif
                            <td class="whitespace-nowrap">
                                <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $customer->customer_code }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('customers.show', $customer) }}" onclick="event.stopPropagation()" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $customer->name }}</a>
                            </td>
                            <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->phone }}</td>
                            <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->area?->name }}</td>
                            <td class="whitespace-nowrap text-sm">
                                @if($customer->odp)
                                    <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold px-2 py-0.5 rounded bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300">
                                        {{ $customer->odp->kode }}
                                        @if($customer->port_odp)<span class="text-gray-400">#{{ $customer->port_odp }}</span>@endif
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->package?->name }}</td>
                            <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $customer->ppp_username }}</td>
                            <td class="whitespace-nowrap text-sm text-gray-900 dark:text-white">Tgl {{ $customer->due_day }}</td>
                            <td class="whitespace-nowrap">
                                <x-badge variant="{{ $customer->status_color }}">{{ $customer->status_badge }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap">
                                @if($pppConnection)
                                    <x-badge variant="success">Online</x-badge>
                                    <span class="ml-1.5 text-xs text-gray-500 dark:text-gray-400" title="Uptime koneksi">{{ $pppConnection['uptime'] }}</span>
                                @else
                                    <x-badge variant="default">Offline</x-badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-3 md:hidden">
        @foreach($customers as $customer)
            @php
                $pppConnection = $pppActiveConnections[$customer->router_id.':'.$customer->ppp_username] ?? null;
            @endphp
            <a href="{{ route('customers.show', $customer) }}" class="block rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <div class="flex items-center justify-between gap-2">
                    @if(auth()->user()->canDeleteCustomers())
                    <input type="checkbox" value="{{ $customer->id }}" x-model="selected" @click.stop class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    @endif
                    <span class="font-mono text-xs font-medium text-gray-500 dark:text-gray-400">{{ $customer->customer_code }}</span>
                    <div class="flex items-center gap-1.5">
                        <x-badge variant="{{ $customer->status_color }}">{{ $customer->status_badge }}</x-badge>
                        @if($pppConnection)
                            <x-badge variant="success">Online</x-badge>
                        @else
                            <x-badge variant="default">Offline</x-badge>
                        @endif
                    </div>
                </div>
                <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $customer->address ?? '-' }}</p>
                @if($customer->odp)
                    <p class="mt-1 text-xs text-orange-600 dark:text-orange-400 font-medium flex items-center gap-1">
                        <span>ODP: {{ $customer->odp->kode }}</span>
                        @if($customer->port_odp)<span>(Port {{ $customer->port_odp }})</span>@endif
                    </p>
                @endif
                <p class="mt-1 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $customer->ppp_username }}</p>
                @if($pppConnection)
                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Aktif selama {{ $pppConnection['uptime'] }}</p>
                @endif
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
    </div>
@endif
