<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
    $brand = \App\Models\Setting::get('company_name') ?: (\App\Models\Setting::get('app_name') ?: config('app.name'));
    @endphp
    <title>{{ $brand }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-200">
    <div class="app-shell pb-24">
        @if(session('success'))
            <x-alert variant="success" dismissible class="mx-4 mt-4">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible class="mx-4 mt-4">{{ session('error') }}</x-alert>
        @endif

        <main class="px-4 py-6">
            {{ $slot }}
        </main>
    </div>

    <!-- Bottom Navigation -->
    @auth('customer')
    <nav class="fixed inset-x-0 bottom-0 z-50 mx-auto max-w-md">
        <div class="border-t border-slate-100 bg-white/95 px-2 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg">
            <div class="grid grid-cols-4 gap-1 py-2">
                <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'text-[#2563eb]' : 'text-slate-400' }} flex flex-col items-center gap-1 py-1.5 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('portal.dashboard') ? '2.2' : '1.7' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span class="text-[11px] font-semibold">Beranda</span>
                </a>
                <a href="{{ route('portal.bills') }}" class="{{ request()->routeIs('portal.bills') ? 'text-[#2563eb]' : 'text-slate-400' }} flex flex-col items-center gap-1 py-1.5 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('portal.bills') ? '2.2' : '1.7' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                    <span class="text-[11px] font-medium">Tagihan</span>
                </a>
                <a href="{{ route('portal.invoices.index') }}" class="{{ request()->routeIs('portal.invoices.*') ? 'text-[#2563eb]' : 'text-slate-400' }} flex flex-col items-center gap-1 py-1.5 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('portal.invoices.*') ? '2.2' : '1.7' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-6-8h6m8-6H3a1 1 0 00-1 1v14a1 1 0 001 1h18a1 1 0 001-1V3a1 1 0 00-1-1z" />
                    </svg>
                    <span class="text-[11px] font-medium">Riwayat</span>
                </a>
                <a href="{{ route('portal.account') }}" class="{{ request()->routeIs('portal.account') ? 'text-[#2563eb]' : 'text-slate-400' }} flex flex-col items-center gap-1 py-1.5 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('portal.account') ? '2.2' : '1.7' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-[11px] font-medium">Akun</span>
                </a>
            </div>
        </div>
    </nav>
    @endauth
</body>
</html>