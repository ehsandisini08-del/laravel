<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Support\SettingSupport;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        $customer = auth('customer')->user();
        $customer->load(['area', 'router', 'package', 'pppSecret']);

        $activeBills = $customer->invoices()
            ->whereIn('status', ['unpaid', 'overdue'])
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get();

        return view('portal.dashboard', compact('customer', 'activeBills'));
    }

    public function invoices(): View
    {
        $customer = auth('customer')->user();

        $invoices = $customer->invoices()
            ->with(['package', 'items'])
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->paginate(SettingSupport::perPage());

        return view('portal.invoices.index', compact('customer', 'invoices'));
    }

    public function showInvoice(Invoice $invoice): View
    {
        $customer = auth('customer')->user();

        abort_unless($invoice->customer_id === $customer->id, 403);

        $invoice->load(['items', 'payments.paidByUser', 'package']);

        $paymentProvider = Setting::get('payment_provider', 'none');

        return view('portal.invoices.show', compact('customer', 'invoice', 'paymentProvider'));
    }
}
