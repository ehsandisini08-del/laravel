<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('WhatsApp Gateway Monitoring') }}
            </h2>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Auto-refresh: <span id="refresh-countdown">10</span>s
                </span>
                <div id="loading-indicator" class="hidden">
                    <svg class="w-5 h-5 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <!-- Alerts Section -->
        <div id="alerts-container" class="hidden px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Alerts will be injected here -->
        </div>

        <!-- Overview Stats -->
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Devices -->
                <div class="p-6 bg-white shadow dark:bg-gray-800 sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Devices</p>
                            <p id="stat-total-devices" class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">-</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full dark:bg-blue-900">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 text-sm">
                        <span class="text-green-600 dark:text-green-400"><span id="stat-connected">0</span> connected</span>
                        <span class="mx-2 text-gray-400">•</span>
                        <span class="text-red-600 dark:text-red-400"><span id="stat-disconnected">0</span> disconnected</span>
                    </div>
                </div>

                <!-- Average Uptime -->
                <div class="p-6 bg-white shadow dark:bg-gray-800 sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Average Uptime</p>
                            <p id="stat-uptime" class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">-%</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full dark:bg-green-900">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Alerts -->
                <div class="p-6 bg-white shadow dark:bg-gray-800 sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Alerts</p>
                            <p id="stat-alerts" class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">-</p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full dark:bg-yellow-900">
                            <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Message Queue -->
                <div class="p-6 bg-white shadow dark:bg-gray-800 sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Queued Messages</p>
                            <p id="stat-queue" class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">-</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full dark:bg-purple-900">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Devices Grid -->
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Devices Status</h3>
                </div>
                <div class="p-6">
                    <div id="devices-container" class="space-y-4">
                        <!-- Devices will be injected here -->
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            Loading devices...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection History -->
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h3>
                </div>
                <div class="p-6">
                    <div id="history-container" class="space-y-2">
                        <!-- History will be injected here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let refreshInterval;
        let countdownInterval;
        let countdown = 10;

        // Start auto-refresh
        function startAutoRefresh() {
            // Initial fetch
            fetchAllData();

            // Refresh every 10 seconds
            refreshInterval = setInterval(() => {
                fetchAllData();
                countdown = 10;
            }, 10000);

            // Countdown timer
            countdownInterval = setInterval(() => {
                countdown--;
                if (countdown < 0) countdown = 10;
                document.getElementById('refresh-countdown').textContent = countdown;
            }, 1000);
        }

        // Fetch all monitoring data
        async function fetchAllData() {
            showLoading(true);
            
            try {
                await Promise.all([
                    fetchOverview(),
                    fetchAlerts(),
                ]);
            } catch (error) {
                console.error('Error fetching data:', error);
            }
            
            showLoading(false);
        }

        // Fetch overview data
        async function fetchOverview() {
            try {
                const response = await fetch('{{ route('whatsapp.monitoring.api.overview') }}');
                const data = await response.json();

                if (data.success && data.overview) {
                    updateOverviewStats(data.overview);
                    updateDevices(data.overview.devices);
                } else {
                    console.error('Overview fetch failed:', data);
                }
            } catch (error) {
                console.error('Error fetching overview:', error);
            }
        }

        // Fetch alerts
        async function fetchAlerts() {
            try {
                const response = await fetch('{{ route('whatsapp.monitoring.api.alerts') }}');
                const data = await response.json();

                if (data.success && data.alerts) {
                    updateAlerts(data.alerts);
                }
            } catch (error) {
                console.error('Error fetching alerts:', error);
            }
        }

        // Update overview stats
        function updateOverviewStats(overview) {
            document.getElementById('stat-total-devices').textContent = overview.devices.total || 0;
            document.getElementById('stat-connected').textContent = overview.devices.connected || 0;
            document.getElementById('stat-disconnected').textContent = overview.devices.disconnected || 0;
            document.getElementById('stat-uptime').textContent = overview.uptime.average.toFixed(1) + '%';
            document.getElementById('stat-alerts').textContent = overview.alerts.total || 0;
            document.getElementById('stat-queue').textContent = overview.queue.totalPending || 0;
        }

        // Update alerts
        function updateAlerts(alerts) {
            const container = document.getElementById('alerts-container');
            
            if (!alerts || alerts.length === 0) {
                container.classList.add('hidden');
                return;
            }

            container.classList.remove('hidden');
            
            const alertsHtml = alerts.map(alert => {
                const severityColors = {
                    critical: 'bg-red-100 border-red-500 text-red-900 dark:bg-red-900 dark:text-red-100',
                    high: 'bg-orange-100 border-orange-500 text-orange-900 dark:bg-orange-900 dark:text-orange-100',
                    medium: 'bg-yellow-100 border-yellow-500 text-yellow-900 dark:bg-yellow-900 dark:text-yellow-100',
                    low: 'bg-blue-100 border-blue-500 text-blue-900 dark:bg-blue-900 dark:text-blue-100',
                };
                
                const colorClass = severityColors[alert.severity] || severityColors.medium;
                
                return `
                    <div class="p-4 border-l-4 ${colorClass}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-medium">${alert.message}</p>
                                <p class="mt-1 text-sm opacity-75">Session: ${alert.sessionName} • ${new Date(alert.timestamp).toLocaleString()}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold uppercase rounded">${alert.severity}</span>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = alertsHtml;
        }

        // Update devices
        function updateDevices(devicesData) {
            const container = document.getElementById('devices-container');
            
            console.log('updateDevices called with:', devicesData);
            
            if (!devicesData) {
                console.error('devicesData is null or undefined');
                container.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">No devices data</div>';
                return;
            }

            // Fetch detailed device info
            fetch('{{ route('whatsapp.monitoring.api.status') }}')
                .then(res => {
                    console.log('Status response:', res);
                    return res.json();
                })
                .then(data => {
                    console.log('Status data:', data);
                    if (data.success && data.devices) {
                        console.log('Rendering', data.devices.length, 'devices');
                        renderDevices(data.devices);
                    } else {
                        console.error('No devices in response');
                        container.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">No devices found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching device status:', error);
                    container.innerHTML = '<div class="text-center text-red-500">Error loading devices</div>';
                });
        }

        // Render devices
        function renderDevices(devices) {
            const container = document.getElementById('devices-container');
            
            console.log('renderDevices called with', devices.length, 'devices');
            
            if (!devices || devices.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">No devices found</div>';
                return;
            }
            
            const devicesHtml = devices.map(device => {
                const statusColors = {
                    connected: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    disconnected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    reconnecting: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    qr_waiting: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    logged_out: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                };
                
                const statusClass = statusColors[device.status] || statusColors.disconnected;
                
                return `
                    <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">${device.sessionName}</h4>
                                    <span class="px-2 py-1 text-xs font-semibold rounded ${statusClass}">${device.status}</span>
                                </div>
                                ${device.phoneNumber ? `<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">📱 ${device.phoneNumber}</p>` : '<p class="mt-1 text-sm text-gray-500 dark:text-gray-500">No phone number (scan QR)</p>'}
                                ${device.profileName ? `<p class="text-sm text-gray-600 dark:text-gray-400">${device.profileName}</p>` : ''}
                                ${device.reconnectAttempts > 0 ? `<p class="text-sm text-orange-600 dark:text-orange-400">Reconnect attempts: ${device.reconnectAttempts}</p>` : ''}
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="reconnectDevice('${device.sessionName}')" 
                                        class="px-3 py-1 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                    Reconnect
                                </button>
                                <button onclick="backupDevice('${device.sessionName}')" 
                                        class="px-3 py-1 text-sm text-white bg-green-600 rounded hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                                    Backup
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = devicesHtml;
            console.log('Devices rendered successfully');
        }

        // Reconnect device
        async function reconnectDevice(sessionName) {
            if (!confirm(`Reconnect device ${sessionName}?`)) return;

            try {
                const response = await fetch(`/whatsapp/monitoring/api/reconnect/${sessionName}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    alert('Reconnect initiated!');
                    fetchAllData();
                } else {
                    alert('Failed to reconnect: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // Backup device
        async function backupDevice(sessionName) {
            try {
                const response = await fetch(`/whatsapp/monitoring/api/backup/${sessionName}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    alert('Backup created successfully!');
                } else {
                    alert('Failed to create backup: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // Show/hide loading indicator
        function showLoading(show) {
            const indicator = document.getElementById('loading-indicator');
            if (show) {
                indicator.classList.remove('hidden');
            } else {
                indicator.classList.add('hidden');
            }
        }

        // Start on page load
        document.addEventListener('DOMContentLoaded', () => {
            startAutoRefresh();
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearInterval(refreshInterval);
            clearInterval(countdownInterval);
        });
    </script>
    @endpush
</x-app-layout>
