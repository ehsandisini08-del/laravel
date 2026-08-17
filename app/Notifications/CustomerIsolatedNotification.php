<?php

namespace App\Notifications;

use App\Models\Customer;
use App\Models\Invoice;

class CustomerIsolatedNotification extends BaseMobileNotification
{
    public function __construct(protected Customer $customer, protected ?Invoice $invoice = null) {}

    protected function title(): string
    {
        return 'Layanan Diisolir';
    }

    protected function body(): string
    {
        $period = $this->invoice?->billing_period;

        $message = "Halo {$this->customer->name}, layanan internet Anda diisolir karena tagihan belum dibayar.";

        if ($period) {
            $message .= " Periode {$period}.";
        }

        return $message;
    }

    protected function data(): array
    {
        return [
            'type' => 'customer_isolated',
            'customer_id' => (string) $this->customer->id,
            'url' => route('portal.dashboard'),
        ];
    }
}
