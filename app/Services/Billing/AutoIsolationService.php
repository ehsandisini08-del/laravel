<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Models\BillingLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IsolationLog;
use App\Models\Router;
use App\Models\Setting;
use App\Notifications\CustomerIsolatedNotification;
use App\Services\Mikrotik\PPPActiveService;
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;
use App\Services\Mobile\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoIsolationService
{
    public function disableExpiredCustomers(): array
    {
        $result = ['disabled' => 0, 'failed' => 0, 'skipped' => 0];

        if (Setting::get('auto_isolate_enabled', '1') !== '1') {
            return $result;
        }

        $today = Carbon::today();

        $invoices = Invoice::whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Overdue->value])
            ->whereNotNull('isolation_day')
            ->get();

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;

            if (! $customer) {
                $result['skipped']++;

                continue;
            }

            if ($customer->service_status === ServiceStatus::Isolated) {
                $result['skipped']++;

                continue;
            }

            $isolationDay = $invoice->isolation_day;
            $billingMonth = $invoice->billing_month;
            $billingYear = $invoice->billing_year;

            $isolationDate = Carbon::create($billingYear, $billingMonth, min($isolationDay, Carbon::create($billingYear, $billingMonth)->daysInMonth));
            $monthEnd = Carbon::create($billingYear, $billingMonth)->endOfMonth();

            if ($today->lt($isolationDate) || $today->gt($monthEnd)) {
                continue;
            }

            $success = $this->disableCustomer($customer, $invoice);

            if ($success) {
                $result['disabled']++;
            } else {
                $result['failed']++;
            }
        }

        if ($result['disabled'] > 0 || $result['failed'] > 0) {
            Log::info('Isolir otomatis selesai', $result);
        }

        return $result;
    }

    public function disableCustomer(Customer $customer, ?Invoice $invoice = null): bool
    {
        $pppSecret = $customer->pppSecret;

        if (! $pppSecret) {
            Log::warning('Customer tidak memiliki PPP secret, tidak dapat diisolir', [
                'customer_id' => $customer->id,
            ]);

            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'action' => 'disabled',
                'reason' => 'No PPP secret found',
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            return false;
        }

        $mikrotikId = $pppSecret->mikrotik_id;

        if (empty($mikrotikId)) {
            Log::warning('PPP secret tidak memiliki mikrotik_id, tidak dapat diisolir', [
                'customer_id' => $customer->id,
                'ppp_secret_id' => $pppSecret->id,
            ]);

            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'router_id' => $pppSecret->router_id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'disabled',
                'reason' => 'MikroTik ID empty',
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            return false;
        }

        $router = Router::find($pppSecret->router_id);

        if (! $router) {
            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'disabled',
                'reason' => 'Router not found',
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            return false;
        }

        if (! $router->isOnline()) {
            Log::warning('Router offline, tidak dapat mengisolir customer', [
                'customer_id' => $customer->id,
                'router_id' => $router->id,
                'router_name' => $router->name,
            ]);

            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'router_id' => $router->id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'disabled',
                'reason' => 'Router offline',
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            return false;
        }

        try {
            $mikrotikService = app()->makeWith(MikrotikPPPSecretService::class, ['router' => $router]);

            $result = $mikrotikService->disableSecret($mikrotikId);

            if (! $result['success']) {
                Log::error('Failed to disable PPP secret on MikroTik', [
                    'customer_id' => $customer->id,
                    'router_id' => $router->id,
                    'mikrotik_id' => $mikrotikId,
                    'error' => $result['message'],
                ]);

                IsolationLog::create([
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice?->id,
                    'router_id' => $router->id,
                    'ppp_secret_id' => $pppSecret->id,
                    'action' => 'disabled',
                    'reason' => $result['message'],
                    'status' => 'failed',
                    'executed_at' => now(),
                ]);

                return false;
            }

            $disconnectResult = $this->disconnectActiveSessions($router, $pppSecret->name);

            if (! $disconnectResult['success']) {
                Log::error('Gagal memutus active connection saat isolir', [
                    'customer_id' => $customer->id,
                    'router_id' => $router->id,
                    'username' => $pppSecret->name,
                    'error' => $disconnectResult['message'],
                ]);

                IsolationLog::create([
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice?->id,
                    'router_id' => $router->id,
                    'ppp_secret_id' => $pppSecret->id,
                    'action' => 'disconnect',
                    'reason' => $disconnectResult['message'],
                    'status' => 'failed',
                    'executed_at' => now(),
                ]);

                return false;
            }

            $customer->update(['service_status' => 'isolated']);
            $pppSecret->update(['disabled' => true]);

            if ($disconnectResult['disconnected'] > 0) {
                IsolationLog::create([
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice?->id,
                    'router_id' => $router->id,
                    'ppp_secret_id' => $pppSecret->id,
                    'action' => 'disconnect',
                    'reason' => 'Active connection dihapus saat isolir',
                    'status' => 'success',
                    'executed_at' => now(),
                ]);
            }

            app(PushNotificationService::class)->toCustomer($customer, new CustomerIsolatedNotification($customer, $invoice));

            BillingLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'action' => 'customer_isolated',
                'description' => "Customer {$customer->name} diisolir pada router {$router->name}",
            ]);

            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'router_id' => $router->id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'disabled',
                'reason' => 'Isolir otomatis',
                'status' => 'success',
                'executed_at' => now(),
            ]);

            Log::info('Customer diisolir', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'router_id' => $router->id,
                'router_name' => $router->name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Pengecualian saat mengisolir customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'router_id' => $pppSecret->router_id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'disabled',
                'reason' => $e->getMessage(),
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            return false;
        }
    }

    protected function disconnectActiveSessions(Router $router, string $username): array
    {
        $activeService = app()->makeWith(PPPActiveService::class, ['router' => $router]);

        try {
            $connections = $activeService->getActiveConnections();
        } catch (\Exception $e) {
            Log::error('Gagal mengambil active connection saat isolir', [
                'router_id' => $router->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to get active connections: '.$e->getMessage()];
        }

        $sessions = array_values(array_filter(
            $connections,
            fn (array $connection) => ($connection['name'] ?? null) === $username
        ));

        if (empty($sessions)) {
            return ['success' => true, 'disconnected' => 0, 'failed' => 0];
        }

        $disconnected = 0;
        $failed = 0;

        foreach ($sessions as $session) {
            $result = $activeService->disconnectUser($session['id']);

            if ($result['success'] || str_contains($result['message'], 'no such item')) {
                $disconnected++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'disconnected' => $disconnected,
            'failed' => $failed,
            'message' => "{$disconnected} active connection(s) removed.".($failed > 0 ? " {$failed} failed." : ''),
        ];
    }
}
