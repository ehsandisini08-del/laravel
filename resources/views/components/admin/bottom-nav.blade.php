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
        'route' => 'billing.invoices.index',
        'active' => request()->routeIs('billing.invoices.*'),
        'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
    ],
    [
        'label' => 'Router',
        'route' => 'routers.index',
        'active' => request()->routeIs('routers.*'),
        'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
    ],
    [
        'label' => 'FTTH',
        'route' => 'ftth.map',
        'active' => request()->routeIs('ftth.*'),
        'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
    ],
];

if (Auth::user()->isAdminArea()) {
    $tabs = array_values(array_filter($tabs, fn ($tab) => $tab['route'] !== 'routers.index'));
} elseif (Auth::user()->isTeknisi()) {
    $tabs = array_values(array_filter($tabs, fn ($tab) => ! in_array($tab['route'], ['billing.invoices.index', 'routers.index'], true)));
}
@endphp

<div class="fixed inset-x-0 bottom-0 z-40 mx-auto max-w-7xl lg:hidden">
    {{-- Bottom navigation bar --}}
    <div class="border-t border-slate-100 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg dark:border-gray-700 dark:bg-gray-800/95">
        <div class="flex px-2 py-1.5">
            @foreach($tabs as $tab)
                <a href="{{ route($tab['route']) }}"
                   class="flex flex-1 flex-col items-center gap-0.5 rounded-xl py-1.5 transition-colors {{ $tab['active'] ? 'text-[#2563eb] dark:text-blue-400' : 'text-slate-400 dark:text-gray-500' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $tab['active'] ? '2.2' : '1.7' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                    </svg>
                    <span class="text-[10px] font-semibold leading-none">{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>