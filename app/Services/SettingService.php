<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SettingService
{
    /**
     * Define all setting sections and their fields.
     *
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     fields: array<string, array{
     *         label: string,
     *         type: string,
     *         default: string|bool,
     *         rules: array|string,
     *         options?: array<string, string>,
     *         placeholder?: string,
     *         hint?: string,
     *     }>,
     * }>
     */
    public function sections(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'description' => 'Konfigurasi dasar aplikasi.',
                'fields' => [
                    'app_name' => [
                        'label' => 'Nama Aplikasi',
                        'type' => 'text',
                        'default' => 'Admin',
                        'rules' => ['required', 'string', 'max:100'],
                    ],
                    'app_url' => [
                        'label' => 'URL Aplikasi',
                        'type' => 'url',
                        'default' => url('/'),
                        'rules' => ['nullable', 'url', 'max:255'],
                    ],
                    'customer_app_url' => [
                        'label' => 'URL Download Aplikasi',
                        'description' => 'Link unduhan aplikasi Android yang dikirim bersama info login portal.',
                        'type' => 'url',
                        'default' => '',
                        'rules' => ['nullable', 'url', 'max:255'],
                    ],
                    'timezone' => [
                        'label' => 'Zona Waktu',
                        'type' => 'select',
                        'default' => config('app.timezone'),
                        'options' => [
                            'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                            'Asia/Makassar' => 'Asia/Makassar (WITA)',
                            'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                            'UTC' => 'UTC',
                        ],
                        'rules' => ['required', 'string'],
                    ],
                    'locale' => [
                        'label' => 'Bahasa',
                        'type' => 'select',
                        'default' => 'id',
                        'options' => [
                            'id' => 'Indonesia',
                            'en' => 'English',
                        ],
                        'rules' => ['required', 'string'],
                    ],
                    'date_format' => [
                        'label' => 'Format Tanggal',
                        'type' => 'select',
                        'default' => 'd/m/Y',
                        'options' => [
                            'd/m/Y' => 'DD/MM/YYYY',
                            'd-m-Y' => 'DD-MM-YYYY',
                            'Y-m-d' => 'YYYY-MM-DD',
                            'M d, Y' => 'Mon DD, YYYY',
                        ],
                        'rules' => ['required', 'string'],
                    ],
                    'pagination' => [
                        'label' => 'Data per Halaman',
                        'type' => 'number',
                        'default' => '15',
                        'rules' => ['required', 'integer', 'min:5', 'max:100'],
                    ],
                ],
            ],
            'company' => [
                'label' => 'Company Profile',
                'description' => 'Informasi profil perusahaan ISP.',
                'fields' => [
                    'company_name' => [
                        'label' => 'Nama Perusahaan',
                        'type' => 'text',
                        'default' => '',
                        'rules' => ['nullable', 'string', 'max:150'],
                    ],
                    'company_address' => [
                        'label' => 'Alamat',
                        'type' => 'textarea',
                        'default' => '',
                        'rules' => ['nullable', 'string', 'max:500'],
                    ],
                    'company_phone' => [
                        'label' => 'Telepon',
                        'type' => 'text',
                        'default' => '',
                        'rules' => ['nullable', 'string', 'max:30'],
                    ],
                    'company_email' => [
                        'label' => 'Email',
                        'type' => 'email',
                        'default' => '',
                        'rules' => ['nullable', 'email', 'max:150'],
                    ],
                    'company_tax_number' => [
                        'label' => 'Nomor NPWP',
                        'type' => 'text',
                        'default' => '',
                        'rules' => ['nullable', 'string', 'max:30'],
                    ],
                    'company_website' => [
                        'label' => 'Website',
                        'type' => 'url',
                        'default' => '',
                        'rules' => ['nullable', 'url', 'max:255'],
                    ],
                ],
            ],
            'billing' => [
                'label' => 'Billing',
                'description' => 'Preferensi modul billing & invoice.',
                'fields' => [
                    'invoice_prefix' => [
                        'label' => 'Prefix Nomor Invoice',
                        'type' => 'text',
                        'default' => 'INV',
                        'placeholder' => 'INV',
                        'hint' => 'Digunakan sebagai awalan nomor invoice, contoh: INV-202608-000001.',
                        'rules' => ['required', 'string', 'max:10'],
                    ],
                    'currency_code' => [
                        'label' => 'Kode Mata Uang',
                        'type' => 'text',
                        'default' => 'IDR',
                        'rules' => ['required', 'string', 'max:5'],
                    ],
                    'currency_symbol' => [
                        'label' => 'Simbol Mata Uang',
                        'type' => 'text',
                        'default' => 'Rp',
                        'rules' => ['required', 'string', 'max:10'],
                    ],
                    'default_due_day' => [
                        'label' => 'Hari Jatuh Tempo Default',
                        'type' => 'number',
                        'default' => '10',
                        'rules' => ['required', 'integer', 'min:1', 'max:31'],
                    ],
                    'default_isolation_day' => [
                        'label' => 'Hari Isolir Default',
                        'type' => 'number',
                        'default' => '15',
                        'rules' => ['required', 'integer', 'min:1', 'max:31'],
                    ],
                    'auto_isolate_enabled' => [
                        'label' => 'Aktifkan Auto Isolir',
                        'type' => 'boolean',
                        'default' => true,
                        'rules' => ['nullable', 'boolean'],
                    ],
                    'reminder_days_before_due' => [
                        'label' => 'Hari Pengingat Jatuh Tempo',
                        'type' => 'text',
                        'default' => '7,3,1',
                        'placeholder' => '7,3,1',
                        'hint' => 'Daftar hari sebelum jatuh tempo untuk pengingat WhatsApp, dipisahkan koma.',
                        'rules' => ['nullable', 'string', 'max:50'],
                    ],
                ],
            ],
            'smtp' => [
                'label' => 'SMTP Mail',
                'description' => 'Konfigurasi email server untuk notifikasi.',
                'fields' => [
                    'mail_host' => [
                        'label' => 'SMTP Host',
                        'type' => 'text',
                        'default' => '',
                        'rules' => ['nullable', 'string', 'max:150'],
                    ],
                    'mail_port' => [
                        'label' => 'SMTP Port',
                        'type' => 'number',
                        'default' => '587',
                        'rules' => ['nullable', 'integer', 'min:1', 'max:65535'],
                    ],
                    'mail_username' => [
                        'label' => 'SMTP Username',
                        'type' => 'text',
                        'default' => '',
                        'rules' => ['nullable', 'string', 'max:150'],
                    ],
                    'mail_password' => [
                        'label' => 'SMTP Password',
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => '••••••••',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                    'mail_encryption' => [
                        'label' => 'Enkripsi',
                        'type' => 'select',
                        'default' => 'tls',
                        'options' => [
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                            'none' => 'None',
                        ],
                        'rules' => ['nullable', 'string'],
                    ],
                    'mail_from_address' => [
                        'label' => 'From Address',
                        'type' => 'email',
                        'default' => '',
                        'rules' => ['nullable', 'email', 'max:150'],
                    ],
                    'mail_from_name' => [
                        'label' => 'From Name',
                        'type' => 'text',
                        'default' => '',
                        'rules' => ['nullable', 'string', 'max:150'],
                    ],
                ],
            ],
            'payment' => [
                'label' => 'Payment Gateway',
                'description' => 'Konfigurasi payment gateway (Midtrans, Xendit, Tripay).',
                'fields' => [
                    'payment_provider' => [
                        'label' => 'Provider Aktif',
                        'type' => 'select',
                        'default' => 'none',
                        'options' => [
                            'none' => 'Tidak Aktif',
                            'midtrans' => 'Midtrans',
                            'xendit' => 'Xendit',
                            'tripay' => 'Tripay',
                        ],
                        'rules' => ['required', 'string'],
                    ],
                    'payment_sandbox' => [
                        'label' => 'Mode Sandbox',
                        'type' => 'boolean',
                        'default' => true,
                        'rules' => ['nullable', 'boolean'],
                    ],
                    'payment_midtrans_server_key' => [
                        'label' => 'Midtrans - Server Key',
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => '••••••••',
                        'provider' => 'midtrans',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                    'payment_midtrans_client_key' => [
                        'label' => 'Midtrans - Client Key',
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => '••••••••',
                        'provider' => 'midtrans',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                    'payment_xendit_secret_key' => [
                        'label' => 'Xendit - Secret Key',
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => '••••••••',
                        'provider' => 'xendit',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                    'payment_xendit_verification_token' => [
                        'label' => 'Xendit - Webhook Verification Token',
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => '••••••••',
                        'provider' => 'xendit',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                    'payment_tripay_merchant_code' => [
                        'label' => 'Tripay - Merchant Code',
                        'type' => 'text',
                        'default' => '',
                        'provider' => 'tripay',
                        'rules' => ['nullable', 'string', 'max:50'],
                    ],
                    'payment_tripay_api_key' => [
                        'label' => 'Tripay - API Key',
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => '••••••••',
                        'provider' => 'tripay',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                    'payment_tripay_private_key' => [
                        'label' => 'Tripay - Private Key',
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => '••••••••',
                        'provider' => 'tripay',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                ],
            ],
            'system' => [
                'label' => 'System',
                'description' => 'Preferensi sistem & pemeliharaan.',
                'fields' => [
                    'maintenance_mode' => [
                        'label' => 'Mode Pemeliharaan',
                        'type' => 'boolean',
                        'default' => false,
                        'rules' => ['nullable', 'boolean'],
                    ],
                    'debug_mode' => [
                        'label' => 'Mode Debug',
                        'type' => 'boolean',
                        'default' => false,
                        'rules' => ['nullable', 'boolean'],
                    ],
                    'log_retention_days' => [
                        'label' => 'Retensi Log (hari)',
                        'type' => 'number',
                        'default' => '30',
                        'rules' => ['required', 'integer', 'min:1', 'max:365'],
                    ],
                ],
            ],
        ];
    }

    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->sections() as $section) {
            foreach ($section['fields'] as $key => $field) {
                $defaults[$key] = $field['default'];
            }
        }

        return $defaults;
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->sections() as $section) {
            foreach ($section['fields'] as $key => $field) {
                $rules[$key] = $field['rules'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function paymentWebhooks(): array
    {
        return [
            'midtrans' => url('/webhooks/payment/midtrans'),
            'xendit' => url('/webhooks/payment/xendit'),
            'tripay' => url('/webhooks/payment/tripay'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return array_merge(array_map('strval', $this->defaults()), Setting::allSettings());
    }

    /**
     * @return array<string, string>
     */
    public function byGroup(string $group): array
    {
        $values = Setting::byGroup($group);

        foreach ($this->sections() as $sectionKey => $section) {
            if ($sectionKey !== $group) {
                continue;
            }

            foreach ($section['fields'] as $key => $field) {
                if (! array_key_exists($key, $values)) {
                    $values[$key] = (string) $field['default'];
                }
            }
        }

        return $values;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default ?? $this->defaults()[$key] ?? null);
    }

    public function update(array $data): void
    {
        foreach ($this->sections() as $group => $section) {
            foreach ($section['fields'] as $key => $field) {
                if ($field['type'] === 'boolean') {
                    $value = array_key_exists($key, $data) && $data[$key] ? '1' : '0';
                } elseif (array_key_exists($key, $data)) {
                    $value = $data[$key];
                } else {
                    continue;
                }

                Setting::set($key, $value === null ? '' : (string) $value, $group);
            }
        }

        Log::info('Settings updated', [
            'user_id' => auth()->id(),
        ]);
    }
}
