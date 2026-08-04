<?php

namespace App\Services\PaymentGateway;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripayDriver implements PaymentGatewayContract
{
    public function name(): string
    {
        return 'tripay';
    }

    protected function merchantCode(): string
    {
        return (string) Setting::get('payment_tripay_merchant_code', config('services.tripay.merchant_code', ''));
    }

    protected function apiKey(): string
    {
        return (string) Setting::get('payment_tripay_api_key', config('services.tripay.api_key', ''));
    }

    protected function privateKey(): string
    {
        return (string) Setting::get('payment_tripay_private_key', config('services.tripay.private_key', ''));
    }

    protected function isProduction(): bool
    {
        return Setting::get('payment_sandbox', '1') === '0';
    }

    protected function apiBase(): string
    {
        return $this->isProduction()
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create';
    }

    public function createPayment(Invoice $invoice, array $options = []): array
    {
        $method = $options['method'] ?? 'QRIS';
        $amount = (int) round((float) $invoice->amount);
        $merchantRef = $invoice->invoice_number;

        $signature = hash('sha256', $this->merchantCode().$merchantRef.$amount.$this->privateKey());

        $payload = [
            'method' => $method,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $invoice->customer?->name,
            'customer_email' => $invoice->customer?->user?->email,
            'customer_phone' => $invoice->customer?->phone,
            'order_items' => $invoice->items->map(fn ($item) => [
                'sku' => (string) $item->id,
                'name' => $item->description,
                'price' => (int) round((float) $item->price),
                'quantity' => $item->qty,
            ])->all(),
            'return_url' => $options['return_url'] ?? url('/'),
            'expired_time' => 1440,
            'signature' => $signature,
        ];

        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout(20)
            ->post($this->apiBase(), $payload);

        $data = $response->json();

        if ($response->failed() || ! ($data['success'] ?? false)) {
            Log::error('Tripay create transaction failed', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'Gagal membuat pembayaran di Tripay.'];
        }

        $tx = $data['data'] ?? [];

        return [
            'success' => true,
            'reference' => $tx['reference'] ?? null,
            'merchant_ref' => $tx['merchant_ref'] ?? null,
            'payment_url' => $tx['checkout_url'] ?? $tx['pay_url'] ?? null,
            'qr_string' => $tx['qr_string'] ?? null,
            'status' => $tx['status'] ?? null,
        ];
    }

    public function verify(Request $request): array
    {
        $signature = (string) $request->header('X-Callback-Signature', '');
        $rawBody = $request->getContent();

        $expected = hash_hmac('sha256', $rawBody, $this->privateKey());

        if (! hash_equals($expected, $signature)) {
            Log::warning('Tripay webhook: invalid signature', [
                'merchant_ref' => $request->input('merchant_ref'),
            ]);

            throw new PaymentSignatureException('Signature callback Tripay tidak valid.');
        }

        return $request->json()->all();
    }

    public function isPaid(array $payload): bool
    {
        return strtoupper((string) ($payload['status'] ?? '')) === 'PAID';
    }

    public function resolveInvoice(array $payload): ?Invoice
    {
        $merchantRef = $payload['merchant_ref'] ?? null;

        return $merchantRef ? Invoice::where('invoice_number', $merchantRef)->first() : null;
    }

    public function methodFromPayload(array $payload): ?string
    {
        return $payload['payment_method'] ?? null;
    }

    public function mapMethod(?string $rawMethod): PaymentMethod
    {
        $rawMethod = strtoupper((string) $rawMethod);

        return match ($rawMethod) {
            'QRIS' => PaymentMethod::Qris,
            'BCA_VA' => PaymentMethod::VaBca,
            'BRI_VA' => PaymentMethod::VaBri,
            'MANDIRI_VA' => PaymentMethod::VaMandiri,
            'BNI_VA' => PaymentMethod::VaBni,
            'OVO', 'DANA', 'GOPAY', 'SHOPEEPAY', 'LINKAJA' => PaymentMethod::Ewallet,
            'ALFAMART', 'INDOMARET', 'XLTUNAS' => PaymentMethod::Other,
            default => str_ends_with($rawMethod, '_VA') ? PaymentMethod::VaOther : PaymentMethod::Gateway,
        };
    }

    public function extractReference(array $payload): ?string
    {
        return $payload['reference'] ?? $payload['merchant_ref'] ?? null;
    }

    public function checkoutUrl(array $payment): ?string
    {
        return $payment['payment_url'] ?? null;
    }
}
