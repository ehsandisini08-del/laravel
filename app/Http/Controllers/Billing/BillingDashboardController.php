<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\Billing\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BillingDashboardController extends Controller
{
    public function index(InvoiceService $invoiceService)
    {
        $stats = $invoiceService->getStats();
        $routers = Router::enabled()->orderBy('name')->get();

        return view('billing.dashboard', compact('stats', 'routers'));
    }

    public function generate(InvoiceService $invoiceService)
    {
        $now = Carbon::now();
        $nextMonth = $now->copy()->addMonth();

        try {
            $result = $invoiceService->generateAllForMonth($nextMonth->month, $nextMonth->year);

            return redirect()->route('billing.dashboard')
                ->with('success', 'Invoice untuk '.$nextMonth->translatedFormat('F Y').' berhasil dibuat: '.$result['generated'].' dibuat, '.$result['skipped'].' dilewati, '.$result['failed'].' gagal.');
        } catch (\Exception $e) {
            Log::error('Failed to generate invoices', [
                'month' => $nextMonth->month,
                'year' => $nextMonth->year,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('billing.dashboard')
                ->with('error', 'Gagal membuat invoice: '.$e->getMessage());
        }
    }
}
