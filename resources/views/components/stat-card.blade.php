@props(['label' => '', 'value' => '', 'trend' => null, 'trendValue' => null, 'icon' => null, 'color' => 'blue'])

@php
$accent = [
    'blue' => 'from-blue-600 to-blue-700',
    'green' => 'from-green-600 to-green-700',
    'red' => 'from-red-600 to-red-700',
    'yellow' => 'from-amber-500 to-amber-600',
    'purple' => 'from-purple-600 to-purple-700',
];
$iconBg = [
    'blue' => 'bg-blue-500/20 text-blue-200',
    'green' => 'bg-green-500/20 text-green-200',
    'red' => 'bg-red-500/20 text-red-200',
    'yellow' => 'bg-amber-400/20 text-amber-100',
    'purple' => 'bg-purple-500/20 text-purple-200',
];
$dot = [
    'blue' => 'bg-blue-300',
    'green' => 'bg-green-300',
    'red' => 'bg-red-300',
    'yellow' => 'bg-amber-200',
    'purple' => 'bg-purple-300',
];
@endphp

<div class="overflow-hidden rounded-3xl bg-gradient-to-br {{ $accent[$color] }} p-6 text-white shadow-[0_8px_24px_rgba(15,23,42,0.12)]">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-white/75">{{ $label }}</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight">{{ $value }}</p>
            @if($trend && $trendValue)
                <div class="mt-2 flex items-center gap-1.5 text-sm">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/20">
                        <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($trend === 'up')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            @endif
                        </svg>
                    </span>
                    <span class="font-semibold text-white">{{ $trendValue }}</span>
                </div>
            @endif
        </div>
        @if($icon)
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $iconBg[$color] }}">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                </svg>
            </div>
        @endif
    </div>
    <div class="mt-3 h-1 w-12 rounded-full {{ $dot[$color] }}/60"></div>
</div>