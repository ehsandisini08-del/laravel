cd /var/www/billnet
php8.4 artisan tinker --execute='dump(app(App\Services\Billing\InvoiceService::class)->generateAllForMonth(12, 2026));'