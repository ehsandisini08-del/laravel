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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function cetakInvoice(SettingService $settingService)
    {
        $this->denyTeknisi();

        $customers = $this->customersForArea();

        $company = $settingService->byGroup('company');

        return view('billing.cetak-invoice', compact('customers', 'company'));
    }

    public function previewCetakInvoice(Request $request): JsonResponse
    {
        $this->denyTeknisi();

        $invoices = $this->invoicesForFilter($request);

        return response()->json([
            'count' => $invoices->count(),
            'invoices' => $invoices->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'billing_period' => $invoice->billing_period,
                'amount' => number_format((float) $invoice->amount, 0, ',', '.'),
                'due_date' => $invoice->due_date?->format('d M Y'),
                'status' => $invoice->status_label,
                'status_color' => $invoice->status_color,
            ]),
        ]);
    }

    public function downloadCetakInvoicePdf(Request $request, SettingService $settingService)
    {
        $this->denyTeknisi();

        $invoices = $this->invoicesForFilter($request);

        if ($invoices->isEmpty()) {
            return redirect()->route('billing.cetak-invoice')->with('error', 'Tidak ada invoice yang cocok dengan filter.');
        }

        $customer = Customer::find($request->input('customer_id'));

        $this->activityLogger->created('Invoice', $invoices->count().' invoice dicetak (PDF)', null, [
            'count' => $invoices->count(),
            'ids' => $invoices->pluck('id')->all(),
            'customer_id' => $customer?->id,
            'printed_by' => auth()->user()?->name,
        ]);

        $filename = 'invoice-'.str()->slug($customer?->name ?? 'cetak').'.pdf';

        $pdf = Pdf::loadView('billing.invoices.pdf', [
            'invoices' => $invoices,
            'company' => $settingService->byGroup('company'),
        ]);

        return $pdf->download($filename);
    }

    public function printInvoice(Invoice $invoice, SettingService $settingService)
    {
        $this->denyTeknisi();
        $this->authorizeInvoiceAccess($invoice);

        $invoice->load(['customer.area', 'customer.package', 'package', 'router', 'items', 'payments']);

        return view('billing.invoices.print', [
            'invoices' => collect([$invoice]),
            'company' => $settingService->byGroup('company'),
        ]);
    }

    public function index(Request $request, InvoiceService $invoiceService)
    {
        $this->denyTeknisi();

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
        $this->denyTeknisi();

        if (auth()->user()->isAdminArea() && ! in_array($invoice->customer?->area_id, auth()->user()->areaIds(), true)) {
            abort(403, 'Akses ditolak.');
        }

        $invoice->load(['customer.area', 'customer.router', 'customer.package', 'package', 'router', 'items', 'isolationLogs', 'payments.paidByUser']);

        return view('billing.invoices.show', compact('invoice'));
    }

    public function pay(Invoice $invoice, PaymentService $paymentService)
    {
        $this->denyTeknisi();

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
        $this->denyTeknisi();

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
        $this->denyTeknisi();

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

    protected function customersForArea()
    {
        $query = Customer::query()->orderBy('name');

        if (auth()->user()->isAdminArea()) {
            $query->whereIn('area_id', auth()->user()->areaIds());
        }

        return $query->get(['id', 'name', 'customer_code', 'phone', 'address']);
    }

    protected function invoicesForFilter(Request $request)
    {
        $customerId = (int) $request->input('customer_id');
        $fromMonth = (int) $request->input('from_month', now()->month);
        $fromYear = (int) $request->input('from_year', now()->year);
        $toMonth = (int) $request->input('to_month', now()->month);
        $toYear = (int) $request->input('to_year', now()->year);
        $status = $request->input('status');

        $fromKey = $fromYear * 12 + $fromMonth;
        $toKey = $toYear * 12 + $toMonth;

        $query = Invoice::with(['customer.area', 'customer.package', 'package', 'router', 'items', 'payments'])
            ->where('customer_id', $customerId)
            ->whereRaw('(billing_year * 12 + billing_month) BETWEEN ? AND ?', [$fromKey, $toKey])
            ->orderBy('billing_year')
            ->orderBy('billing_month');

        if (auth()->user()->isAdminArea()) {
            $query->whereHas('customer', fn ($q) => $q->whereIn('area_id', auth()->user()->areaIds()));
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    protected function denyTeknisi(): void
    {
        if (auth()->user()->isTeknisi()) {
            abort(403, 'Akses ditolak.');
        }
    }

    protected function authorizeInvoiceAccess(Invoice $invoice): void
    {
        $this->denyTeknisi();

        if (auth()->user()->isAdminArea() && ! in_array($invoice->customer?->area_id, auth()->user()->areaIds(), true)) {
            abort(403, 'Akses ditolak.');
        }
    }
}
