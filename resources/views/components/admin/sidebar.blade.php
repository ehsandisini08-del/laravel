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
                    @if(Auth::user()->canAccessPackages())
                    <a href="{{ route('packages.index') }}" class="{{ request()->routeIs('packages.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Packages
                    </a>
                    @endif
                    @if(Auth::user()->canAccessAreas())
                    <a href="{{ route('areas.index') }}" class="{{ request()->routeIs('areas.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Areas
                    </a>
                    @endif
                </div>
            </div>

            @if(Auth::user()->canAccessBilling())
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Billing</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('billing.invoices.index') }}" class="{{ request()->routeIs('billing.invoices.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Invoice
                    </a>
                    <a href="{{ route('billing.cetak-invoice') }}" class="{{ request()->routeIs('billing.cetak-invoice') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" /></svg>
                        Cetak Invoice
                    </a>
                </div>
            </div>
            @endif

            @if(Auth::user()->canAccessNetwork() || Auth::user()->canAccessCpes())
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Network</p>
                <div class="mt-2 space-y-1">
                    @if(Auth::user()->canAccessNetwork())
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
                    @endif
                    @if(Auth::user()->canAccessCpes())
                    <a href="{{ route('cpes.index') }}" class="{{ request()->routeIs('cpes.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        CPE Devices
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- FTTH Monitoring --}}
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">FTTH</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('ftth.map') }}" class="{{ request()->routeIs('ftth.map') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        FTTH Map
                    </a>
                    <a href="{{ route('ftth.odc.index') }}" class="{{ request()->routeIs('ftth.odc.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        ODC
                    </a>
                    <a href="{{ route('ftth.odp.index') }}" class="{{ request()->routeIs('ftth.odp.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        ODP
                    </a>
                    <a href="{{ route('ftth.fiber.index') }}" class="{{ request()->routeIs('ftth.fiber.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.75 12H19.25M4.75 12a.25.25 0 01-.25-.25V9.75a.25.25 0 01.25-.25H9M4.75 12a.25.25 0 00-.25.25v2a.25.25 0 00.25.25H9M9 9.5V12m0 0v2.5M19.25 12a.25.25 0 00.25-.25V9.75a.25.25 0 00-.25-.25H15M19.25 12a.25.25 0 01.25.25v2a.25.25 0 01-.25.25H15M15 9.5V12m0 0v2.5"/></svg>
                        Jalur Fiber
                    </a>
                </div>
            </div>

            {{-- Teknisi --}}
            @if(Auth::user()->canAccessTeknisi())
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teknisi</p>
                <div class="mt-2 space-y-1">
                    @if(Auth::user()->canManageTeknisiTasks())
                    <a href="{{ route('teknisi.buat-tugas') }}" class="{{ request()->routeIs('teknisi.buat-tugas') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Buat Tugas
                    </a>
                    @endif
                    <a href="{{ route('teknisi.tugas-perbaikan') }}" class="{{ request()->routeIs('teknisi.tugas-perbaikan') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.017l4.085 1.203a1.5 1.5 0 011.026 1.838l-.66 2.246a1.5 1.5 0 01-1.838 1.026l-4.085-1.203a3.57 3.57 0 01-1.378-.855"/></svg>
                        Tugas Perbaikan
                    </a>
                    <a href="{{ route('teknisi.laporan-harian') }}" class="{{ request()->routeIs('teknisi.laporan-harian') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Laporan Harian
                    </a>
                    <a href="{{ route('teknisi.laporan-pemasangan') }}" class="{{ request()->routeIs('teknisi.laporan-pemasangan') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Laporan Pemasangan
                    </a>
                    <a href="{{ route('teknisi.pekerjaan') }}" class="{{ request()->routeIs('teknisi.pekerjaan') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg>
                        Pekerjaan
                    </a>
                </div>
            </div>
            @endif

            @if(Auth::user()->canAccessGudang())
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gudang</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('gudang.stok') }}" class="{{ request()->routeIs('gudang.stok') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Stok Barang
                    </a>
                    <a href="{{ route('gudang.barang.index') }}" class="{{ request()->routeIs('gudang.barang.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Barang
                    </a>
                    <a href="{{ route('gudang.kategori.index') }}" class="{{ request()->routeIs('gudang.kategori.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Kategori
                    </a>
                    <a href="{{ route('gudang.barang-masuk') }}" class="{{ request()->routeIs('gudang.barang-masuk') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                        </svg>
                        Barang Masuk
                    </a>
                    <a href="{{ route('gudang.barang-keluar') }}" class="{{ request()->routeIs('gudang.barang-keluar') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21v-12m0 0l-4 4m4-4l4 4M4 7V5a2 2 0 012-2h12a2 2 0 012 2v2" />
                        </svg>
                        Barang Keluar
                    </a>
                    <a href="{{ route('gudang.opname.index') }}" class="{{ request()->routeIs('gudang.opname.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Stok Opname
                    </a>
                    <a href="{{ route('gudang.riwayat') }}" class="{{ request()->routeIs('gudang.riwayat') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat Stok
                    </a>
                </div>
            </div>
            @endif

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
