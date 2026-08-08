<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Job Monitor</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pantau job queue & scheduler secara live</p>
            </div>
            <span class="inline-flex items-center gap-2 text-xs text-gray-500">
                <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                Live (auto-refresh 5 detik)
            </span>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="monitorState()">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-card><div class="text-sm text-gray-500">Sukses Hari Ini</div><div x-text="stats.today_success ?? 0" class="text-2xl font-bold text-green-600"></div></x-card>
            <x-card><div class="text-sm text-gray-500">Gagal Hari Ini</div><div x-text="stats.today_failed ?? 0" class="text-2xl font-bold text-red-600"></div></x-card>
            <x-card><div class="text-sm text-gray-500">Antrian Pending</div><div x-text="stats.pending_jobs ?? 0" class="text-2xl font-bold text-yellow-600"></div></x-card>
            <x-card><div class="text-sm text-gray-500">Failed Jobs</div><div x-text="stats.failed_jobs ?? 0" class="text-2xl font-bold text-red-600"></div></x-card>
            <x-card><div class="text-sm text-gray-500">Avg Durasi (ms)</div><div x-text="stats.avg_duration_ms ?? 0" class="text-2xl font-bold text-blue-600"></div></x-card>
        </div>

        <div class="flex gap-2">
            <button @click="tab='jobs'" :class="tab==='jobs' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">Queue Jobs</button>
            <button @click="tab='schedules'" :class="tab==='schedules' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">Scheduler</button>
        </div>

        <x-card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Waktu</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Class / Command</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tries</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durasi (ms)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Error</th>
                        </tr>
                    </thead>
                    <tbody id="monitor-rows" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach(($tab ?? $recentJobs) as $log)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $log->created_at?->format('d M H:i:s') }}</td>
                                <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-white">{{ $log->class }}</td>
                                <td class="px-4 py-2"><x-badge variant="{{ $log->statusColor() }}">{{ $log->statusLabel() }}</x-badge></td>
                                <td class="px-4 py-2 text-sm text-right text-gray-600">{{ $log->tries }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-600">{{ $log->duration_ms ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-red-600 truncate max-w-[240px]" title="{{ $log->exception_message }}">{{ $log->exception_message ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    @push('scripts')
    <script>
        function monitorState() {
            return {
                tab: 'jobs',
                stats: { today_success: 0, today_failed: 0, pending_jobs: 0, failed_jobs: 0, avg_duration_ms: 0 },
                init() {
                    this.poll();
                    setInterval(() => this.poll(), 5000);
                },
                poll() {
                    fetch('{{ route('monitoring.jobs.status') }}', {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.stats) Object.assign(this.stats, data.stats);
                        const rows = this.tab === 'schedules' ? (data.recent_schedules || []) : (data.recent_jobs || []);
                        this.render(rows);
                    })
                    .catch(() => {});
                },
                render(rows) {
                    const tbody = document.getElementById('monitor-rows');
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    rows.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="px-4 py-2 text-sm text-gray-600">${row.created_at || ''}</td>
                            <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-white">${row.class || ''}</td>
                            <td class="px-4 py-2"><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-${badgeBg(row.status_color)} text-${badgeText(row.status_color)}">${row.status_label || row.status}</span></td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600">${row.tries ?? '-'}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600">${row.duration_ms ?? '-'}</td>
                            <td class="px-4 py-2 text-xs text-red-600 truncate max-w-[240px]" title="${row.exception || ''}">${row.exception || ''}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }
        }
        function badgeBg(color) {
            return color === 'danger' ? 'red-100' : color === 'success' ? 'green-100' : color === 'warning' ? 'yellow-100' : 'gray-100';
        }
        function badgeText(color) {
            return color === 'danger' ? 'red-700' : color === 'success' ? 'green-700' : color === 'warning' ? 'yellow-700' : 'gray-700';
        }
    </script>
    @endpush
</x-admin-layout>