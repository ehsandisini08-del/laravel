<?php

namespace App\Http\Controllers;

use App\Models\Cpe;
use App\Services\ActivityLoggerService;
use App\Services\Genieacs\CpeSyncService;
use App\Services\Genieacs\GenieacsService;
use App\Support\SettingSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CpeController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $this->denyAdminArea();

        $query = Cpe::with('customer');

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('link')) {
            if ($request->input('link') === 'linked') {
                $query->whereNotNull('customer_id');
            } elseif ($request->input('link') === 'unlinked') {
                $query->whereNull('customer_id');
            }
        }

        $cpes = $query->latest('synced_at')->paginate(SettingSupport::perPage())->withQueryString();

        return view('cpes.index', compact('cpes'), [
            'genieacsConfigured' => app(GenieacsService::class)->isConfigured(),
        ]);
    }

    public function show(Cpe $cpe)
    {
        $this->denyAdminArea();

        $cpe->load('customer');

        return view('cpes.show', compact('cpe'));
    }

    public function sync()
    {
        $this->denyAdminArea();

        try {
            Log::info('Starting CPE sync from controller', [
                'user_id' => auth()->id(),
            ]);

            $result = app(CpeSyncService::class)->sync();

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 500);
            }

            Log::info('CPE sync completed from controller', [
                'total' => $result['total'],
                'matched' => $result['matched'],
                'user_id' => auth()->id(),
            ]);

            $this->activityLogger->synced('CPE', "CPE devices synced from GenieACS ({$result['total']} devices, {$result['matched']} matched)");

            return response()->json([
                'success' => true,
                'message' => "{$result['total']} device(s) disinkronkan, {$result['matched']} terhubung ke customer.",
                'total' => $result['total'],
                'matched' => $result['matched'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync CPE from controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi CPE: '.$e->getMessage(),
            ], 500);
        }
    }

    public function refresh(Cpe $cpe)
    {
        $this->denyAdminArea();

        try {
            $result = app(CpeSyncService::class)->refreshDevice($cpe->genieacs_id);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 500);
            }

            $this->activityLogger->synced('CPE', "CPE device '{$result['cpe']->genieacs_id}' refreshed from GenieACS");

            return response()->json([
                'success' => true,
                'message' => 'Data device berhasil diperbarui dari GenieACS.',
                'cpe' => $result['cpe']->refresh()->load('customer'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh CPE from controller', [
                'cpe_id' => $cpe->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal refresh device: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Cpe $cpe): RedirectResponse
    {
        $this->denyAdminArea();

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
                Log::warning('Failed to push wifi config to device', [
                    'cpe_id' => $cpe->id,
                    'genieacs_id' => $cpe->genieacs_id,
                    'error' => $pushResult['error'],
                    'user_id' => auth()->id(),
                ]);

                return back()->with('error', 'Gagal mengirim perubahan ke perangkat: '.($pushResult['error'] ?? 'terjadi kesalahan').'. Perubahan tidak disimpan.');
            }
        }

        $cpe->update([
            'ssid' => $ssid,
            'wifi_password' => $wifiPassword,
        ]);

        $this->activityLogger->updated('CPE', "WiFi credentials updated for CPE device '{$cpe->genieacs_id}'".($cpe->wifi_config_path !== null ? ' and pushed to device' : ''));

        if ($cpe->wifi_config_path !== null) {
            return back()->with('success', 'SSID dan password WiFi berhasil diperbarui dan dikirim ke perangkat.');
        }

        return back()->with('success', 'SSID dan password WiFi disimpan. Parameter WiFi tidak terdeteksi di perangkat, jadi tidak dikirim.');
    }

    public function reboot(Cpe $cpe)
    {
        $this->denyAdminArea();

        try {
            $result = app(CpeSyncService::class)->rebootDevice($cpe);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 500);
            }

            $this->activityLogger->updated('CPE', "CPE device '{$cpe->genieacs_id}' reboot command sent via GenieACS");

            return response()->json([
                'success' => true,
                'message' => 'Perintah restart berhasil dikirim ke perangkat. Device akan reboot dalam beberapa detik.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reboot CPE', [
                'cpe_id' => $cpe->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah restart: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function denyAdminArea(): void
    {
        if (auth()->user()->isAdminArea()) {
            abort(403, 'Akses ditolak.');
        }
    }
}
