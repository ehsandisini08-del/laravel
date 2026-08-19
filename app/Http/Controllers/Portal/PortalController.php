<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Cpe;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ActivityLoggerService;
use App\Services\Genieacs\CpeSyncService;
use App\Support\SettingSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function dashboard(): View
    {
        $customer = auth('customer')->user();
        $customer->load(['area', 'router', 'package', 'pppSecret']);

        $activeBills = $customer->invoices()
            ->whereIn('status', ['unpaid', 'overdue'])
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get();

        $paymentProvider = Setting::get('payment_provider', 'none');

        return view('portal.dashboard', compact('customer', 'activeBills', 'paymentProvider'));
    }

    public function bills(): View
    {
        $customer = auth('customer')->user();

        $bills = $customer->invoices()
            ->whereIn('status', ['unpaid', 'overdue'])
            ->with(['package', 'items'])
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get();

        $paymentProvider = Setting::get('payment_provider', 'none');

        return view('portal.bills', compact('customer', 'bills', 'paymentProvider'));
    }

    public function account(): View
    {
        $customer = auth('customer')->user();

        $customer->load(['area', 'router', 'package', 'pppSecret']);

        return view('portal.account', compact('customer'));
    }

    public function wifi(): View
    {
        $customer = auth('customer')->user();

        $cpes = $customer->cpes()->latest('synced_at')->get();

        return view('portal.wifi', compact('customer', 'cpes'));
    }

    public function updateWifi(Request $request, Cpe $cpe): RedirectResponse
    {
        $customer = auth('customer')->user();

        abort_unless($cpe->customer_id === $customer->id, 403);

        $validated = $request->validate([
            'ssid' => ['nullable', 'string', 'max:255'],
            'wifi_password' => ['nullable', 'string', 'max:255'],
        ]);

        $ssid = $validated['ssid'] ?? null;
        $wifiPassword = $validated['wifi_password'] ?? null;

        $changed = $ssid !== $cpe->ssid || $wifiPassword !== $cpe->wifi_password;

        if (! $changed) {
            return back()->with('success', 'Tidak ada perubahan pada SSID atau password WiFi.');
        }

        if ($cpe->wifi_config_path !== null) {
            $pushResult = app(CpeSyncService::class)->pushWifiConfig($cpe, $ssid, $wifiPassword);

            if (! $pushResult['success']) {
                Log::warning('Failed to push wifi config from customer portal', [
                    'cpe_id' => $cpe->id,
                    'genieacs_id' => $cpe->genieacs_id,
                    'customer_id' => $customer->id,
                    'error' => $pushResult['error'],
                ]);

                return back()->with('error', 'Gagal mengirim perubahan ke perangkat: '.($pushResult['error'] ?? 'terjadi kesalahan').'. Perubahan tidak disimpan.');
            }
        }

        $cpe->update([
            'ssid' => $ssid,
            'wifi_password' => $wifiPassword,
        ]);

        $this->activityLogger->updated('CPE', "WiFi credentials updated via customer portal for CPE device '{$cpe->genieacs_id}'".($cpe->wifi_config_path !== null ? ' and pushed to device' : ''), $cpe);

        if ($cpe->wifi_config_path !== null) {
            return back()->with('success', 'SSID dan password WiFi berhasil diperbarui dan dikirim ke perangkat.');
        }

        return back()->with('success', 'SSID dan password WiFi disimpan. Parameter WiFi tidak terdeteksi di perangkat, jadi tidak dikirim.');
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
