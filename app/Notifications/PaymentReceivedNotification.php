<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Currency;

class PaymentReceivedNotification extends BaseMobileNotification
{
    public function __construct(protected Invoice $invoice) {}

    protected function title(): string
    {
        return 'Pembayaran Diterima';
    }

    protected function body(): string
    {
        return "Pembayaran tagihan {$this->invoice->invoice_number} sebesar ".Currency::format((float) $this->invoice->amount).' telah diterima. Terima kasih!';
    }

    protected function data(): array
    {
        return [
            'type' => 'payment_received',
            'invoice_id' => (string) $this->invoice->id,
            'url' => route('portal.invoices.show', $this->invoice),
        ];
    }
}
