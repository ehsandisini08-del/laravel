@props(['task'])

<form method="POST" action="{{ route('teknisi.repair-tasks.comment', $task) }}" class="space-y-3">
    @csrf
    
    <div>
        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tambah Komentar</label>
        <textarea name="comment" id="comment" rows="3" required class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Tulis update atau catatan tentang tugas ini..."></textarea>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="app-btn-primary px-4 py-2 text-sm">
            Kirim Komentar
        </button>
    </div>
</form>
