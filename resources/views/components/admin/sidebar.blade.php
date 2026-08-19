<aside class="fixed inset-y-0 left-0 z-50 hidden w-64 flex-col bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 lg:flex">
    <div class="flex h-full flex-col">
        <div class="flex h-16 shrink-0 items-center border-b border-gray-200 dark:border-gray-700 px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-semibold text-gray-900 dark:text-white">Admin</span>
            </a>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4 overflow-y-auto">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Management</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Customers
                    </a>
                    <a href="{{ route('packages.index') }}" class="{{ request()->routeIs('packages.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Packages
                    </a>
                    <a href="{{ route('areas.index') }}" class="{{ request()->routeIs('areas.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Areas
                    </a>
                </div>
            </div>

            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Billing</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('billing.invoices.index') }}" class="{{ request()->routeIs('billing.invoices.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Invoice
                    </a>
                </div>
            </div>

            @if(Auth::user()->canAccessNetwork())
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Network</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('routers.index') }}" class="{{ request()->routeIs('routers.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                        Routers
                    </a>
                    <a href="{{ route('ppp-secrets.index') }}" class="{{ request()->routeIs('ppp-secrets.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        PPP Secrets
                    </a>
                    <a href="{{ route('ppp-profiles.index') }}" class="{{ request()->routeIs('ppp-profiles.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                        PPP Profiles
                    </a>
                    <a href="{{ route('ppp-active.index') }}" class="{{ request()->routeIs('ppp-active.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Active Connections
                    </a>
                    <a href="{{ route('cpes.index') }}" class="{{ request()->routeIs('cpes.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        CPE Devices
                    </a>
                    <a href="{{ route('infrastruktur.index') }}" class="{{ request()->routeIs('infrastruktur.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M5 6l7-3 7 3M4 10h16v11H4V10z" />
                        </svg>
                        Infrastruktur
                    </a>
                </div>
            </div>
            @endif

            <div class="pt-4" x-data="{ open: @json(request()->routeIs('gudang.*')) }">
                <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span class="flex-1 text-left">Gudang</span>
                    <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-collapse class="mt-1 ml-6 space-y-1">
                    <a href="{{ route('gudang.stok') }}" class="{{ request()->routeIs('gudang.stok') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                        Stok Barang
                    </a>
                    <a href="{{ route('gudang.barang-masuk') }}" class="{{ request()->routeIs('gudang.barang-masuk') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                        Barang Masuk
                    </a>
                    <a href="{{ route('gudang.barang-keluar') }}" class="{{ request()->routeIs('gudang.barang-keluar') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                        Barang Keluar
                    </a>
                </div>
            </div>

            @if(Auth::user()->canAccessAdministration())
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Administration</p>
                <div class="mt-2 space-y-1">
                    @if(Auth::user()->isDeveloper())
                    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                    </a>
                    @endif
                    @if(Auth::user()->isDeveloper())
                    <a href="{{ route('update.index') }}" class="{{ request()->routeIs('update.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Update Aplikasi
                    </a>
                    @endif
                    @if(Auth::user()->isDeveloper())
                    <a href="{{ route('monitoring.jobs') }}" class="{{ request()->routeIs('monitoring.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Job Monitor
                    </a>
                    @endif
                    @if(Auth::user()->canManageUsers())
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        User Management
                    </a>
                    @endif
                    @if(Auth::user()->canManageUsers())
                    <a href="{{ route('unlock-accounts.index') }}" class="{{ request()->routeIs('unlock-accounts.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Unlock Akun
                    </a>
                    @endif
                    <a href="{{ route('whatsapp.dashboard') }}" class="{{ request()->routeIs('whatsapp.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        WhatsApp Gateway
                    </a>
                    @if(request()->routeIs('whatsapp.*'))
                    <div class="ml-6 space-y-1">
                        <a href="{{ route('whatsapp.dashboard') }}" class="{{ request()->routeIs('whatsapp.dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('whatsapp.devices.index') }}" class="{{ request()->routeIs('whatsapp.devices.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                            Devices
                        </a>
                        <a href="{{ route('whatsapp.templates.index') }}" class="{{ request()->routeIs('whatsapp.templates.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                            Templates
                        </a>
                        <a href="{{ route('whatsapp.messages.index') }}" class="{{ request()->routeIs('whatsapp.messages.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                            Messages
                        </a>
                        <a href="{{ route('whatsapp.broadcast.create') }}" class="{{ request()->routeIs('whatsapp.broadcast.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                            Broadcast
                        </a>
                        <a href="{{ route('whatsapp.settings.index') }}" class="{{ request()->routeIs('whatsapp.settings.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                            Settings
                        </a>
                    </div>
                    @endif
                    <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Logs
                    </a>
                    <a href="{{ route('backup.index') }}" class="{{ request()->routeIs('backup.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Backup
                    </a>
                </div>
            </div>
            @endif
        </nav>

        <div class="border-t border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>
        </div>
    </div>
</aside>
