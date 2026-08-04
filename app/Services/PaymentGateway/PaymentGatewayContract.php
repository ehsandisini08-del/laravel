<?php

namespace App\Services\PaymentGateway;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Http\Request;

interface PaymentGatewayContract
{
    public function name(): string;

    /**
     * Create a payment/charge for the given invoice.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createPayment(Invoice $invoice, array $options = []): array;

    /**
     * Verify a webhook request signature and return the normalized payload.
     *
     * @return array<string, mixed>
     *
     * @throws PaymentSignatureException
     */
    public function verify(Request $request): array;

    /**
     * Determine whether a normalized payload represents a successful payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function isPaid(array $payload): bool;

    /**
     * Resolve the invoice from a normalized payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveInvoice(array $payload): ?Invoice;

    /**
     * Map the raw gateway payment method to a PaymentMethod enum.
     */
    public function mapMethod(?string $rawMethod): PaymentMethod;

    /**
     * Derive a normalized raw method string from a payload, e.g. "bank_transfer:bca".
     *
     * @param  array<string, mixed>  $payload
     */
    public function methodFromPayload(array $payload): ?string;

    /**
     * Extract a payment reference (transaction id) from the payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractReference(array $payload): ?string;

    /**
     * Extract the hosted checkout URL from a createPayment result payload.
     *
     * @param  array<string, mixed>  $payment
     */
    public function checkoutUrl(array $payment): ?string;
}
