<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\PaymentService;
use App\Services\PaymentGateway\PaymentGatewayManager;
use App\Services\PaymentGateway\PaymentSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentGatewayManager $manager, PaymentService $paymentService)
    {
        try {
            $driver = $manager->driver($provider);
            $payload = $driver->verify($request);
        } catch (PaymentSignatureException $e) {
            Log::warning('Payment webhook: invalid signature', ['provider' => $provider]);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        if (! $driver->isPaid($payload)) {
            Log::info('Payment webhook: payment not successful, ignored', [
                'provider' => $provider,
                'payload' => $payload,
            ]);

            return response()->json(['status' => 'ok', 'message' => 'Payment not successful, ignored']);
        }

        $invoice = $driver->resolveInvoice($payload);

        if (! $invoice) {
            Log::warning('Payment webhook: invoice not found', [
                'provider' => $provider,
                'payload' => $payload,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
        }

        if ($this->amountMismatch($payload, $invoice)) {
            Log::warning('Payment webhook: amount mismatch', [
                'provider' => $provider,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'expected' => $invoice->amount,
                'payload' => $payload,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 400);
        }

        $rawMethod = $driver->methodFromPayload($payload);

        $result = $paymentService->markAsPaid($invoice, [
            'method' => $driver->mapMethod($rawMethod),
            'gateway' => $driver->name(),
            'reference' => $driver->extractReference($payload),
            'gateway_status' => $payload['status'] ?? $payload['transaction_status'] ?? null,
            'payload' => $payload,
        ]);

        if (! $result['success']) {
            Log::info('Payment webhook: invoice already paid', [
                'provider' => $provider,
                'invoice_id' => $invoice->id,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function amountMismatch(array $payload, Invoice $invoice): bool
    {
        $reported = $payload['amount'] ?? $payload['gross_amount'] ?? null;

        if ($reported === null) {
            return false;
        }

        return abs((float) $reported - (float) $invoice->amount) > 0.01;
    }
}
