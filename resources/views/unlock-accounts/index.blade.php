<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Unlock Akun</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Keluarkan sesi akun yang masih aktif, misalnya setelah aplikasi dihapus tanpa logout terlebih dahulu.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert> @endif

        <div x-data="{ activeTab: 'users' }">
            <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
                <button type="button" @click="activeTab = 'users'" :class="activeTab === 'users' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'" class="-mb-px border-b-2 py-3 text-sm font-semibold transition-colors">
                    Admin ({{ $users->count() }})
                </button>
                <button type="button" @click="activeTab = 'customers'" :class="activeTab === 'customers' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'" class="-mb-px border-b-2 py-3 text-sm font-semibold transition-colors">
                    Customer ({{ $customers->count() }})
                </button>
            </div>

            <div x-show="activeTab === 'users'" class="pt-6">
                @if($users->isEmpty())
                    <x-card><div class="text-center py-12"><p class="text-gray-500">Tidak ada sesi admin yang aktif.</p></div></x-card>
                @else
                    <x-card>
                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="text-left">Nama</th>
                                        <th class="text-left">Email</th>
                                        <th class="text-left">Role</th>
                                        <th class="text-left">IP</th>
                                        <th class="text-left">Aktivitas Terakhir</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        @php $session = $sessionInfo->get($user->active_session_id); @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
                                                    @if($user->id === Auth::id())
                                                        <span class="text-xs text-gray-400">(Anda)</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                                            <td class="px-4 py-3"><x-badge variant="{{ $user->roleColor() }}">{{ $user->roleLabel() }}</x-badge></td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $session?->ip_address ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $session ? \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() : '-' }}</td>
                                            <td class="px-4 py-3 text-right text-sm">
                                                <form method="POST" action="{{ route('unlock-accounts.unlock-user', $user) }}" x-data="{ confirmed: false }" @submit.prevent="async () => { if(!confirmed && await customConfirm('Keluarkan sesi user {{ $user->name }}? Akun tersebut harus login kembali.')) { confirmed = true; $el.submit(); } }">
                                                    @csrf
                                                    <button type="submit" class="btn-sm bg-amber-600 text-white hover:bg-amber-500">Unlock</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                @endif
            </div>

            <div x-show="activeTab === 'customers'" class="pt-6">
                @if($customers->isEmpty())
                    <x-card><div class="text-center py-12"><p class="text-gray-500">Tidak ada sesi customer yang aktif.</p></div></x-card>
                @else
                    <x-card>
                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="text-left">Nama</th>
                                        <th class="text-left">Kode Customer</th>
                                        <th class="text-left">Telepon</th>
                                        <th class="text-left">IP</th>
                                        <th class="text-left">Aktivitas Terakhir</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customers as $customer)
                                        @php $session = $sessionInfo->get($customer->active_session_id); @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-semibold text-sm">
                                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $customer->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-400">{{ $customer->customer_code }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $customer->phone ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $session?->ip_address ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $session ? \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() : '-' }}</td>
                                            <td class="px-4 py-3 text-right text-sm">
                                                <form method="POST" action="{{ route('unlock-accounts.unlock-customer', $customer) }}" x-data="{ confirmed: false }" @submit.prevent="async () => { if(!confirmed && await customConfirm('Keluarkan sesi customer {{ $customer->name }}? Customer tersebut harus login kembali.')) { confirmed = true; $el.submit(); } }">
                                                    @csrf
                                                    <button type="submit" class="btn-sm bg-amber-600 text-white hover:bg-amber-500">Unlock</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>