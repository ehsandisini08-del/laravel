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
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;
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

            if (! $today->isSameDay($isolationDate)) {
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
            Log::info('Auto isolation completed', $result);
        }

        return $result;
    }

    public function disableCustomer(Customer $customer, ?Invoice $invoice = null): bool
    {
        $pppSecret = $customer->pppSecret;

        if (! $pppSecret) {
            Log::warning('Customer has no PPP secret, cannot isolate', [
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
            Log::warning('PPP secret has no mikrotik_id, cannot isolate', [
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
            Log::warning('Router offline, cannot isolate customer', [
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
            $mikrotikService = new MikrotikPPPSecretService($router);

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

            $customer->update(['service_status' => 'isolated']);
            $pppSecret->update(['disabled' => true]);

            BillingLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'action' => 'customer_isolated',
                'description' => "Customer {$customer->name} isolated on router {$router->name}",
            ]);

            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'router_id' => $router->id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'disabled',
                'reason' => 'Auto isolation',
                'status' => 'success',
                'executed_at' => now(),
            ]);

            Log::info('Customer isolated', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'router_id' => $router->id,
                'router_name' => $router->name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception during customer isolation', [
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
}
