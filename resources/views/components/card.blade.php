@props(['title' => '', 'icon' => null])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden']) }}>
    @if($title || $icon)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                @if($icon)
                    <div class="flex-shrink-0">
                        {!! $icon !!}
                    </div>
                @endif
                @if($title)
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                @endif
            </div>
        </div>
    @endif
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
