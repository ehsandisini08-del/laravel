@props(['task', 'showTakeButton' => false, 'showCompleteButton' => false])

<div class="app-card">
    <div class="p-5 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $task->nama_customer }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">ID: #{{ $task->id }}</p>
            </div>
            <div>
                {!! $task->status_badge !!}
            </div>
        </div>

        <div class="space-y-2 text-sm">
            <div class="flex items-start gap-2">
                <svg class="h-4 w-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-gray-700 dark:text-gray-300 flex-1">{{ Str::limit($task->alamat, 50) }}</p>
            </div>

            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <a href="tel:{{ $task->no_telp }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $task->no_telp }}</a>
            </div>

            @if($task->latitude && $task->longitude)
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <a href="{{ $task->maps_link }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">Lihat di Maps</a>
                </div>
            @endif
        </div>

        <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Keterangan:</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($task->keterangan, 100) }}</p>
        </div>

        <div class="flex items-center gap-2 pt-2">
            <a href="{{ route('teknisi.repair-tasks.show', $task) }}" class="flex-1 text-center app-btn-ghost px-3 py-2 text-sm">
                Lihat Detail
            </a>

            @if($showTakeButton)
                <button type="button"
                    onclick="openTakeModal('{{ route('teknisi.repair-tasks.take', $task) }}', '{{ addslashes($task->nama_customer) }}', '{{ $task->id }}', '{{ addslashes($task->alamat) }}')"
                    class="flex-1 app-btn-primary px-3 py-2 text-sm flex items-center justify-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Ambil Tugas
                </button>
            @endif

            @if($showCompleteButton)
                <a href="{{ route('teknisi.repair-tasks.show', $task) }}" class="flex-1 text-center app-btn-success px-3 py-2 text-sm">
                    Selesaikan
                </a>
            @endif
        </div>

        <div class="pt-2 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
            <p>Dibuat {{ $task->created_at->diffForHumans() }}</p>
            @if($task->takenBy)
                <p class="mt-1">
                    <span class="text-gray-400">Teknisi:</span> <span class="font-medium text-gray-700 dark:text-gray-300">{{ $task->all_technicians_names }}</span>
                </p>
            @endif
        </div>
    </div>
</div>
