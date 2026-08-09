@props(['title' => null, 'icon' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'app-card']) }}>
    @if($title || $action)
        <div class="flex items-center justify-between gap-3 px-5 pt-5 pb-3">
            <div class="flex items-center gap-3">
                @if($icon)
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#2563eb]">
                        {!! $icon !!}
                    </div>
                @endif
                @if($title)
                    <h2 class="text-base font-bold text-slate-900">{{ $title }}</h2>
                @endif
            </div>
            @if($action)
                <div class="shrink-0">{{ $action }}</div>
            @endif
        </div>
    @endif
    <div class="px-5 py-4 {{ ($title || $action) ? '' : 'pt-5' }}">
        {{ $slot }}
    </div>
</div>