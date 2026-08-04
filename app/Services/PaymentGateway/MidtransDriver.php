<?php

namespace App\Services\PaymentGateway;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransDriver implements PaymentGatewayContract
{
    public function name(): string
    {
        return 'midtrans';
    }

    protected function serverKey(): string
    {
        return (string) Setting::get('payment_midtrans_server_key', config('services.midtrans.server_key', ''));
    }

    protected function clientKey(): string
    {
        return (string) Setting::get('payment_midtrans_client_key', config('services.midtrans.client_key', ''));
    }

    protected function isProduction(): bool
    {
        return Setting::get('payment_sandbox', '1') === '0';
    }

    protected function apiBase(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    public function createPayment(Invoice $invoice, array $options = []): array
    {
        $payload = [
            'transaction_details' => [
                'order_id' => $invoice->invoice_number,
                'gross_amount' => (int) round((float) $invoice->amount),
            ],
            'customer_details' => [
                'first_name' => $invoice->customer?->name,
                'email' => $invoice->customer?->user?->email,
                'phone' => $invoice->customer?->phone,
            ],
            'item_details' => $invoice->items->map(fn ($item) => [
                'id' => $item->id,
                'price' => (int) round((float) $item->price),
                'quantity' => $item->qty,
                'name' => $item->description,
            ])->all(),
        ];

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->timeout(20)
            ->post($this->apiBase(), $payload);

        if ($response->failed()) {
            Log::error('Midtrans create payment failed', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'Gagal membuat pembayaran di Midtrans.'];
        }

        $data = $response->json();

        return [
            'success' => true,
            'token' => $data['token'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
        ];
    }

    public function verify(Request $request): array
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey());

        if (! hash_equals($expected, $signatureKey)) {
            Log::warning('Midtrans webhook: invalid signature', ['order_id' => $orderId]);

            throw new PaymentSignatureException('Signature Midtrans tidak valid.');
        }

        return $payload;
    }

    public function isPaid(array $payload): bool
    {
        $statusCode = (string) ($payload['status_code'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');

        return in_array($statusCode, ['200', '201'], true)
            || in_array($transactionStatus, ['capture', 'settlement'], true);
    }

    public function resolveInvoice(array $payload): ?Invoice
    {
        $orderId = $payload['order_id'] ?? null;

        return $orderId ? Invoice::where('invoice_number', $orderId)->first() : null;
    }

    public function methodFromPayload(array $payload): ?string
    {
        $paymentType = (string) ($payload['payment_type'] ?? '');

        if ($paymentType === 'bank_transfer' || $paymentType === 'credit_card') {
            $bank = strtolower((string) ($payload['va_numbers'][0]['bank'] ?? ''));

            return $bank ? "bank_transfer:{$bank}" : 'bank_transfer';
        }

        return $paymentType ?: null;
    }

    public function mapMethod(?string $rawMethod): PaymentMethod
    {
        $rawMethod = strtolower((string) $rawMethod);

        return match ($rawMethod) {
            'bank_transfer:bca' => PaymentMethod::VaBca,
            'bank_transfer:bri' => PaymentMethod::VaBri,
            'bank_transfer:mandiri' => PaymentMethod::VaMandiri,
            'bank_transfer:bni' => PaymentMethod::VaBni,
            'bank_transfer' => PaymentMethod::VaOther,
            'qris' => PaymentMethod::Qris,
            'gopay', 'shopeepay', 'dana', 'ovo', 'ewallet' => PaymentMethod::Ewallet,
            'echannel' => PaymentMethod::VaOther,
            'cstore' => PaymentMethod::Other,
            default => PaymentMethod::Gateway,
        };
    }

    public function extractReference(array $payload): ?string
    {
        return $payload['transaction_id'] ?? $payload['order_id'] ?? null;
    }

    public function checkoutUrl(array $payment): ?string
    {
        return $payment['redirect_url'] ?? null;
    }
}
