@props(['title' => '', 'icon' => null])

<div {{ $attributes->merge(['class' => 'app-card']) }}>
    @if($title || $icon)
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-gray-700 px-5 py-4">
            <div class="flex items-center gap-3">
                @if($icon)
                    <div class="flex shrink-0 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/40 text-[#2563eb] dark:text-blue-300">
                        {!! $icon !!}
                    </div>
                @endif
                @if($title)
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
                @endif
            </div>
        </div>
    @endif
    <div class="px-5 py-5">
        {{ $slot }}
    </div>
</div>