<?php

namespace App\Services\PaymentGateway;

use App\Models\Setting;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGatewayContract>> */
    protected array $drivers = [
        'midtrans' => MidtransDriver::class,
        'xendit' => XenditDriver::class,
        'tripay' => TripayDriver::class,
    ];

    public function driver(?string $provider = null): PaymentGatewayContract
    {
        $provider = $provider ?: (string) Setting::get('payment_provider', 'none');

        if (! isset($this->drivers[$provider])) {
            throw new InvalidArgumentException("Payment gateway provider '{$provider}' tidak dikenali.");
        }

        return app($this->drivers[$provider]);
    }

    public function supportedProviders(): array
    {
        return array_keys($this->drivers);
    }
}
