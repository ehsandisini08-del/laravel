<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Services\ActivityLoggerService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\SettingService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function cetakInvoice(Request $request, InvoiceService $invoiceService, SettingService $settingService)
    {
        $filters = $request->only(['search', 'status', 'router_id', 'area_id', 'package_id']);

        $defaultMonth = $request->filled('month') ? (int) $request->month : now()->month;
        $defaultYear = $request->filled('year') ? (int) $request->year : now()->year;
        $filters['month'] = $defaultMonth;
        $filters['year'] = $defaultYear;

        $invoices = $invoiceService->getAll($filters)->withQueryString();

        if (auth()->user()->isAdminArea()) {
            $areaIds = auth()->user()->areaIds();
            $areas = Area::active()->whereIn('id', $areaIds)->orderBy('name')->get();
            $packages = Package::active()
                ->whereHas('areas', fn ($q) => $q->whereIn('area_id', $areaIds))
                ->orderBy('name')
                ->get();
            $routers = Router::enabled()
                ->whereIn('id', Customer::whereIn('area_id', $areaIds)->whereNotNull('router_id')->distinct()->pluck('router_id'))
                ->orderBy('name')
                ->get();
        } else {
            $routers = Router::enabled()->orderBy('name')->get();
            $areas = Area::active()->orderBy('name')->get();
            $packages = Package::active()->orderBy('name')->get();
        }

        $company = $settingService->byGroup('company');

        return view('billing.cetak-invoice', compact('invoices', 'routers', 'areas', 'packages', 'defaultMonth', 'defaultYear', 'company'));
    }

    public function printInvoice(Invoice $invoice, SettingService $settingService)
    {
        $this->authorizeInvoiceAccess($invoice);

        $invoice->load(['customer.area', 'customer.package', 'package', 'router', 'items', 'payments']);

        return view('billing.invoices.print', [
            'invoices' => collect([$invoice]),
            'company' => $settingService->byGroup('company'),
        ]);
    }

    public function printSelected(Request $request, SettingService $settingService)
    {
        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->slice(0, 200)
            ->all();

        if (empty($ids)) {
            return back()->with('error', 'Pilih minimal satu invoice untuk dicetak.');
        }

        $invoices = Invoice::with(['customer.area', 'customer.package', 'package', 'router', 'items', 'payments'])
            ->whereIn('id', $ids)
            ->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'Invoice yang dipilih tidak ditemukan.');
        }

        foreach ($invoices as $invoice) {
            $this->authorizeInvoiceAccess($invoice);
        }

        $this->activityLogger->created('Invoice', $invoices->count().' invoice dicetak', null, [
            'count' => $invoices->count(),
            'ids' => $invoices->pluck('id')->all(),
            'printed_by' => auth()->user()?->name,
        ]);

        return view('billing.invoices.print', [
            'invoices' => $invoices,
            'company' => $settingService->byGroup('company'),
        ]);
    }

    public function index(Request $request, InvoiceService $invoiceService)
    {
        $filters = $request->only(['search', 'status', 'router_id', 'area_id', 'package_id']);

        $defaultMonth = $request->filled('month') ? (int) $request->month : now()->month;
        $defaultYear = $request->filled('year') ? (int) $request->year : now()->year;
        $filters['month'] = $defaultMonth;
        $filters['year'] = $defaultYear;

        $invoices = $invoiceService->getAll($filters);

        if (auth()->user()->isAdminArea()) {
            $areaIds = auth()->user()->areaIds();
            $areas = Area::active()->whereIn('id', $areaIds)->orderBy('name')->get();
            $packages = Package::active()
                ->whereHas('areas', fn ($q) => $q->whereIn('area_id', $areaIds))
                ->orderBy('name')
                ->get();
            $routers = Router::enabled()
                ->whereIn('id', Customer::whereIn('area_id', $areaIds)->whereNotNull('router_id')->distinct()->pluck('router_id'))
                ->orderBy('name')
                ->get();
        } else {
            $routers = Router::enabled()->orderBy('name')->get();
            $areas = Area::active()->orderBy('name')->get();
            $packages = Package::active()->orderBy('name')->get();
        }

        return view('billing.invoices.index', compact('invoices', 'routers', 'areas', 'packages', 'defaultMonth', 'defaultYear'));
    }

    public function show(Invoice $invoice)
    {
        if (auth()->user()->isAdminArea() && ! in_array($invoice->customer?->area_id, auth()->user()->areaIds(), true)) {
            abort(403, 'Akses ditolak.');
        }

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

    public function destroyMany(Request $request)
    {
        abort_unless(auth()->user()->canDeleteInvoices(), 403);

        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->slice(0, 500)
            ->all();

        $invoices = Invoice::whereIn('id', $ids)->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'Tidak ada invoice yang dipilih untuk dihapus.');
        }

        foreach ($invoices as $invoice) {
            $invoice->reminders()->delete();
            $invoice->delete();
        }

        $this->activityLogger->deleted('Invoice', $invoices->count().' invoice dihapus massal', null, [
            'count' => $invoices->count(),
            'ids' => $invoices->pluck('id')->all(),
            'deleted_by' => auth()->user()?->name,
        ]);

        return back()->with('success', $invoices->count().' invoice berhasil dihapus.');
    }

    protected function authorizeInvoiceAccess(Invoice $invoice): void
    {
        if (auth()->user()->isAdminArea() && ! in_array($invoice->customer?->area_id, auth()->user()->areaIds(), true)) {
            abort(403, 'Akses ditolak.');
        }
    }
}
