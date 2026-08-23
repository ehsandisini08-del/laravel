@props(['comment'])

<div class="flex gap-3 {{ $comment->is_system ? 'opacity-75' : '' }}">
    <div class="shrink-0">
        @if($comment->is_system)
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
        @else
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-semibold text-sm">
                {{ strtoupper(substr($comment->user->name, 0, 1)) }}
            </div>
        @endif
    </div>

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
            @if($comment->is_system)
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Sistem</span>
            @else
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment->user->name }}</span>
            @endif
            <span class="text-xs text-gray-400">•</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
        </div>

        <div class="text-sm text-gray-700 dark:text-gray-300 {{ $comment->is_system ? 'italic' : '' }}">
            {{ $comment->comment }}
        </div>
    </div>
</div>
