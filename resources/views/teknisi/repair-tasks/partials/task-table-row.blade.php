@props(['task'])

<tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
    <td class="px-4 py-3">
        <div>
            <p class="font-medium text-gray-900 dark:text-white">{{ $task->nama_customer }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ $task->id }}</p>
        </div>
    </td>
    <td class="px-4 py-3">
        <p class="text-gray-700 dark:text-gray-300 text-sm max-w-xs">{{ Str::limit($task->alamat, 40) }}</p>
    </td>
    <td class="px-4 py-3">
        <p class="text-gray-700 dark:text-gray-300 text-sm max-w-xs">{{ Str::limit($task->keterangan, 50) }}</p>
    </td>
    <td class="px-4 py-3">
        {!! $task->status_badge !!}
    </td>
    <td class="px-4 py-3">
        <div>
            <p class="text-sm text-gray-900 dark:text-white">{{ $task->assignedBy->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $task->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </td>
    <td class="px-4 py-3">
        @if($task->takenBy)
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->all_technicians_names }}</p>
        @else
            <span class="text-xs text-gray-400">-</span>
        @endif
    </td>
    <td class="px-4 py-3 text-right">
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('teknisi.repair-tasks.show', $task) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
            @if(auth()->user()->canManageTeknisiTasks())
                <form method="POST" action="{{ route('teknisi.repair-tasks.destroy', $task) }}" onsubmit="return confirm('Yakin ingin menghapus tugas ini?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            @endif
        </div>
    </td>
</tr>
