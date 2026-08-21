<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Jalur Fiber</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manajemen jalur kabel fiber optik</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ftth.map') }}" class="app-btn-soft px-4 py-2.5 text-sm">🗺 Lihat Map</a>
                <a href="{{ route('ftth.fiber.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">+ Tambah Jalur</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <div class="app-card overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipe Kabel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sumber</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tujuan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($fibers as $fiber)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $fiber->nama }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $fiber->tipe_kabel ?: '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ strtoupper($fiber->source_type ?? '-') }} #{{ $fiber->source_id }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ strtoupper($fiber->destination_type ?? '-') }} #{{ $fiber->destination_id }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $fiber->status === 'ACTIVE' ? 'bg-green-100 text-green-700' : ($fiber->status === 'DAMAGE' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ $fiber->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('ftth.fiber.edit', $fiber) }}" class="text-gray-500 hover:text-gray-700 text-sm">Edit</a>
                                <form method="POST" action="{{ route('ftth.fiber.destroy', $fiber) }}" onsubmit="return confirm('Hapus jalur ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">Belum ada jalur fiber. <a href="{{ route('ftth.fiber.create') }}" class="text-blue-600 underline">Tambah sekarang</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $fibers->links() }}</div>
    </div>
</x-admin-layout>
