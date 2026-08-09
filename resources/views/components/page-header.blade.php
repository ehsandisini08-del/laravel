@props(['title' => '', 'subtitle' => '', 'actions' => null])

<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>