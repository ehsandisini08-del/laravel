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
<body class="font-sans antialiased bg-gray-50">
    <div class="flex min-h-screen">
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-11 w-11 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Portal Pelanggan</h2>
                            <p class="text-sm text-gray-500">{{ $brand }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-gray-600">Masuk menggunakan <strong>Kode Customer</strong> dan <strong>Password</strong> yang diberikan oleh admin.</p>
                </div>

                <div class="mt-8">
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('portal.login') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="customer_code" value="Kode Customer" />
                            <x-text-input id="customer_code" class="block mt-1 w-full text-center tracking-widest" type="text" name="customer_code" :value="old('customer_code')" required autofocus maxlength="6" autocomplete="username" placeholder="000000" />
                            <x-input-error :messages="$errors->get('customer_code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" value="Password (3 digit)" />
                            <x-text-input id="password" class="block mt-1 w-full text-center tracking-widest" type="password" name="password" required maxlength="3" autocomplete="current-password" placeholder="•••" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                                <span class="ml-2 text-sm text-gray-600">Ingat saya</span>
                            </label>
                        </div>

                        <div>
                            <button type="submit" class="w-full justify-center inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Masuk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="relative hidden w-0 flex-1 lg:block">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700"></div>
            <div class="absolute inset-0 flex items-center justify-center p-12">
                <div class="max-w-md text-center">
                    <h2 class="text-3xl font-bold text-white">Portal Pembayaran Tagihan</h2>
                    <p class="mt-4 text-lg text-blue-100">Lihat detail akun, tagihan aktif, dan riwayat tagihan Anda.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
