@props(['task'])

<div id="completeModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeCompleteModal()"></div>

        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

        <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            <form method="POST" action="{{ route('teknisi.repair-tasks.complete', $task) }}" enctype="multipart/form-data">
                @csrf

                <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="modal-title">
                                Selesaikan Tugas
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Isi keterangan penyelesaian dan upload foto bukti (opsional)
                            </p>
                        </div>
                        <button type="button" onclick="closeCompleteModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="keterangan_teknisi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Keterangan Penyelesaian <span class="text-red-500">*</span>
                            </label>
                            <textarea name="keterangan_teknisi" id="keterangan_teknisi" rows="6" required class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Jelaskan apa yang telah dikerjakan, masalah yang ditemukan, dan solusinya..."></textarea>
                        </div>

                        <div>
                            <label for="foto_bukti" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Foto Bukti <span class="text-xs text-gray-500">(Opsional)</span>
                            </label>
                            <div class="mt-1 flex justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 px-6 py-10 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="mt-4 flex text-sm leading-6 text-gray-600 dark:text-gray-400">
                                        <label for="foto_bukti" class="relative cursor-pointer rounded-md font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-500">
                                            <span>Upload foto</span>
                                            <input id="foto_bukti" name="foto_bukti" type="file" accept="image/jpeg,image/jpg,image/png" class="sr-only" onchange="previewImage(event)">
                                        </label>
                                        <p class="pl-1">atau drag and drop</p>
                                    </div>
                                    <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">PNG, JPG, JPEG maksimal 5MB</p>
                                </div>
                            </div>
                            <div id="imagePreview" class="hidden mt-3">
                                <img id="preview" src="" alt="Preview" class="rounded-lg max-h-48 mx-auto">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeCompleteModal()" class="app-btn-ghost px-4 py-2 text-sm">
                        Batal
                    </button>
                    <button type="submit" class="app-btn-success px-6 py-2.5 text-sm">
                        Selesaikan Tugas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
            document.getElementById('imagePreview').classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}
</script>
