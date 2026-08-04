<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Models\BillingLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IsolationLog;
use App\Models\Payment;
use App\Models\Router;
use App\Models\User;
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * @param  array{
     *     method?: PaymentMethod|string|null,
     *     gateway?: string|null,
     *     reference?: string|null,
     *     gateway_status?: string|null,
     *     paid_by?: User|null,
     *     payload?: array|null,
     *     notes?: string|null,
     *     amount?: int|float|string|null,
     * }  $options
     */
    public function markAsPaid(Invoice $invoice, array $options = []): array
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            return [
                'success' => false,
                'reactivated' => null,
                'message' => "Invoice {$invoice->invoice_number} sudah ditandai dibayar.",
            ];
        }

        $customer = $invoice->customer;
        $reactivation = ['attempted' => false, 'success' => true, 'message' => ''];

        DB::transaction(function () use ($invoice, $customer, $options, &$reactivation) {
            $method = isset($options['method']) && $options['method'] instanceof PaymentMethod
                ? $options['method']
                : PaymentMethod::from($options['method'] ?? PaymentMethod::Cash->value);
            $gateway = $options['gateway'] ?? 'manual';
            $paidBy = $options['paid_by'] ?? auth()->user();
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'payment_method' => $method,
                'paid_at' => now(),
            ]);

            $paymentData = [
                'payment_method' => $method,
                'gateway_provider' => $gateway,
                'reference' => $options['reference'] ?? null,
                'gateway_status' => $options['gateway_status'] ?? null,
                'amount' => $options['amount'] ?? $invoice->amount,
                'status' => PaymentStatus::Success,
                'paid_by_user_id' => $paidBy instanceof User ? $paidBy->id : null,
                'notes' => $options['notes'] ?? null,
                'payload' => $options['payload'] ?? null,
                'paid_at' => now(),
            ];

            $pending = $gateway !== 'manual'
                ? Payment::where('invoice_id', $invoice->id)
                    ->where('gateway_provider', $gateway)
                    ->where('status', PaymentStatus::Pending)
                    ->latest()
                    ->first()
                : null;

            if ($pending) {
                $pending->update($paymentData);
            } else {
                Payment::create(array_merge(['invoice_id' => $invoice->id], $paymentData));
            }

            BillingLog::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'action' => 'invoice_paid',
                'description' => "Invoice {$invoice->invoice_number} dibayar via {$gateway} ({$method->label()})",
            ]);

            if ($customer && $customer->service_status !== ServiceStatus::Active) {
                $reactivation = $this->reactivateCustomer($customer, $invoice);
            }
        });

        if ($reactivation['attempted'] && ! $reactivation['success']) {
            return [
                'success' => true,
                'reactivated' => false,
                'message' => $reactivation['message'],
            ];
        }

        return [
            'success' => true,
            'reactivated' => $reactivation['attempted'] ? true : null,
            'message' => "Invoice {$invoice->invoice_number} dibayar.",
        ];
    }

    protected function reactivateCustomer(Customer $customer, Invoice $invoice): array
    {
        $pppSecret = $customer->pppSecret;

        if (! $pppSecret || empty($pppSecret->mikrotik_id)) {
            $this->activateCustomer($customer, $invoice, $pppSecret);

            return ['attempted' => true, 'success' => true, 'message' => ''];
        }

        if (! $pppSecret->disabled) {
            $this->activateCustomer($customer, $invoice, $pppSecret);

            return ['attempted' => true, 'success' => true, 'message' => ''];
        }

        $router = Router::find($pppSecret->router_id);

        if (! $router || ! $router->isOnline()) {
            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'router_id' => $pppSecret->router_id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'enabled',
                'reason' => 'Router offline',
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            $message = $router
                ? "Invoice dibayar, tapi reactivation gagal karena router '{$router->name}' offline."
                : 'Invoice dibayar, tapi reactivation gagal karena router tidak ditemukan.';

            Log::warning('Failed to reactivate customer, router offline', [
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'router_id' => $pppSecret->router_id,
            ]);

            return ['attempted' => true, 'success' => false, 'message' => $message];
        }

        $mikrotikService = app()->makeWith(MikrotikPPPSecretService::class, ['router' => $router]);
        $result = $mikrotikService->enableSecret($pppSecret->mikrotik_id);

        if (! $result['success']) {
            IsolationLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'router_id' => $pppSecret->router_id,
                'ppp_secret_id' => $pppSecret->id,
                'action' => 'enabled',
                'reason' => $result['message'],
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            Log::error('Failed to re-enable PPP secret on MikroTik', [
                'customer_id' => $customer->id,
                'router_id' => $router->id,
                'mikrotik_id' => $pppSecret->mikrotik_id,
                'error' => $result['message'],
            ]);

            return [
                'attempted' => true,
                'success' => false,
                'message' => "Invoice dibayar, tapi reactivation gagal: {$result['message']}",
            ];
        }

        $this->activateCustomer($customer, $invoice, $pppSecret);

        IsolationLog::create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'router_id' => $pppSecret->router_id,
            'ppp_secret_id' => $pppSecret->id,
            'action' => 'enabled',
            'reason' => 'Payment received',
            'status' => 'success',
            'executed_at' => now(),
        ]);

        return ['attempted' => true, 'success' => true, 'message' => ''];
    }

    protected function activateCustomer(Customer $customer, Invoice $invoice, $pppSecret = null): void
    {
        $customer->update(['service_status' => ServiceStatus::Active]);

        if ($pppSecret && $pppSecret->disabled) {
            $pppSecret->update(['disabled' => false]);
        }

        BillingLog::create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'action' => 'customer_reactivated',
            'description' => "Customer {$customer->name} reactivated",
        ]);
    }
}
