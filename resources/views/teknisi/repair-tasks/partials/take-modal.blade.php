@props(['availableTeknisi' => []])

<div id="takeModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="take-modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeTakeModal()"></div>

        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

        <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle border border-gray-100 dark:border-gray-700">
            <form id="takeModalForm" method="POST" action="">
                @csrf

                <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="take-modal-title">
                                Konfirmasi Ambil Tugas
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Ambil tiket perbaikan dan tentukan rekan kerja bila bersama tim
                            </p>
                        </div>
                        <button type="button" onclick="closeTakeModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Target Task Info Box -->
                    <div class="mb-4 rounded-xl bg-blue-50/70 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/50 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Nama Pelanggan</p>
                                <p id="takeModalCustomerName" class="text-base font-bold text-gray-900 dark:text-white mt-0.5">-</p>
                            </div>
                            <span id="takeModalTaskId" class="text-xs font-mono font-semibold px-2.5 py-1 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 shrink-0">#0</span>
                        </div>
                        <div class="mt-2 flex items-start gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                            <svg class="h-4 w-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <p id="takeModalCustomerAddress" class="line-clamp-2">-</p>
                        </div>
                    </div>

                    <!-- Lead Technician Info -->
                    <div class="mb-4 flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700">
                        <div class="h-9 w-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teknisi Utama (Lead)</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ auth()->user()->name }} <span class="text-xs font-normal text-blue-600 dark:text-blue-400">(Anda)</span></p>
                        </div>
                    </div>

                    <!-- Partner Technicians Selection -->
                    @php
                        $otherTeknisi = collect($availableTeknisi)->filter(fn($t) => $t->id !== auth()->id());
                    @endphp

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-semibold text-gray-900 dark:text-white">
                                Teknisi Pendamping / Rekan Kerja
                            </label>
                            <span class="text-xs text-gray-500 dark:text-gray-400">(Opsional jika berdua/tim)</span>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Centang teknisi lain jika Anda mengerjakan tugas ini bersama rekan:
                        </p>

                        @if($otherTeknisi->isNotEmpty())
                            <div class="max-h-44 overflow-y-auto space-y-1 rounded-xl border border-gray-200 dark:border-gray-700 p-2 bg-gray-50/50 dark:bg-gray-900/30">
                                @foreach($otherTeknisi as $tek)
                                    <label class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-white dark:hover:bg-gray-800 transition-colors cursor-pointer select-none border border-transparent hover:border-gray-200 dark:hover:border-gray-700">
                                        <input type="checkbox" name="partner_ids[]" value="{{ $tek->id }}" class="take-modal-partner-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-offset-gray-800">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $tek->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $tek->email }}</p>
                                        </div>
                                        <span class="text-[11px] px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Teknisi</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-700/30 text-xs text-gray-500 text-center">
                                Tidak ada akun teknisi lain yang terdaftar. Anda akan mengambil tugas ini sendiri.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-700/60">
                    <button type="button" onclick="closeTakeModal()" class="app-btn-ghost px-4 py-2 text-sm">
                        Batal
                    </button>
                    <button type="submit" class="app-btn-primary px-5 py-2.5 text-sm flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Konfirmasi & Ambil Tugas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTakeModal(actionUrl, customerName, taskId, customerAddress) {
    const form = document.getElementById('takeModalForm');
    if (form) {
        form.action = actionUrl;
    }
    const nameEl = document.getElementById('takeModalCustomerName');
    if (nameEl) {
        nameEl.textContent = customerName || '-';
    }
    const idEl = document.getElementById('takeModalTaskId');
    if (idEl) {
        idEl.textContent = '#' + (taskId || '0');
    }
    const addrEl = document.getElementById('takeModalCustomerAddress');
    if (addrEl) {
        addrEl.textContent = customerAddress || '-';
    }

    // Reset partner checkboxes
    document.querySelectorAll('.take-modal-partner-checkbox').forEach(cb => cb.checked = false);

    const modal = document.getElementById('takeModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeTakeModal() {
    const modal = document.getElementById('takeModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}
</script>
