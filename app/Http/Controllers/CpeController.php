<?php

namespace App\Http\Controllers;

use App\Models\Cpe;
use App\Services\ActivityLoggerService;
use App\Services\Genieacs\CpeSyncService;
use App\Services\Genieacs\GenieacsService;
use App\Support\SettingSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CpeController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
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
        $cpe->load('customer');

        return view('cpes.show', compact('cpe'));
    }

    public function sync()
    {
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
}
