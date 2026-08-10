@props([
    'label' => '',
    'href' => '#',
    'icon' => '',
    'color' => 'bg-blue-600',
    'active' => false,
    'wide' => false,
])

@if($wide)
    <a href="{{ $href }}"
       class="group flex w-full items-center gap-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_1px_3px_rgba(15,23,42,0.04),0_8px_24px_rgba(15,23,42,0.06)] transition-all active:scale-[0.98] dark:border-gray-700 dark:bg-gray-800 {{ $active ? 'ring-2 ring-blue-500 dark:ring-blue-400' : '' }}">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $color }} text-white shadow-md">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $label }}</p>
            <p class="text-xs text-slate-500 dark:text-gray-400">Gateway &amp; broadcast pesan</p>
        </div>
        <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform group-active:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </a>
@else
    <a href="{{ $href }}"
       class="group flex flex-col items-center gap-2.5 rounded-2xl border border-slate-100 bg-white p-3 shadow-[0_1px_3px_rgba(15,23,42,0.04),0_8px_24px_rgba(15,23,42,0.06)] transition-all active:scale-95 dark:border-gray-700 dark:bg-gray-800 {{ $active ? 'ring-2 ring-blue-500 dark:ring-blue-400' : '' }}">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $color }} text-white shadow-md">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
            </svg>
        </div>
        <span class="text-center text-xs font-semibold leading-tight text-slate-700 dark:text-gray-200">{{ $label }}</span>
    </a>
@endif