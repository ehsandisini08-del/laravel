@props(['label' => '', 'value' => '', 'trend' => null, 'trendValue' => null, 'icon' => null, 'color' => 'blue'])

@php
$iconColors = [
    'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    'red' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
    'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            @if($trend && $trendValue)
                <div class="mt-2 flex items-center gap-1 text-sm">
                    @if($trend === 'up')
                        <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="text-green-600 dark:text-green-400 font-medium">{{ $trendValue }}</span>
                    @else
                        <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                        <span class="text-red-600 dark:text-red-400 font-medium">{{ $trendValue }}</span>
                    @endif
                    <span class="text-gray-500 dark:text-gray-400">from last month</span>
                </div>
            @endif
        </div>
        @if($icon)
            <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-lg {{ $iconColors[$color] }} flex items-center justify-center">
                    {!! $icon !!}
                </div>
            </div>
        @endif
    </div>
</div>
