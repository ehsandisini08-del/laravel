<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Daftar ODP</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optical Distribution Point</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ftth.map') }}" class="app-btn-soft px-4 py-2.5 text-sm">🗺 Lihat Map</a>
                <a href="{{ route('ftth.odp.create') }}" class="app-btn-primary px-4 py-2.5 text-sm">+ Tambah ODP</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <div class="app-card overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ODC</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Port</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($odps as $odp)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $odp->kode }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $odp->nama }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $odp->odc?->kode ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    @php $pct = $odp->kapasitas > 0 ? ($odp->port_terpakai / $odp->kapasitas * 100) : 0; @endphp
                                    <div class="h-2 rounded-full {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-500' : 'bg-orange-400') }}" style="width:{{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $odp->port_terpakai }}/{{ $odp->kapasitas }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($odp->customers_count) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $odp->status === 'ACTIVE' ? 'bg-green-100 text-green-700' : ($odp->status === 'DOWN' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ $odp->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('ftth.odp.show', $odp) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                                <a href="{{ route('ftth.odp.edit', $odp) }}" class="text-gray-500 hover:text-gray-700 text-sm">Edit</a>
                                <form method="POST" action="{{ route('ftth.odp.destroy', $odp) }}" onsubmit="return confirm('Hapus ODP {{ $odp->kode }}?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">Belum ada data ODP. <a href="{{ route('ftth.odp.create') }}" class="text-blue-600 underline">Tambah sekarang</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $odps->links() }}</div>
    </div>
</x-admin-layout>
