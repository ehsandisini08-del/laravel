<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage application configuration</p>
            </div>
        </div>
    </x-slot>

    <div x-data="{ tab: '{{ array_key_first($sections) }}', paymentProvider: '{{ $settings['payment_provider'] ?? 'none' }}' }">
        @if(session('success')) <x-alert variant="success" dismissible>{{ session('success') }}</x-alert> @endif

        <div class="mb-6 flex flex-wrap gap-2">
            @foreach($sections as $key => $section)
                <button type="button"
                    @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    {{ $section['label'] }}
                </button>
            @endforeach
        </div>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-6">
                @foreach($sections as $key => $section)
                    <div x-show="tab === '{{ $key }}'" x-cloak>
                        <x-card title="{{ $section['label'] }}" :icon="null">
                            <p class="mb-6 -mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $section['description'] }}</p>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                @foreach($section['fields'] as $fieldKey => $field)
                                    @php
                                        $currentValue = old($fieldKey, $settings[$fieldKey] ?? $field['default']);
                                    @endphp
                                    <div class="{{ in_array($field['type'], ['textarea', 'boolean', 'file']) ? 'sm:col-span-2' : '' }}" @if(! empty($field['provider'])) x-show="paymentProvider === '{{ $field['provider'] }}'" x-cloak @endif>
                                        @if($field['type'] === 'boolean')
                                            <div class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3">
                                                <input type="hidden" name="{{ $fieldKey }}" value="0">
                                                <input type="checkbox" name="{{ $fieldKey }}" value="1" id="{{ $fieldKey }}" {{ old($fieldKey, $settings[$fieldKey] ?? $field['default']) == '1' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500">
                                                <label for="{{ $fieldKey }}" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $field['label'] }}</label>
                                            </div>
                                        @else
                                            <label for="{{ $fieldKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ $field['label'] }}
                                            </label>
                                            <div class="mt-1">
                                                @if($field['type'] === 'file')
                                                    <div x-data="{
                                                        previewUrl: null,
                                                        removeMarked: false,
                                                        fileChosen(event) {
                                                            const file = event.target.files[0];
                                                            if (file) {
                                                                this.previewUrl = URL.createObjectURL(file);
                                                                this.removeMarked = false;
                                                            } else {
                                                                this.previewUrl = null;
                                                            }
                                                        }
                                                    }" class="space-y-3">
                                                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                                            <div class="relative flex h-24 w-44 shrink-0 items-center justify-center rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60 p-2 overflow-hidden">
                                                                <template x-if="previewUrl">
                                                                    <img :src="previewUrl" alt="Preview Logo" class="max-h-full max-w-full object-contain">
                                                                </template>
                                                                <template x-if="!previewUrl">
                                                                    @if(! empty($currentValue) && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentValue))
                                                                        <img src="{{ asset('storage/'.$currentValue) }}" alt="Logo saat ini" class="max-h-full max-w-full object-contain transition-all" :class="removeMarked ? 'opacity-30 grayscale' : ''">
                                                                    @else
                                                                        <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 text-center">
                                                                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                            </svg>
                                                                            <span class="text-[11px] mt-1 font-medium">Belum ada logo</span>
                                                                        </div>
                                                                    @endif
                                                                </template>
                                                            </div>

                                                            <div class="flex-1 min-w-0 space-y-2">
                                                                <input type="file"
                                                                    name="{{ $fieldKey }}"
                                                                    id="{{ $fieldKey }}"
                                                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                                                    @change="fileChosen"
                                                                    class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900/40 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/60 cursor-pointer">

                                                                @if(! empty($currentValue) && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentValue))
                                                                    <div class="flex items-center gap-2 pt-1">
                                                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-red-600 dark:text-red-400 cursor-pointer">
                                                                            <input type="checkbox"
                                                                                name="remove_{{ $fieldKey }}"
                                                                                value="1"
                                                                                x-model="removeMarked"
                                                                                class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
                                                                            <span>Hapus logo saat ini</span>
                                                                        </label>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif($field['type'] === 'select')
                                                    <select name="{{ $fieldKey }}" id="{{ $fieldKey }}" {{ $fieldKey === 'payment_provider' ? '@change="paymentProvider = $event.target.value"' : '' }} class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                        @foreach($field['options'] ?? [] as $optionValue => $optionLabel)
                                                            <option value="{{ $optionValue }}" {{ (string) $currentValue === (string) $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($field['type'] === 'textarea')
                                                    <textarea name="{{ $fieldKey }}" id="{{ $fieldKey }}" rows="3" placeholder="{{ $field['placeholder'] ?? '' }}" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $currentValue }}</textarea>
                                                @else
                                                    <input type="{{ $field['type'] }}" name="{{ $fieldKey }}" id="{{ $fieldKey }}"
                                                        value="{{ $currentValue }}"
                                                        placeholder="{{ $field['placeholder'] ?? '' }}" autocomplete="off"
                                                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                @endif
                                            </div>
                                        @endif
                                        @if(! empty($field['hint']))
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                                        @endif
                                        @error($fieldKey) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach

                                @if($key === 'payment')
                                    <div class="sm:col-span-2">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Webhook URL</p>
                                        <div class="space-y-2">
                                            @foreach($paymentWebhooks as $provider => $webhookUrl)
                                                <div x-show="paymentProvider === '{{ $provider }}'" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900/30 p-3">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($provider) }} callback URL</p>
                                                    <p class="mt-1 text-xs font-mono text-gray-900 dark:text-white break-all">{{ $webhookUrl }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Salin URL callback di atas ke dashboard payment gateway yang aktif. Pastikan domain dapat diakses dari internet saat produksi.</p>
                                    </div>
                                @endif
                            </div>
                        </x-card>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
