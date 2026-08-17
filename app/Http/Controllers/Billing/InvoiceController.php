<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Services\ActivityLoggerService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request, InvoiceService $invoiceService)
    {
        $invoices = $invoiceService->getAll($request->only(['search', 'status', 'router_id', 'area_id', 'package_id', 'month', 'year']));
        $routers = Router::enabled()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $packages = Package::active()->orderBy('name')->get();

        $defaultMonth = $request->filled('month') ? $request->month : now()->month;
        $defaultYear = $request->filled('year') ? $request->year : now()->year;

        return view('billing.invoices.index', compact('invoices', 'routers', 'areas', 'packages', 'defaultMonth', 'defaultYear'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer.area', 'customer.router', 'customer.package', 'package', 'router', 'items', 'isolationLogs', 'payments.paidByUser']);

        return view('billing.invoices.show', compact('invoice'));
    }

    public function pay(Invoice $invoice, PaymentService $paymentService)
    {
        $result = $paymentService->markAsPaid($invoice, [
            'method' => 'cash',
            'paid_by' => auth()->user(),
            'notes' => 'Pembayaran tunai oleh admin',
        ]);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        if ($result['reactivated'] === false) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    public function destroy(Invoice $invoice)
    {
        abort_unless(auth()->user()->canDeleteInvoices(), 403);

        $number = $invoice->invoice_number;
        $period = $invoice->billing_period;

        $invoice->reminders()->delete();
        $invoice->delete();

        $this->activityLogger->deleted('Invoice', "Invoice {$number} ({$period}) dihapus", null, [
            'invoice_number' => $number,
            'billing_period' => $period,
            'customer_id' => $invoice->customer_id,
            'deleted_by' => auth()->user()?->name,
        ]);

        return redirect()->route('billing.invoices.index')
            ->with('success', "Invoice {$number} berhasil dihapus.");
    }
}
