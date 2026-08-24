<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Currency;

class NewInvoiceNotification extends BaseMobileNotification
{
    public function __construct(protected Invoice $invoice) {}

    protected function title(): string
    {
        return 'Tagihan Baru Diterbitkan';
    }

    protected function body(): string
    {
        $period = $this->invoice->billing_period ? " periode {$this->invoice->billing_period}" : '';

        return "Tagihan {$this->invoice->invoice_number}{$period} sebesar ".Currency::format((float) $this->invoice->amount).' telah diterbitkan.';
    }

    protected function data(): array
    {
        return [
            'type' => 'new_invoice',
            'invoice_id' => (string) $this->invoice->id,
            'billing_period' => (string) ($this->invoice->billing_period ?? ''),
            'amount' => (string) $this->invoice->amount,
            'due_date' => (string) ($this->invoice->due_date?->format('Y-m-d') ?? ''),
            'url' => route('portal.invoices.show', $this->invoice),
        ];
    }

    protected function channelId($notifiable): string
    {
        return 'billnet_customer';
    }
}
