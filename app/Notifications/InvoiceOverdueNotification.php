<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Currency;

class InvoiceOverdueNotification extends BaseMobileNotification
{
    public function __construct(protected Invoice $invoice) {}

    protected function title(): string
    {
        return 'Tagihan Jatuh Tempo';
    }

    protected function body(): string
    {
        return "Tagihan {$this->invoice->invoice_number} sebesar ".Currency::format((float) $this->invoice->amount).' telah jatuh tempo.';
    }

    protected function data(): array
    {
        return [
            'type' => 'invoice_overdue',
            'invoice_id' => (string) $this->invoice->id,
            'url' => route('portal.invoices.show', $this->invoice),
        ];
    }
}
