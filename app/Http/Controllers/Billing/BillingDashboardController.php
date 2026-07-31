<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Jobs\Billing\GenerateInvoiceJob;
use App\Models\Router;
use App\Services\Billing\InvoiceService;
use Carbon\Carbon;

class BillingDashboardController extends Controller
{
    public function index(InvoiceService $invoiceService)
    {
        $stats = $invoiceService->getStats();
        $routers = Router::enabled()->orderBy('name')->get();

        return view('billing.dashboard', compact('stats', 'routers'));
    }

    public function generate()
    {
        $now = Carbon::now();
        $nextMonth = $now->copy()->addMonth();

        GenerateInvoiceJob::dispatch($nextMonth->month, $nextMonth->year);

        return redirect()->route('billing.dashboard')
            ->with('success', 'Generate invoice untuk bulan '.$nextMonth->translatedFormat('F Y').' sedang diproses melalui queue.');
    }
}