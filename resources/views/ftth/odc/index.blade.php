<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Daftar ODC</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optical Distribution Cabinet</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ftth.map') }}" class="app-btn-soft px-4 py-2.5 text-sm">🗺 Lihat Map</a>
                <a href="{{ route('ftth.odc.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">+ Tambah ODC</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <div class="app-card overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kapasitas</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ODP</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Koordinat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($odcs as $odc)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $odc->kode }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $odc->nama }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($odc->kapasitas) }} Core</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($odc->odps_count) }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono">
                            @if($odc->latitude)
                                {{ $odc->latitude }}, {{ $odc->longitude }}
                            @else
                                <span class="text-red-400">Belum diset</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                {{ $odc->status === 'ACTIVE' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                   ($odc->status === 'DOWN' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400') }}">
                                {{ $odc->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('ftth.odc.show', $odc) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm font-medium">Detail</a>
                                <a href="{{ route('ftth.odc.edit', $odc) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 text-sm">Edit</a>
                                <form method="POST" action="{{ route('ftth.odc.destroy', $odc) }}" onsubmit="return confirm('Hapus ODC {{ $odc->kode }}?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada data ODC. <a href="{{ route('ftth.odc.create') }}" class="text-blue-600 underline">Tambah sekarang</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $odcs->links() }}</div>
    </div>
</x-admin-layout>
