<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: false }">
            <x-admin.sidebar />
            
            <div class="lg:pl-64">
                <x-admin.navbar />

                <main class="py-6">
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

        <x-global-confirm />

        <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

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

            // Global Custom Confirm Dialog Function (v2)
            window.customConfirm = function(message, options = {}) {
                console.log('[v2] customConfirm called with:', { message, options });
                return new Promise((resolve) => {
                    const detail = {
                        message: message,
                        confirmLabel: options.confirmLabel || 'Hapus',
                        confirmColor: options.confirmColor || 'red',
                        callback: () => resolve(true)
                    };
                    console.log('[v2] Dispatching event with detail:', detail);
                    window.dispatchEvent(new CustomEvent('open-confirm', { detail }));
                    
                    // Handle cancel - resolve to false when backdrop is clicked or cancel button
                    const handleCancel = () => {
                        resolve(false);
                    };
                    
                    // Listen for one-time cancel
                    window.addEventListener('confirm-cancelled', handleCancel, { once: true });
                });
            };

            // Log when script loads
            console.log('Global showToast and customConfirm functions loaded');
        </script>

        @stack('scripts')
    </body>
</html>
