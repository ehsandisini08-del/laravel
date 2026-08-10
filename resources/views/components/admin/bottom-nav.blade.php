@php
$tabs = [
    [
        'label' => 'Beranda',
        'route' => 'dashboard',
        'active' => request()->routeIs('dashboard'),
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    ],
    [
        'label' => 'Pelanggan',
        'route' => 'customers.index',
        'active' => request()->routeIs('customers.*'),
        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    ],
    [
        'label' => 'Tagihan',
        'route' => 'billing.dashboard',
        'active' => request()->routeIs('billing.*'),
        'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
    ],
    [
        'label' => 'Jaringan',
        'route' => 'routers.index',
        'active' => request()->routeIs('routers.*'),
        'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
    ],
];
@endphp

<div x-data="{ menuOpen: false }" class="fixed inset-x-0 bottom-0 z-40 mx-auto max-w-7xl lg:hidden">
    {{-- Bottom navigation bar --}}
    <div class="border-t border-slate-100 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg dark:border-gray-700 dark:bg-gray-800/95">
        <div class="grid grid-cols-5 px-2 py-1.5">
            @foreach($tabs as $tab)
                <a href="{{ route($tab['route']) }}"
                   class="flex flex-col items-center gap-0.5 rounded-xl py-1.5 transition-colors {{ $tab['active'] ? 'text-[#2563eb] dark:text-blue-400' : 'text-slate-400 dark:text-gray-500' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $tab['active'] ? '2.2' : '1.7' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                    </svg>
                    <span class="text-[10px] font-semibold leading-none">{{ $tab['label'] }}</span>
                </a>
            @endforeach

            {{-- Tab "Lainnya" --}}
            <button type="button" @click="menuOpen = true"
                class="flex flex-col items-center gap-0.5 rounded-xl py-1.5 transition-colors {{ request()->routeIs('packages.*', 'areas.*', 'ppp-*', 'whatsapp.*', 'users.*', 'logs.*', 'settings.*', 'update.*', 'monitoring.*', 'backup.*', 'customers.import.form') ? 'text-[#2563eb] dark:text-blue-400' : 'text-slate-400 dark:text-gray-500' }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('packages.*', 'areas.*', 'ppp-*', 'whatsapp.*', 'users.*', 'logs.*', 'settings.*', 'update.*', 'monitoring.*', 'backup.*', 'customers.import.form') ? '2.2' : '1.7' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                </svg>
                <span class="text-[10px] font-semibold leading-none">Lainnya</span>
            </button>
        </div>
    </div>

    {{-- Background overlay --}}
    <div x-show="menuOpen" @click="menuOpen = false" x-transition.opacity
        class="fixed inset-0 z-[60] bg-slate-900/50 backdrop-blur-sm lg:hidden" style="display: none;"></div>

    {{-- Bottom sheet menu --}}
    <div x-show="menuOpen" @keydown.escape.window="menuOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed inset-x-0 bottom-0 z-[70] mx-auto max-h-[85vh] overflow-y-auto rounded-t-3xl bg-white pb-[env(safe-area-inset-bottom)] shadow-2xl dark:bg-gray-800"
        style="display: none;"
    >
        <div class="sticky top-0 z-10 -mx-0 flex items-center justify-between border-b border-slate-100 bg-white/95 px-5 py-4 backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Menu</h2>
            <button type="button" @click="menuOpen = false" class="icon-btn -m-2 p-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="space-y-5 px-5 py-5">
            {{-- Management --}}
            <div>
                <p class="app-label mb-2">Management</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('customers.index') }}" class="btn-soft {{ request()->routeIs('customers.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Pelanggan</a>
                    <a href="{{ route('packages.index') }}" class="btn-soft {{ request()->routeIs('packages.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Paket</a>
                    <a href="{{ route('areas.index') }}" class="btn-soft {{ request()->routeIs('areas.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Area</a>
                    <a href="{{ route('customers.import.form') }}" class="btn-soft {{ request()->routeIs('customers.import.form') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Import</a>
                </div>
            </div>

            {{-- Billing --}}
            <div>
                <p class="app-label mb-2">Billing</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('billing.dashboard') }}" class="btn-soft {{ request()->routeIs('billing.dashboard') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Dashboard</a>
                    <a href="{{ route('billing.invoices.index') }}" class="btn-soft {{ request()->routeIs('billing.invoices.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Invoice</a>
                </div>
            </div>

            {{-- Network --}}
            <div>
                <p class="app-label mb-2">Network</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('routers.index') }}" class="btn-soft {{ request()->routeIs('routers.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Router</a>
                    <a href="{{ route('ppp-secrets.index') }}" class="btn-soft {{ request()->routeIs('ppp-secrets.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">PPP Secrets</a>
                    <a href="{{ route('ppp-profiles.index') }}" class="btn-soft {{ request()->routeIs('ppp-profiles.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">PPP Profiles</a>
                    <a href="{{ route('ppp-active.index') }}" class="btn-soft {{ request()->routeIs('ppp-active.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Active Conn</a>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div>
                <p class="app-label mb-2">WhatsApp</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('whatsapp.dashboard') }}" class="btn-soft {{ request()->routeIs('whatsapp.dashboard') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Dashboard</a>
                    <a href="{{ route('whatsapp.devices.index') }}" class="btn-soft {{ request()->routeIs('whatsapp.devices.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Device</a>
                    <a href="{{ route('whatsapp.templates.index') }}" class="btn-soft {{ request()->routeIs('whatsapp.templates.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Template</a>
                    <a href="{{ route('whatsapp.messages.index') }}" class="btn-soft {{ request()->routeIs('whatsapp.messages.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Pesan</a>
                    <a href="{{ route('whatsapp.broadcast.create') }}" class="btn-soft {{ request()->routeIs('whatsapp.broadcast.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Broadcast</a>
                </div>
            </div>

            {{-- Administration --}}
            <div>
                <p class="app-label mb-2">Administrasi</p>
                <div class="grid grid-cols-2 gap-2">
                    @if(Auth::user()->canManageUsers())
                        <a href="{{ route('users.index') }}" class="btn-soft {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">User</a>
                    @endif
                    <a href="{{ route('logs.index') }}" class="btn-soft {{ request()->routeIs('logs.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Log</a>
                    <a href="{{ route('backup.index') }}" class="btn-soft {{ request()->routeIs('backup.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Backup</a>
                    @if(Auth::user()->isDeveloper())
                        <a href="{{ route('settings.index') }}" class="btn-soft {{ request()->routeIs('settings.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Pengaturan</a>
                        <a href="{{ route('update.index') }}" class="btn-soft {{ request()->routeIs('update.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Update</a>
                        <a href="{{ route('monitoring.jobs') }}" class="btn-soft {{ request()->routeIs('monitoring.*') ? 'bg-blue-600 text-white' : '' }} justify-center px-3 py-2.5 text-xs">Job Monitor</a>
                    @endif
                </div>
            </div>

            {{-- Akun & Keluar --}}
            <div class="flex gap-2 pt-1">
                <a href="{{ route('profile.edit') }}" class="app-btn-ghost flex-1 px-4 py-2.5 text-sm">Profil</a>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="app-btn-danger-ghost w-full px-4 py-2.5 text-sm">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</div>