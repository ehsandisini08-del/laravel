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

        <form method="POST" action="{{ route('settings.update') }}">
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
                                    <div class="{{ in_array($field['type'], ['textarea', 'boolean']) ? 'sm:col-span-2' : '' }}" @if(! empty($field['provider'])) x-show="paymentProvider === '{{ $field['provider'] }}'" x-cloak @endif>
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
                                                @if($field['type'] === 'select')
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
