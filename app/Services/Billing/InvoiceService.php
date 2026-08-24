<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Models\BillingLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\NewInvoiceNotification;
use App\Services\Mobile\PushNotificationService;
use App\Support\SettingSupport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function generateForCustomer(Customer $customer, int $month, int $year): ?Invoice
    {
        $existing = Invoice::where('customer_id', $customer->id)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->first();

        if ($existing) {
            return null;
        }

        $package = $customer->package;
        if (! $package) {
            Log::warning('Customer has no package, skipping invoice generation', [
                'customer_id' => $customer->id,
            ]);

            return null;
        }

        $dueDate = Carbon::create($year, $month, min($customer->due_day, Carbon::create($year, $month)->daysInMonth));

        $invoice = DB::transaction(function () use ($customer, $package, $month, $year, $dueDate) {
            $invoice = Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber($year, $month),
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'router_id' => $customer->router_id,
                'billing_month' => $month,
                'billing_year' => $year,
                'amount' => $package->price,
                'due_day' => $customer->due_day,
                'isolation_day' => $customer->isolation_day,
                'due_date' => $dueDate,
                'status' => InvoiceStatus::Unpaid,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $package->name,
                'qty' => 1,
                'price' => $package->price,
                'subtotal' => $package->price,
            ]);

            BillingLog::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'action' => 'invoice_generated',
                'description' => "Invoice {$invoice->invoice_number} generated for {$customer->name}",
            ]);

            Log::info('Invoice generated', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $customer->id,
                'amount' => $package->price,
                'billing_period' => $invoice->billing_period,
            ]);

            return $invoice;
        });

        app(PushNotificationService::class)->toCustomer($customer, new NewInvoiceNotification($invoice));

        return $invoice;
    }

    public function generateAllForMonth(int $month, int $year): array
    {
        $customers = Customer::where('status', 'Active')->get();
        $result = ['generated' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($customers as $customer) {
            try {
                $invoice = $this->generateForCustomer($customer, $month, $year);

                if ($invoice) {
                    $result['generated']++;
                } else {
                    $result['skipped']++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate invoice', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);

                $result['failed']++;
            }
        }

        BillingLog::create([
            'action' => 'batch_generate',
            'description' => "Batch invoice generation for {$month}/{$year}: {$result['generated']} generated, {$result['skipped']} skipped, {$result['failed']} failed",
        ]);

        return $result;
    }

    public function markOverdue(): int
    {
        $count = 0;
        $today = Carbon::today();

        $invoices = Invoice::where('status', InvoiceStatus::Unpaid->value)
            ->whereDate('due_date', '<', $today)
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->update(['status' => InvoiceStatus::Overdue]);

            $customer = $invoice->customer;
            if ($customer && $customer->service_status === ServiceStatus::Active) {
                $customer->update(['service_status' => 'overdue']);
            }

            app(PushNotificationService::class)->toCustomerById($invoice->customer_id, new InvoiceOverdueNotification($invoice));

            BillingLog::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'action' => 'marked_overdue',
                'description' => "Invoice {$invoice->invoice_number} marked as overdue",
            ]);

            $count++;
        }

        if ($count > 0) {
            Log::info('Overdue invoices marked', ['count' => $count]);
        }

        return $count;
    }

    public function getStats(): array
    {
        $now = Carbon::now();

        $inUserAreas = function ($query) {
            if (auth()->user()->isAdminArea()) {
                $query->whereHas('customer', fn ($q) => $q->whereIn('area_id', auth()->user()->areaIds()));
            }

            return $query;
        };

        $totalThisMonth = $inUserAreas(
            Invoice::where('billing_month', $now->month)->where('billing_year', $now->year)
        )->count();
        $totalUnpaid = $inUserAreas(Invoice::where('status', InvoiceStatus::Unpaid->value))->count();
        $totalOverdue = $inUserAreas(Invoice::where('status', InvoiceStatus::Overdue->value))->count();
        $totalPaid = $inUserAreas(Invoice::where('status', InvoiceStatus::Paid->value))->count();
        $dueToday = $inUserAreas(Invoice::whereDate('due_date', Carbon::today()))->count();
        $totalAmountUnpaid = $inUserAreas(
            Invoice::whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Overdue->value])
        )->sum('amount');

        $customerAreaScope = function ($query) {
            if (auth()->user()->isAdminArea()) {
                $query->whereIn('area_id', auth()->user()->areaIds());
            }

            return $query;
        };

        return [
            'total_this_month' => $totalThisMonth,
            'total_unpaid' => $totalUnpaid,
            'total_overdue' => $totalOverdue,
            'total_paid' => $totalPaid,
            'due_today' => $dueToday,
            'total_amount_unpaid' => $totalAmountUnpaid,
            'active_customers' => $customerAreaScope(Customer::where('service_status', 'active'))->count(),
            'overdue_customers' => $customerAreaScope(Customer::where('service_status', 'overdue'))->count(),
            'isolated_customers' => $customerAreaScope(Customer::where('service_status', 'isolated'))->count(),
        ];
    }

    public function getAll(array $filters = [])
    {
        $query = Invoice::with(['customer.area', 'package', 'router']);

        if (auth()->user()->isAdminArea()) {
            $query->whereHas('customer', fn ($q) => $q->whereIn('area_id', auth()->user()->areaIds()));
        }

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['router_id'])) {
            $query->where('router_id', $filters['router_id']);
        }

        if (! empty($filters['area_id'])) {
            $query->whereHas('customer', fn ($q) => $q->where('area_id', $filters['area_id']));
        }

        if (! empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        if (! empty($filters['month'])) {
            $query->where('billing_month', $filters['month']);
        }

        if (! empty($filters['year'])) {
            $query->where('billing_year', $filters['year']);
        }

        return $query->latest()->paginate(SettingSupport::perPage())->withQueryString();
    }

    protected function generateInvoiceNumber(int $year, int $month): string
    {
        $code = Setting::get('invoice_prefix', 'INV') ?: 'INV';
        $prefix = sprintf('%s-%d%02d-', $code, $year, $month);

        do {
            $number = $prefix.random_int(10000, 99999);
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
