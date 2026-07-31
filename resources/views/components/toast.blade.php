@props(['variant' => 'info'])

@php
$variants = [
    'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
    'error' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
    'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
];
@endphp

<div x-data="{ show: false, message: '' }" 
     @toast.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 5000)"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed bottom-4 right-4 z-50 max-w-sm"
     style="display: none;">
    <div class="rounded-lg border p-4 shadow-lg {{ $variants[$variant] }}">
        <div class="flex items-start gap-3">
            <div class="flex-1">
                <p class="text-sm font-medium" x-text="message"></p>
            </div>
            <button @click="show = false" class="flex-shrink-0 hover:opacity-70 transition-opacity">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>
