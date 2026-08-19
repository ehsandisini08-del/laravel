<x-portal-layout>
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">WiFi</h1>
        <p class="mt-1 text-sm text-slate-500">Informasi perangkat dan pengaturan WiFi Anda.</p>
    </header>

    @if($cpes->isEmpty())
        <x-app-card>
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-50 text-[#2563eb]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/></svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-900">Belum Ada Perangkat WiFi</h3>
                <p class="mt-1 max-w-xs text-sm text-slate-500">Perangkat Anda belum terdaftar. Silakan hubungi admin jika Anda memiliki perangkat WiFi.</p>
            </div>
        </x-app-card>
    @else
        @foreach($cpes as $cpe)
            <section class="mb-6">
                <div class="app-section-title">
                    <h2>{{ $cpe->model_name ?? $cpe->genieacs_id }}</h2>
                    @if($cpe->isOnline())
                        <span class="app-badge-success">Online</span>
                    @elseif($cpe->status === 'offline')
                        <span class="app-badge-danger">Offline</span>
                    @else
                        <span class="app-badge-neutral">Unknown</span>
                    @endif
                </div>

                <!-- Informasi perangkat -->
                <x-app-card>
                    <div class="divide-y divide-slate-100">
                        <div class="flex items-center justify-between py-3.5">
                            <div>
                                <p class="text-xs text-slate-400">Model</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $cpe->model_name ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-3.5">
                            <div>
                                <p class="text-xs text-slate-400">Serial Number</p>
                                <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ $cpe->serial_number ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-3.5">
                            <div>
                                <p class="text-xs text-slate-400">IP Address</p>
                                <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ $cpe->ip_address ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-3.5">
                            <div>
                                <p class="text-xs text-slate-400">MAC Address</p>
                                <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ $cpe->mac_address ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-3.5">
                            <div>
                                <p class="text-xs text-slate-400">SSID</p>
                                <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ $cpe->ssid ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-3.5">
                            <div>
                                <p class="text-xs text-slate-400">RX Power</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $cpe->rx_power ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-3.5">
                            <div>
                                <p class="text-xs text-slate-400">Inform Terakhir</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $cpe->last_inform_at?->format('d M Y H:i') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </x-app-card>
            </section>

            <!-- Perangkat terhubung -->
            <section class="mb-6">
                <div class="app-section-title">
                    <h2>Perangkat Terhubung WiFi</h2>
                </div>
                <x-app-card>
                    @if(!empty($cpe->wifi_devices))
                        <div class="divide-y divide-slate-100">
                            @foreach($cpe->wifi_devices as $device)
                                <div class="py-3.5">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-800">
                                                @if(!empty($device['hostname']))
                                                    {{ $device['hostname'] }}
                                                @elseif(!empty($device['vendor']))
                                                    {{ $device['vendor'] }}
                                                @else
                                                    <span class="text-slate-400">Perangkat tidak dikenal</span>
                                                @endif
                                            </p>
                                            <p class="mt-0.5 font-mono text-xs text-slate-400">{{ $device['ip_address'] ?? '-' }}</p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            @if(!empty($device['vendor']))
                                                <span class="text-xs text-slate-400">{{ $device['vendor'] }}</span>
                                            @endif
                                            <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $device['mac_address'] ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="py-2 text-center text-sm text-slate-400">Tidak ada perangkat yang terhubung ke WiFi saat ini.</p>
                    @endif
                </x-app-card>
            </section>

            <!-- Edit SSID & password -->
            <section class="mb-6">
                <div class="app-section-title">
                    <h2>Edit SSID &amp; Password</h2>
                </div>
                <x-app-card>
                    <form method="POST" action="{{ route('portal.wifi.update', $cpe) }}" class="space-y-4" x-data="{ showPassword: false }">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="ssid-{{ $cpe->id }}" class="app-label">SSID</label>
                            <input type="text" name="ssid" id="ssid-{{ $cpe->id }}" value="{{ old('ssid', $cpe->ssid) }}" placeholder="Nama WiFi perangkat" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('ssid')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="wifi_password-{{ $cpe->id }}" class="app-label">Password WiFi</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" name="wifi_password" id="wifi_password-{{ $cpe->id }}" value="{{ old('wifi_password', $cpe->wifi_password) }}" placeholder="Password WiFi perangkat" class="mt-1 block w-full rounded-xl border-slate-300 pr-10 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            @error('wifi_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="app-btn-primary w-full px-4 py-3 text-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Simpan &amp; Kirim ke Device
                        </button>
                    </form>
                </x-app-card>
            </section>
        @endforeach
    @endif
</x-portal-layout>