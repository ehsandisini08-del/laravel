<?php

namespace App\Services\PaymentGateway;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditDriver implements PaymentGatewayContract
{
    public function name(): string
    {
        return 'xendit';
    }

    protected function secretKey(): string
    {
        return (string) Setting::get('payment_xendit_secret_key', config('services.xendit.secret_key', ''));
    }

    protected function verificationToken(): string
    {
        return (string) Setting::get('payment_xendit_verification_token', config('services.xendit.webhook_verification_token', ''));
    }

    protected function apiBase(): string
    {
        return 'https://api.xendit.co/v2/invoices';
    }

    public function createPayment(Invoice $invoice, array $options = []): array
    {
        $payload = [
            'external_id' => $invoice->invoice_number,
            'amount' => (int) round((float) $invoice->amount),
            'description' => "Invoice {$invoice->invoice_number} - {$invoice->customer?->name}",
            'success_redirect_url' => $options['return_url'] ?? url('/'),
            'failure_redirect_url' => $options['failure_return_url'] ?? $options['return_url'] ?? url('/'),
            'customer' => [
                'given_names' => $invoice->customer?->name,
                'email' => $invoice->customer?->user?->email,
                'mobile_number' => $invoice->customer?->phone,
            ],
        ];

        $response = Http::withBasicAuth($this->secretKey(), '')
            ->timeout(20)
            ->post($this->apiBase(), $payload);

        if ($response->failed()) {
            Log::error('Xendit create invoice failed', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'Gagal membuat pembayaran di Xendit.'];
        }

        $data = $response->json();

        return [
            'success' => true,
            'id' => $data['id'] ?? null,
            'invoice_url' => $data['invoice_url'] ?? null,
            'status' => $data['status'] ?? null,
        ];
    }

    public function verify(Request $request): array
    {
        $incomingToken = (string) $request->header('X-Callback-Token', '');
        $expectedToken = $this->verificationToken();

        if ($expectedToken !== '' && ! hash_equals($expectedToken, $incomingToken)) {
            Log::warning('Xendit webhook: invalid callback token');

            throw new PaymentSignatureException('Token callback Xendit tidak valid.');
        }

        return $request->json()->all();
    }

    public function isPaid(array $payload): bool
    {
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $event = strtoupper((string) ($payload['event'] ?? ''));

        return $status === 'PAID' || str_contains($event, 'PAID');
    }

    public function resolveInvoice(array $payload): ?Invoice
    {
        $externalId = $payload['external_id'] ?? null;

        return $externalId ? Invoice::where('invoice_number', $externalId)->first() : null;
    }

    public function methodFromPayload(array $payload): ?string
    {
        $channel = strtolower((string) ($payload['payment_channel'] ?? ''));
        $method = strtolower((string) ($payload['payment_method'] ?? ''));

        if ($channel !== '') {
            return match (true) {
                str_contains($channel, 'bca') => 'bank_transfer:bca',
                str_contains($channel, 'bri') => 'bank_transfer:bri',
                str_contains($channel, 'mandiri') => 'bank_transfer:mandiri',
                str_contains($channel, 'bni') => 'bank_transfer:bni',
                str_contains($channel, 'qris') => 'qris',
                str_contains($channel, 'gopay'), str_contains($channel, 'ovo'), str_contains($channel, 'dana'), str_contains($channel, 'shopeepay') => 'ewallet',
                default => $channel,
            };
        }

        return $method ?: null;
    }

    public function mapMethod(?string $rawMethod): PaymentMethod
    {
        $rawMethod = strtolower((string) $rawMethod);

        return match ($rawMethod) {
            'bank_transfer:bca' => PaymentMethod::VaBca,
            'bank_transfer:bri' => PaymentMethod::VaBri,
            'bank_transfer:mandiri' => PaymentMethod::VaMandiri,
            'bank_transfer:bni' => PaymentMethod::VaBni,
            'bank_transfer', 'virtual_account' => PaymentMethod::VaOther,
            'qris', 'qr_code' => PaymentMethod::Qris,
            'ewallet', 'gopay', 'ovo', 'dana', 'shopeepay' => PaymentMethod::Ewallet,
            default => PaymentMethod::Gateway,
        };
    }

    public function extractReference(array $payload): ?string
    {
        return $payload['id'] ?? $payload['payment_id'] ?? $payload['external_id'] ?? null;
    }

    public function checkoutUrl(array $payment): ?string
    {
        return $payment['invoice_url'] ?? null;
    }
}
