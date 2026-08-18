@php
    $user = Auth::user();
@endphp

<div class="space-y-5 lg:hidden">
    {{-- Management --}}
    <div class="app-card p-5">
        <div class="mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h2 class="app-label">Management</h2>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <x-admin.menu-tile
                label="Pelanggan"
                href="{{ route('customers.index') }}"
                icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                color="bg-blue-600"
                :active="request()->routeIs('customers.*')"
            />
            <x-admin.menu-tile
                label="Paket"
                href="{{ route('packages.index') }}"
                icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                color="bg-purple-600"
                :active="request()->routeIs('packages.*')"
            />
            <x-admin.menu-tile
                label="Area"
                href="{{ route('areas.index') }}"
                icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"
                color="bg-teal-600"
                :active="request()->routeIs('areas.*')"
            />
            <x-admin.menu-tile
                label="Import"
                href="{{ route('customers.import.form') }}"
                icon="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                color="bg-cyan-600"
                :active="request()->routeIs('customers.import.form')"
            />
        </div>
    </div>

    {{-- Billing --}}
    <div class="app-card p-5">
        <div class="mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            <h2 class="app-label">Billing</h2>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <x-admin.menu-tile
                label="Invoice"
                href="{{ route('billing.invoices.index') }}"
                icon="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                color="bg-emerald-600"
                :active="request()->routeIs('billing.invoices.*')"
            />
        </div>
    </div>

    {{-- Network --}}
    <div class="app-card p-5">
        <div class="mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
            </svg>
            <h2 class="app-label">Network</h2>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <x-admin.menu-tile
                label="Router"
                href="{{ route('routers.index') }}"
                icon="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"
                color="bg-indigo-600"
                :active="request()->routeIs('routers.*')"
            />
            <x-admin.menu-tile
                label="PPP Secrets"
                href="{{ route('ppp-secrets.index') }}"
                icon="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                color="bg-amber-600"
                :active="request()->routeIs('ppp-secrets.*')"
            />
            <x-admin.menu-tile
                label="PPP Profiles"
                href="{{ route('ppp-profiles.index') }}"
                icon="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"
                color="bg-orange-600"
                :active="request()->routeIs('ppp-profiles.*')"
            />
            <x-admin.menu-tile
                label="Active Conn"
                href="{{ route('ppp-active.index') }}"
                icon="M13 10V3L4 14h7v7l9-11h-7z"
                color="bg-red-600"
                :active="request()->routeIs('ppp-active.*')"
            />
        </div>
    </div>

    {{-- WhatsApp --}}
    <div>
        <x-admin.menu-tile
            label="WhatsApp"
            href="{{ route('whatsapp.menu') }}"
            icon="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
            color="bg-green-500"
            wide
            :active="request()->routeIs('whatsapp.*')"
        />
    </div>

    {{-- Administrasi --}}
    <div class="app-card p-5">
        <div class="mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h2 class="app-label">Administrasi</h2>
        </div>
        <div class="grid grid-cols-2 gap-3">
            @if($user->canManageUsers())
                <x-admin.menu-tile
                    label="User"
                    href="{{ route('users.index') }}"
                    icon="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                    color="bg-slate-600"
                    :active="request()->routeIs('users.*')"
                />
                <x-admin.menu-tile
                    label="Unlock Akun"
                    href="{{ route('unlock-accounts.index') }}"
                    icon="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                    color="bg-amber-600"
                    :active="request()->routeIs('unlock-accounts.*')"
                />
            @endif
            <x-admin.menu-tile
                label="Log"
                href="{{ route('logs.index') }}"
                icon="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                color="bg-zinc-600"
                :active="request()->routeIs('logs.*')"
            />
            <x-admin.menu-tile
                label="Backup"
                href="{{ route('backup.index') }}"
                icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                color="bg-stone-600"
                :active="request()->routeIs('backup.*')"
            />
            @if($user->isDeveloper())
                <x-admin.menu-tile
                    label="Pengaturan"
                    href="{{ route('settings.index') }}"
                    icon="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    color="bg-yellow-500"
                    :active="request()->routeIs('settings.*')"
                />
                <x-admin.menu-tile
                    label="Update"
                    href="{{ route('update.index') }}"
                    icon="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                    color="bg-sky-600"
                    :active="request()->routeIs('update.*')"
                />
                <x-admin.menu-tile
                    label="Job Monitor"
                    href="{{ route('monitoring.jobs') }}"
                    icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"
                    color="bg-violet-600"
                    :active="request()->routeIs('monitoring.*')"
                />
            @endif
        </div>
    </div>
</div>