<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
    $brand = \App\Models\Setting::get('company_name') ?: (\App\Models\Setting::get('app_name') ?: config('app.name'));
@endphp
    <title>{{ $brand }} - Portal Pelanggan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen">
    <header class="bg-white shadow-sm border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-9 w-9 shrink-0 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ $brand }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Portal Pelanggan</p>
                    </div>
                </div>
                <nav class="flex items-center gap-1 text-sm">
                    <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg px-3 py-2 font-medium transition-colors">Beranda</a>
                    <a href="{{ route('portal.invoices.index') }}" class="{{ request()->routeIs('portal.invoices.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg px-3 py-2 font-medium transition-colors">Riwayat Tagihan</a>
                    <form method="POST" action="{{ route('portal.logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg px-3 py-2 font-medium transition-colors">Keluar</button>
                    </form>
                </nav>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success')) <x-alert variant="success" dismissible class="mb-6">{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert variant="danger" dismissible class="mb-6">{{ session('error') }}</x-alert> @endif

        {{ $slot }}
    </main>
</body>
</html>
