<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Jobs\WhatsApp\BroadcastJob;
use App\Models\Area;
use App\Models\Package;
use App\Models\WaDevice;
use App\Models\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaBroadcastController extends Controller
{
    public function create()
    {
        $devices = WaDevice::where('status', 'connected')->get();
        $templates = WaTemplate::where('is_active', true)->get();
        $areas = Area::all();
        $packages = Package::all();

        return view('whatsapp.broadcast.create', compact('devices', 'templates', 'areas', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'exists:wa_devices,id'],
            'template_id' => ['nullable', 'exists:wa_templates,id'],
            'message' => ['required_without:template_id', 'string', 'max:5000'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'status' => ['nullable', 'string', 'in:active,inactive,suspended'],
        ]);

        BroadcastJob::dispatch(
            $validated['device_id'],
            $validated['template_id'] ?? null,
            $validated['message'] ?? '',
            [
                'area_id' => $validated['area_id'] ?? null,
                'package_id' => $validated['package_id'] ?? null,
                'status' => $validated['status'] ?? null,
            ],
        );

        Log::info('Broadcast started', [
            'device_id' => $validated['device_id'],
            'template_id' => $validated['template_id'] ?? null,
        ]);

        return redirect()->route('whatsapp.broadcast.create')
            ->with('success', 'Broadcast sedang diproses melalui queue.');
    }
}
