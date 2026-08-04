<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\StoreWaDeviceRequest;
use App\Models\WaDevice;
use App\Services\ActivityLoggerService;
use App\Services\WhatsApp\WhatsAppGatewayService;
use App\Support\SettingSupport;
use Exception;
use Illuminate\Support\Facades\Log;

class WaDeviceController extends Controller
{
    public function __construct(
        protected WhatsAppGatewayService $whatsAppGatewayService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index()
    {
        $devices = WaDevice::latest()->paginate(SettingSupport::perPage())->withQueryString();

        return view('whatsapp.devices.index', compact('devices'));
    }

    public function create()
    {
        return view('whatsapp.devices.create');
    }

    public function store(StoreWaDeviceRequest $request)
    {
        try {
            $device = $this->whatsAppGatewayService->createDevice(
                $request->device_name,
                $request->session_name,
            );

            $this->activityLogger->created('WhatsApp Device', "Device {$device->device_name} created", $device);

            $message = $device->status === 'disconnected'
                ? 'Device berhasil dibuat di database, tetapi tidak terhubung ke Baileys Gateway. Generate QR nanti.'
                : 'Device berhasil dibuat. Silakan scan QR Code.';

            return redirect()->route('whatsapp.devices.show', $device)
                ->with('success', $message);
        } catch (Exception $e) {
            Log::error('Failed to create WhatsApp device', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Gagal membuat device: '.$e->getMessage());
        }
    }

    public function show(WaDevice $device)
    {
        return view('whatsapp.devices.show', compact('device'));
    }

    public function destroy(WaDevice $device)
    {
        try {
            $name = $device->device_name;

            $this->whatsAppGatewayService->deleteDevice($device);

            $this->activityLogger->deleted('WhatsApp Device', "Device {$name} deleted");

            return redirect()->route('whatsapp.devices.index')
                ->with('success', 'Device berhasil dihapus.');
        } catch (Exception $e) {
            Log::error('Failed to delete WhatsApp device', ['error' => $e->getMessage()]);

            return back()->with('error', 'Gagal menghapus device: '.$e->getMessage());
        }
    }

    public function generateQr(WaDevice $device)
    {
        try {
            $qrCode = $this->whatsAppGatewayService->generateQr($device);

            $this->activityLogger->updated('WhatsApp Device', "QR Code generated for {$device->device_name}", $device);

            return response()->json([
                'success' => true,
                'qr_code' => $qrCode,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function status(WaDevice $device)
    {
        try {
            $status = $this->whatsAppGatewayService->refreshStatus($device);

            return response()->json([
                'success' => true,
                'status' => $status,
                'status_label' => $device->status_label,
                'phone_number' => $device->phone_number,
                'profile_name' => $device->profile_name,
                'last_seen' => $device->last_seen?->diffForHumans(),
                'status_color' => $device->status_color,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function disconnect(WaDevice $device)
    {
        try {
            $this->whatsAppGatewayService->disconnect($device);

            $this->activityLogger->updated('WhatsApp Device', "Device {$device->device_name} disconnected", $device);

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(WaDevice $device)
    {
        try {
            $this->whatsAppGatewayService->logout($device);

            $this->activityLogger->updated('WhatsApp Device', "Device {$device->device_name} logged out", $device);

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function sync()
    {
        try {
            $count = $this->whatsAppGatewayService->syncDevices();

            $this->activityLogger->synced('WhatsApp Device', "{$count} devices synced");

            return redirect()->route('whatsapp.devices.index')
                ->with('success', "{$count} device berhasil disinkronkan.");
        } catch (Exception $e) {
            return back()->with('error', 'Gagal sinkronisasi: '.$e->getMessage());
        }
    }
}
