<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'package_id',
        'router_id',
        'billing_month',
        'billing_year',
        'amount',
        'due_day',
        'isolation_day',
        'due_date',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'billing_month' => 'integer',
        'billing_year' => 'integer',
        'amount' => 'decimal:2',
        'due_day' => 'integer',
        'isolation_day' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'status' => InvoiceStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isolationLogs(): HasMany
    {
        return $this->hasMany(IsolationLog::class);
    }

    public function isUnpaid(): bool
    {
        return $this->status === InvoiceStatus::Unpaid;
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Overdue;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getBillingPeriodAttribute(): string
    {
        return sprintf('%s %d', $this->getMonthName(), $this->billing_year);
    }

    protected function getMonthName(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $months[$this->billing_month] ?? 'Unknown';
    }
}
