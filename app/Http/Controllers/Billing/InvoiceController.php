<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
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
        $invoice->load(['customer.area', 'customer.router', 'customer.package', 'package', 'router', 'items', 'isolationLogs']);

        return view('billing.invoices.show', compact('invoice'));
    }
}
