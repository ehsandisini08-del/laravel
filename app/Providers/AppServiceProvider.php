<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $settings = $this->loadSettings();

        $this->applyTimezone($settings);
        $this->applyDebug($settings);
        $this->applySmtp($settings);

        Blade::directive('currency', function (string $expression) {
            return "<?php echo \\App\\Support\\Currency::format($expression); ?>";
        });
    }

    /**
     * @return array<string, string>
     */
    protected function loadSettings(): array
    {
        try {
            return Setting::allSettings();
        } catch (\Throwable $e) {
            Log::debug('Unable to load settings during bootstrap: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function applyTimezone(array $settings): void
    {
        $timezone = $settings['timezone'] ?? null;

        if ($timezone && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($timezone);
            config(['app.timezone' => $timezone]);
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function applyDebug(array $settings): void
    {
        if (array_key_exists('debug_mode', $settings)) {
            config(['app.debug' => $settings['debug_mode'] === '1']);
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function applySmtp(array $settings): void
    {
        $host = $settings['mail_host'] ?? null;

        if (! $host) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) ($settings['mail_port'] ?? 587),
            'mail.mailers.smtp.username' => $settings['mail_username'] ?? null,
            'mail.mailers.smtp.password' => $settings['mail_password'] ?? null,
            'mail.mailers.smtp.encryption' => ($settings['mail_encryption'] ?? 'tls') ?: null,
            'mail.from.address' => $settings['mail_from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $settings['mail_from_name'] ?? config('mail.from.name'),
        ]);
    }
}
