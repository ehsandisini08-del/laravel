<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Infrastruktur</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola infrastruktur jaringan</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        <div x-data="{ activeTab: 'odc' }">
            <div class="mb-6 flex flex-wrap gap-2">
                <button type="button"
                    @click="activeTab = 'odc'"
                    :class="activeTab === 'odc' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M5 6l7-3 7 3M4 10h16v11H4V10z" />
                    </svg>
                    ODC
                </button>
                <button type="button"
                    @click="activeTab = 'odp'"
                    :class="activeTab === 'odp' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM8 9h8M8 13h8M8 17h5" />
                    </svg>
                    ODP
                </button>
                <button type="button"
                    @click="activeTab = 'map'"
                    :class="activeTab === 'map' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13a3 3 0 100-6 3 3 0 000 6z" />
                    </svg>
                    MAP
                </button>
            </div>

            <div x-show="activeTab === 'odc'" class="pt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar ODC</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola Optical Distribution Cabinet</p>
                    </div>
                    <a href="{{ route('odcs.create') }}" class="app-btn-primary px-4 py-2 text-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add ODC
                    </a>
                </div>

                @if($odcs->isEmpty())
                    <x-card>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M5 6l7-3 7 3M4 10h16v11H4V10z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada ODC</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan menambahkan ODC baru.</p>
                            <div class="mt-6">
                                <a href="{{ route('odcs.create') }}" class="app-btn-primary px-4 py-2 text-sm">Add ODC</a>
                            </div>
                        </div>
                    </x-card>
                @else
                    <div class="admin-panel">
                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="text-left">Kode ODC</th>
                                        <th class="text-left">Nama ODC</th>
                                        <th class="text-left">Lokasi</th>
                                        <th class="text-left">Koordinat</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($odcs as $odc)
                                        <tr>
                                            <td class="whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-mono font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">{{ $odc->kode_odc }}</span>
                                            </td>
                                            <td class="whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $odc->nama_odc }}</td>
                                            <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                @if($odc->latitude && $odc->longitude)
                                                    <a href="https://www.google.com/maps?q={{ $odc->latitude }},{{ $odc->longitude }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">Lihat Peta</a>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                                @if($odc->latitude && $odc->longitude)
                                                    {{ $odc->latitude }}, {{ $odc->longitude }}
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('odcs.edit', $odc) }}" class="icon-btn" title="Edit">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>
                                                    <form method="POST" action="{{ route('odcs.destroy', $odc) }}" x-data @submit.prevent="async () => { if(await customConfirm('Apakah Anda yakin ingin menghapus ODC &quot;{{ $odc->nama_odc }}&quot;?')) $el.submit() }" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="icon-btn-danger" title="Delete">
                                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">{{ $odcs->links() }}</div>
                @endif
            </div>

            <div x-show="activeTab === 'odp'" class="pt-6" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar ODP</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola Optical Distribution Point</p>
                    </div>
                    <a href="{{ route('odps.create') }}" class="app-btn-primary px-4 py-2 text-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add ODP
                    </a>
                </div>

                @if($odps->isEmpty())
                    <x-card>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM8 9h8M8 13h8M8 17h5" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada ODP</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan menambahkan ODP baru.</p>
                            <div class="mt-6">
                                <a href="{{ route('odps.create') }}" class="app-btn-primary px-4 py-2 text-sm">Add ODP</a>
                            </div>
                        </div>
                    </x-card>
                @else
                    <div class="admin-panel">
                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="text-left">Kode ODP</th>
                                        <th class="text-left">Nama ODP</th>
                                        <th class="text-left">ODC</th>
                                        <th class="text-left">Lokasi</th>
                                        <th class="text-left">Koordinat</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($odps as $odp)
                                        <tr>
                                            <td class="whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-mono font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">{{ $odp->kode_odp }}</span>
                                            </td>
                                            <td class="whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $odp->nama_odp }}</td>
                                            <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $odp->odc->kode_odc }} - {{ $odp->odc->nama_odc }}</td>
                                            <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                @if($odp->latitude && $odp->longitude)
                                                    <a href="https://www.google.com/maps?q={{ $odp->latitude }},{{ $odp->longitude }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">Lihat Peta</a>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                                @if($odp->latitude && $odp->longitude)
                                                    {{ $odp->latitude }}, {{ $odp->longitude }}
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('odps.edit', $odp) }}" class="icon-btn" title="Edit">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>
                                                    <form method="POST" action="{{ route('odps.destroy', $odp) }}" x-data @submit.prevent="async () => { if(await customConfirm('Apakah Anda yakin ingin menghapus ODP &quot;{{ $odp->nama_odp }}&quot;?')) $el.submit() }" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="icon-btn-danger" title="Delete">
                                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">{{ $odps->links() }}</div>
                @endif
            </div>

            <div x-show="activeTab === 'map'" class="pt-6" x-cloak>
                <x-card>
                    <div class="text-center py-16">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 13a3 3 0 100-6 3 3 0 000 6z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">MAP</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">Modul MAP (peta infrastruktur) akan tersedia di sini.</p>
                        <div class="mt-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200">Coming Soon</span>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>