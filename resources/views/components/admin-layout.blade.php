<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#2563eb">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <x-admin.sidebar />
            
            <div class="lg:pl-64">
                <x-admin.navbar />

                <main class="py-6 pb-28 lg:pb-6">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        @if(isset($header))
                            <div class="mb-6">
                                {{ $header }}
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <x-admin.bottom-nav />

        {{-- Akses Dibatasi Popup Modal --}}
        @if(session('access_restricted'))
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" x-cloak>
            <div class="relative w-full max-w-sm overflow-hidden rounded-3xl bg-white p-6 text-center shadow-2xl transition-all dark:bg-gray-800 border border-slate-100 dark:border-gray-700 animate-scale-in">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 ring-8 ring-amber-50/50 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-900/20 mb-4">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Akses Dibatasi</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-gray-300 leading-relaxed">{{ session('access_restricted') }}</p>
                <button @click="open = false" type="button" class="mt-5 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-colors">
                    Mengerti
                </button>
            </div>
        </div>
        @endif

        <div id="toast-container" class="fixed bottom-20 right-4 z-50 space-y-2 lg:bottom-4"></div>

        <script>
            // Global Toast Notification Function
            function showToast(message, type = 'info') {
                const container = document.getElementById('toast-container');
                if (!container) {
                    console.error('Toast container not found');
                    return;
                }
                
                const colors = {
                    success: 'bg-green-50 dark:bg-green-900/20 border-green-500 text-green-800 dark:text-green-200',
                    error: 'bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800 dark:text-red-200',
                    info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-500 text-blue-800 dark:text-blue-200',
                    warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500 text-yellow-800 dark:text-yellow-200'
                };
                
                const toast = document.createElement('div');
                toast.className = `${colors[type] || colors.info} border-l-4 p-4 rounded-lg shadow-lg max-w-sm animate-slide-in`;
                toast.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-1 text-sm font-medium">${message}</div>
                        <button onclick="this.parentElement.parentElement.remove()" class="text-current opacity-70 hover:opacity-100 transition-opacity">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                `;
                
                container.appendChild(toast);
                setTimeout(() => toast.remove(), 5000);
            }

            // Log when script loads
            console.log('Global showToast function loaded');

            // Turn admin data tables into stacked cards on small screens
            (function () {
                const mq = window.matchMedia('(max-width: 1023px)');

                function decorate() {
                    const tables = document.querySelectorAll('table');
                    tables.forEach((table) => {
                        const heads = [...table.querySelectorAll('thead th')].map((th) =>
                            th.textContent.trim()
                        );
                        if (mq.matches) {
                            table.querySelectorAll('tbody tr').forEach((tr) => {
                                [...tr.querySelectorAll(':scope > td')].forEach((td, i) => {
                                    td.setAttribute('data-label', heads[i] ?? '');
                                });
                            });
                            table.classList.add('mobile-stack');
                        } else {
                            table.classList.remove('mobile-stack');
                        }
                    });
                }

                decorate();
                mq.addEventListener('change', decorate);

                // Re-decorate tables rendered later by Alpine / fetch (e.g. ppp-active, monitoring)
                const observer = new MutationObserver((mutations) => {
                    for (const m of mutations) {
                        if (m.type === 'childList' && m.target.closest && m.target.closest('table')) {
                            decorate();
                            break;
                        }
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
                window.addEventListener('load', decorate);
            })();
        </script>

        @stack('scripts')
    </body>
</html>
