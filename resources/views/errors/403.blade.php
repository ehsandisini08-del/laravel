<!DOCTYPE html>
<html lang="id" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <title>Akses Dibatasi - {{ config('app.name', 'Billnet') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-900/40 backdrop-blur-sm min-h-screen flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"></div>

    <div class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 sm:p-8 text-center shadow-2xl transition-all dark:bg-gray-800 border border-slate-100 dark:border-gray-700 animate-scale-in">
        {{-- Warning Icon Badge --}}
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 ring-8 ring-amber-50/50 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-900/20">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
            </svg>
        </div>

        {{-- Title --}}
        <h2 class="mt-5 text-xl font-bold text-slate-900 dark:text-white">Akses Dibatasi</h2>

        {{-- Dynamic Message --}}
        @php
            $user = auth()->user();
            $customMessage = $exception?->getMessage();
            if (empty($customMessage) || $customMessage === 'This action is unauthorized.' || $customMessage === 'Akses ditolak.') {
                if ($user && $user->isTeknisi()) {
                    $message = 'Akun teknisi tidak bisa mengakses halaman ini.';
                } elseif ($user && $user->isAdminArea()) {
                    $message = 'Akun Admin Area memiliki batasan dan tidak bisa mengakses halaman ini.';
                } else {
                    $message = 'Akun Anda tidak memiliki izin untuk mengakses halaman atau tindakan ini.';
                }
            } else {
                $message = $customMessage;
            }
        @endphp

        <p class="mt-2 text-sm text-slate-600 dark:text-gray-300 leading-relaxed">
            {{ $message }}
        </p>

        @if($user)
        <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-700 dark:text-gray-200">
            <span class="h-2 w-2 rounded-full {{ $user->isTeknisi() ? 'bg-blue-500' : ($user->isAdminArea() ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
            Role: {{ $user->roleLabel() }}
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
            <button type="button" onclick="if(window.history.length > 1){ window.history.back(); } else { window.location.href = '{{ route('dashboard') }}'; }" class="flex-1 rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors">
                ← Kembali
            </button>
            <a href="{{ route('dashboard') }}" class="flex-1 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-colors">
                Ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>
