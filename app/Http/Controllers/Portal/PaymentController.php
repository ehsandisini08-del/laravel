<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\PaymentGateway\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function pay(Invoice $invoice): RedirectResponse
    {
        $customer = auth('customer')->user();

        abort_unless($invoice->customer_id === $customer->id, 403);

        if (! in_array($invoice->status->value, ['unpaid', 'overdue'])) {
            return redirect()->route('portal.invoices.show', $invoice)
                ->with('error', 'Invoice ini tidak dapat dibayar (status: '.$invoice->status_label.').');
        }

        $provider = Setting::get('payment_provider', 'none');

        if ($provider === 'none') {
            return redirect()->route('portal.invoices.show', $invoice)
                ->with('error', 'Pembayaran online belum tersedia. Silakan hubungi admin.');
        }

        try {
            $driver = $this->gatewayManager->driver($provider);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('portal.invoices.show', $invoice)
                ->with('error', 'Payment gateway tidak terkonfigurasi.');
        }

        $pending = Payment::where('invoice_id', $invoice->id)
            ->where('gateway_provider', $provider)
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->first();

        if ($pending && $pending->payload) {
            $url = $driver->checkoutUrl($pending->payload);

            if ($url) {
                return redirect()->away($url);
            }
        }

        try {
            $result = $driver->createPayment($invoice, [
                'return_url' => route('portal.invoices.show', $invoice),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from portal', [
                'invoice_id' => $invoice->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('portal.invoices.show', $invoice)
                ->with('error', 'Gagal membuat pembayaran: '.$e->getMessage());
        }

        if (! $result['success']) {
            return redirect()->route('portal.invoices.show', $invoice)
                ->with('error', $result['message']);
        }

        $url = $driver->checkoutUrl($result);

        if (! $url) {
            return redirect()->route('portal.invoices.show', $invoice)
                ->with('error', 'Pembayaran dibuat, tapi link checkout tidak tersedia. Silakan hubungi admin.');
        }

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => PaymentMethod::Gateway,
            'gateway_provider' => $provider,
            'reference' => $driver->extractReference($result),
            'gateway_status' => 'pending',
            'amount' => $invoice->amount,
            'status' => PaymentStatus::Pending,
            'payload' => $result,
            'notes' => 'Pembayaran online dibuat dari portal customer',
        ]);

        return redirect()->away($url);
    }
}
