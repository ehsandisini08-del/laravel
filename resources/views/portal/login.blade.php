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
    <title>{{ $brand }} - Masuk</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-200">
    <div class="app-shell flex min-h-screen flex-col justify-between pb-10">
        <!-- Header -->
        <div class="bg-gradient-to-b from-blue-600 to-blue-700 px-6 pb-16 pt-12 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white/15 backdrop-blur-sm">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">{{ $brand }}</h1>
            <p class="mt-1 text-sm text-blue-100">Portal Pelanggan</p>
        </div>

        <!-- Form -->
        <div class="-mt-8 px-5">
            <x-app-card>
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <p class="text-sm text-slate-500">Masuk menggunakan <strong>Kode Customer</strong> dan <strong>Password</strong> yang diberikan oleh admin.</p>

                <form method="POST" action="{{ route('portal.login') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="customer_code" value="Kode Customer" />
                        <x-text-input id="customer_code" class="app-input mt-2 text-center tracking-[0.3em]" type="text" name="customer_code" :value="old('customer_code')" required autofocus maxlength="6" autocomplete="username" placeholder="000000" />
                        <x-input-error :messages="$errors->get('customer_code')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Password (3 digit)" />
                        <x-text-input id="password" class="app-input mt-2 text-center tracking-[0.3em]" type="password" name="password" required maxlength="3" autocomplete="current-password" placeholder="&bull;&bull;&bull;" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <button type="submit" class="app-btn-primary w-full py-3.5 text-base">
                        Masuk
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                    </button>
                </form>
            </x-app-card>

            <p class="mt-6 text-center text-xs text-slate-400">Gunakan aplikasi ini untuk melihat tagihan & membayar internet Anda.</p>
        </div>
    </div>
</body>
</html>