<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $device->device_name }}</h1>
            <x-badge variant="{{ $device->status_color }}">{{ $device->status_label }}</x-badge>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-card title="Device Information">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Device Name</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $device->device_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Session Name</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $device->session_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Phone Number</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $device->phone_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Profile Name</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $device->profile_name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $device->status_label }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Last Seen</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $device->last_seen?->diffForHumans() ?? 'Never' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Connected Since</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $device->connected_at?->format('d M Y H:i') ?? '-' }}</p>
                        </div>
                    </div>
                </x-card>

                <x-card title="QR Code" class="mt-6">
                    <div id="qr-section" class="text-center">
                        @if($device->isConnected())
                            <p class="text-green-600 dark:text-green-400">Device is connected.</p>
                        @else
                            <div id="qr-container" class="space-y-4">
                                <div id="qr-placeholder" class="mx-auto w-64 h-64 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-500">Click Generate QR</span>
                                </div>
                                <img id="qr-image" src="" class="mx-auto hidden" style="max-width: 256px;">
                                <button id="generate-qr-btn" onclick="generateQR()" class="app-btn-primary px-4 py-2.5 text-sm">
                                    Generate QR
                                </button>
                                <p id="qr-status" class="text-sm text-gray-500"></p>
                            </div>
                        @endif
                    </div>
                </x-card>
            </div>

            <div>
                <x-card title="Actions">
                    <div class="space-y-3">
                        @if(!$device->isConnected())
                        <button onclick="generateQR()" class="app-btn-primary w-full px-4 py-2.5 text-sm">Generate QR</button>
                        @endif
                        @if($device->isConnected())
                        <button onclick="deviceAction('disconnect')" class="app-btn w-full bg-amber-500 text-white px-4 py-2.5 text-sm">Disconnect</button>
                        @endif
                        <button onclick="deviceAction('logout')" class="app-btn w-full bg-red-600 text-white px-4 py-2.5 text-sm">Logout</button>
                        <button onclick="refreshStatus()" class="app-btn w-full bg-slate-500 text-white px-4 py-2.5 text-sm">Refresh Status</button>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <script>
    let pollingInterval = null;

    function generateQR() {
        const btn = document.getElementById('generate-qr-btn');
        const status = document.getElementById('qr-status');
        const qrImg = document.getElementById('qr-image');
        const placeholder = document.getElementById('qr-placeholder');

        btn.disabled = true;
        btn.textContent = 'Generating...';
        status.textContent = '';

        fetch('{{ route('whatsapp.devices.qr', $device) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.qr_code) {
                placeholder.classList.add('hidden');
                qrImg.src = 'data:image/png;base64,' + data.qr_code;
                qrImg.classList.remove('hidden');
                status.textContent = 'Scan QR code with WhatsApp';
                startPolling();
            } else {
                status.textContent = 'Error: ' + (data.error || 'Failed to generate QR');
            }
            btn.disabled = false;
            btn.textContent = 'Generate QR';
        })
        .catch(err => {
            status.textContent = 'Error: ' + err.message;
            btn.disabled = false;
            btn.textContent = 'Generate QR';
        });
    }

    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(() => refreshStatus(true), 3000);
    }

    function refreshStatus(silent = false) {
        fetch('{{ route('whatsapp.devices.status', $device) }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.status === 'connected') {
                    if (pollingInterval) clearInterval(pollingInterval);
                    if (!silent) location.reload();
                } else if (data.status === 'qr_waiting') {
                    if (!silent) {
                        const status = document.getElementById('qr-status');
                        if (status) status.textContent = 'Waiting for scan...';
                    }
                }
            }
        });
    }

    async function deviceAction(action) {
        const confirmed = await customConfirm('Apakah Anda yakin ingin ' + action + ' device ini?');
        if (!confirmed) return;

        fetch('{{ route('whatsapp.devices.' . 'disconnect', $device) }}'.replace('disconnect', action), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed'));
            }
        });
    }
    </script>
</x-admin-layout>