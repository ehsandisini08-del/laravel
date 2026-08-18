<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\Billing\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingDashboardController extends Controller
{
    public function index(InvoiceService $invoiceService)
    {
        $stats = $invoiceService->getStats();
        $routers = Router::enabled()->orderBy('name')->get();

        return view('billing.dashboard', compact('stats', 'routers'));
    }

    public function generate(Request $request, InvoiceService $invoiceService)
    {
        abort_unless(auth()->user()->canGenerateInvoices(), 403, 'Akses ditolak.');

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:'.(now()->year - 1).','.(now()->year + 2)],
        ]);

        $target = now()->addMonth();
        $month = isset($validated['month']) ? (int) $validated['month'] : $target->month;
        $year = isset($validated['year']) ? (int) $validated['year'] : $target->year;

        try {
            $result = $invoiceService->generateAllForMonth($month, $year);

            return redirect()->back()
                ->with('success', 'Invoice untuk '.Carbon::create($year, $month, 1)->translatedFormat('F Y').' berhasil dibuat: '.$result['generated'].' dibuat, '.$result['skipped'].' dilewati, '.$result['failed'].' gagal.');
        } catch (\Exception $e) {
            Log::error('Failed to generate invoices', [
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Gagal membuat invoice: '.$e->getMessage());
        }
    }
}
