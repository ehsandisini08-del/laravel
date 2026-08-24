<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Detail Tugas Perbaikan</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Informasi lengkap tugas perbaikan #{{ $task->id }}</p>
            </div>
            <a href="{{ route('teknisi.repair-tasks.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <x-alert variant="success" dismissible class="mb-6">{{ session('success') }}</x-alert>
    @endif

    @if(session('error'))
        <x-alert variant="danger" dismissible class="mb-6">{{ session('error') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Informasi Customer">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Nama Customer</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->nama_customer }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Alamat</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $task->alamat }}</p>
                            @if($task->latitude && $task->longitude)
                                <a href="{{ $task->maps_link }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                    Buka di Google Maps
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">No Telepon</p>
                            <a href="tel:{{ $task->no_telp }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $task->no_telp }}</a>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Keterangan Tugas">
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $task->keterangan }}</p>
                </div>
            </x-card>

            @if($task->isSelesai())
                <x-card title="Hasil Penyelesaian">
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Keterangan Teknisi</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $task->keterangan_teknisi }}</p>
                        </div>

                        @if($task->foto_bukti)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Foto Bukti</p>
                                <a href="{{ Storage::url($task->foto_bukti) }}" target="_blank">
                                    <img src="{{ Storage::url($task->foto_bukti) }}" alt="Foto Bukti" class="rounded-lg border border-gray-200 dark:border-gray-700 max-w-md">
                                </a>
                            </div>
                        @endif

                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Diselesaikan pada</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->completed_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </x-card>
            @endif

            <x-card title="Timeline & Komentar">
                <div class="space-y-4">
                    @foreach($task->comments as $comment)
                        @include('teknisi.repair-tasks.partials.comment-item', ['comment' => $comment])
                    @endforeach
                </div>

                @if($task->isProses() && $task->taken_by_user_id === auth()->id())
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        @include('teknisi.repair-tasks.partials.comment-form', ['task' => $task])
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Status & Info">
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</p>
                        {!! $task->status_badge !!}
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Dibuat oleh</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->assignedBy->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $task->created_at->format('d M Y, H:i') }}</p>
                    </div>

                    @if($task->takenBy)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Ditangani oleh</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                                {{ $task->takenBy->name }} <span class="text-xs font-normal text-blue-600 dark:text-blue-400">(Lead)</span>
                            </p>
                            @if($task->technicians->where('id', '!=', $task->taken_by_user_id)->isNotEmpty())
                                <div class="mt-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Rekan Teknisi:</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($task->technicians->where('id', '!=', $task->taken_by_user_id) as $partner)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $partner->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($task->taken_at)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $task->taken_at->format('d M Y, H:i') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </x-card>

            @if($task->canBeTakenBy(auth()->user()))
                <x-card>
                    <button type="button"
                        onclick="openTakeModal('{{ route('teknisi.repair-tasks.take', $task) }}', '{{ addslashes($task->nama_customer) }}', '{{ $task->id }}', '{{ addslashes($task->alamat) }}')"
                        class="w-full app-btn-primary px-4 py-2.5 text-sm flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        Ambil Tugas Ini
                    </button>
                </x-card>
            @endif

            @if($task->canBeCompletedBy(auth()->user()))
                <x-card>
                    <button type="button" onclick="openCompleteModal()" class="w-full app-btn-success px-4 py-2.5 text-sm flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Selesaikan Tugas
                    </button>
                </x-card>
            @endif

            @if(auth()->user()->canManageTeknisiTasks())
                <x-card>
                    <form method="POST" action="{{ route('teknisi.repair-tasks.destroy', $task) }}" onsubmit="return confirm('Yakin ingin menghapus tugas ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full app-btn-ghost px-4 py-2.5 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Tugas
                        </button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>

    @if($task->canBeCompletedBy(auth()->user()))
        @include('teknisi.repair-tasks.partials.complete-modal', ['task' => $task])
    @endif

    @include('teknisi.repair-tasks.partials.take-modal', ['availableTeknisi' => $availableTeknisi ?? []])

    @push('scripts')
    <script>
        function openCompleteModal() {
            document.getElementById('completeModal').classList.remove('hidden');
        }

        function closeCompleteModal() {
            document.getElementById('completeModal').classList.add('hidden');
        }
    </script>
    @endpush
</x-admin-layout>
