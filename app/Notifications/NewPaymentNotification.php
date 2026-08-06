<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Currency;

class NewPaymentNotification extends BaseMobileNotification
{
    public function __construct(protected Invoice $invoice) {}

    protected function title(): string
    {
        return 'Pembayaran Baru';
    }

    protected function body(): string
    {
        $customer = $this->invoice->customer;

        return ($customer ? $customer->name.' membayar' : 'Pembayaran')." tagihan {$this->invoice->invoice_number} sebesar ".Currency::format((float) $this->invoice->amount).'.';
    }

    protected function data(): array
    {
        return [
            'type' => 'new_payment',
            'invoice_id' => (string) $this->invoice->id,
            'url' => route('billing.invoices.show', $this->invoice),
        ];
    }
}
