<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Update Aplikasi</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Perbarui aplikasi dari repository git (main branch)</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <x-card title="Versi Terpasang">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Branch</dt>
                            <dd class="font-mono text-gray-900 dark:text-white">{{ $branch ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Commit</dt>
                            <dd class="font-mono text-gray-900 dark:text-white">{{ $commit ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Commit Terakhir</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $lastCommitAt ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Update Terakhir</dt>
                            <dd class="text-gray-900 dark:text-white">
                                @if(($lastUpdate['finished_at'] ?? null))
                                    {{ $lastUpdate['finished_at'] }}
                                    @if(($lastUpdate['success'] ?? false))
                                        <x-badge variant="success">Berhasil</x-badge>
                                    @else
                                        <x-badge variant="danger">Gagal</x-badge>
                                    @endif
                                @else
                                    Belum pernah
                                @endif
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-show="!running">Update dari Repo</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-show="running" x-cloak>
                                Update sedang berjalan...
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Menjalankan git pull, composer install, migrate, build, dan restart queue.
                            </p>
                        </div>
                        <span x-show="running" x-cloak>
                            <svg class="h-6 w-6 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('update.run') }}" class="mt-4"
                          x-data="updateState()"
                          @submit.prevent="async () => { if(await customConfirm('Perbarui aplikasi dari repository git? Proses berjalan di latar belakang dan dapat memakan waktu beberapa menit.', { confirmLabel: 'Update', confirmColor: 'green' })) $el.submit() }">
                        @csrf
                        <button type="submit" :disabled="running"
                                class="app-btn-success w-full px-4 py-2.5 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Perbarui Aplikasi
                        </button>
                    </form>
                </x-card>
            </div>

            <div class="lg:col-span-2">
                <x-card title="Log Update">
                    <div class="flex items-center gap-2 mb-3">
                        <span x-show="running" class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600" x-cloak>
                            <span class="h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span> Berjalan
                        </span>
                        <span x-show="!running" class="text-xs text-gray-500">Idle</span>
                    </div>
                    <pre id="updateLog" class="h-96 overflow-auto rounded-lg bg-gray-900 text-gray-100 text-xs p-4 font-mono">{{ $logTail ?? '' }}</pre>
                </x-card>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function updateState() {
            return {
                running: {{ $running ? 'true' : 'false' }},
                logEl: null,
                init() {
                    this.logEl = document.getElementById('updateLog');
                    if (this.running) this.poll();
                },
                poll() {
                    setInterval(() => {
                        fetch('{{ route('update.status') }}', {
                            headers: { 'Accept': 'application/json' }
                        })
                        .then(r => r.json())
                        .then(data => {
                            this.running = data.running;
                            if (this.logEl && data.log_tail) this.logEl.textContent = data.log_tail;
                            if (!data.running) {
                                window.location.reload();
                            }
                        })
                        .catch(() => {});
                    }, 5000);
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>